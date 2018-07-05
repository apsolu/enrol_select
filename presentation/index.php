<?php

use UniversiteRennes2\Apsolu;

require __DIR__.'/../../../config.php';
require_once($CFG->dirroot.'/enrol/select/locallib.php');

$siteid = optional_param('siteid', 0, PARAM_INT);

$sites = $DB->get_records('apsolu_cities', $params = array(), $sort = 'name');
if (isset($sites[$siteid]) === true) {
    $sites[$siteid]->active = true;
} else {
    $siteid = 0;
}

$PAGE->set_url('/enrol/select/presentation/index.php');

if ($siteid === 0) {
    $title = 'Les créneaux du SIUAPS';
} else {
    $title = 'Les créneaux du SIUAPS du site de '.$sites[$siteid]->name;
}

$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
// $PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add($title);

$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/enrol/select/styles/select2.min.css'));
$PAGE->requires->js_call_amd('enrol_select/presentation', 'initialise');

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$roles = UniversiteRennes2\Apsolu\get_activities_roles();
$teachers = UniversiteRennes2\Apsolu\get_activities_teachers();

$filters = array();

$filters['sites'] = new \stdClass();
$filters['sites']->label = 'Site de pratique';
$filters['sites']->values = array();

$filters['sports'] = new \stdClass();
$filters['sports']->label = 'Activité';
$filters['sports']->values = array();

$filters['areas'] = new \stdClass();
$filters['areas']->label = 'Lieu de pratique';
$filters['areas']->values = array();

$filters['periods'] = new \stdClass();
$filters['periods']->label = 'Période de l\'année';
$filters['periods']->values = array('S1', 'S2');

$filters['times'] = new \stdClass();
$filters['times']->label = 'Période de la journée';
$filters['times']->values = array();
$filters['times']->values[] = 'Matin';
$filters['times']->values[] = 'Midi';
$filters['times']->values[] = 'Après-midi';
$filters['times']->values[] = 'Soir';

$filters['weekdays'] = new \stdClass();
$filters['weekdays']->label = 'Jour de la semaine';
$filters['weekdays']->values = array();
$filters['weekdays']->values[] = get_string('monday', 'calendar');
$filters['weekdays']->values[] = get_string('tuesday', 'calendar');
$filters['weekdays']->values[] = get_string('wednesday', 'calendar');
$filters['weekdays']->values[] = get_string('thursday', 'calendar');
$filters['weekdays']->values[] = get_string('friday', 'calendar');
$filters['weekdays']->values[] = get_string('saturday', 'calendar');
$filters['weekdays']->values[] = get_string('sunday', 'calendar');

$filters['locations'] = new \stdClass();
$filters['locations']->label = 'Lieu';
$filters['locations']->values = array();

$filters['skills'] = new \stdClass();
$filters['skills']->label = 'Niveau';
$filters['skills']->values = array();

$filters['roles'] = new \stdClass();
$filters['roles']->label = 'Type d\'inscription';
$filters['roles']->values = array();

$filters['teachers'] = new \stdClass();
$filters['teachers']->label = 'Enseignants';
$filters['teachers']->values = array();

// category, site, activity, period, jour, start, end, level, zone geo, zone, enroltype, enseignant
$courses = array();
foreach (UniversiteRennes2\Apsolu\get_activities($siteid) as $activity) {
    if (isset($courses[$activity->sport]) === false) {
        $courses[$activity->sport] = new \stdClass();
        $courses[$activity->sport]->name = $activity->sport;
        $courses[$activity->sport]->courses = array();
    }

    $activity->weekday = get_string($activity->weekday, 'calendar');

    $activity->roles = array();
    if (isset($roles[$activity->id]) === true) {
        $activity->roles = array_values($roles[$activity->id]);
    }

    $activity->teachers = array();
    if (isset($teachers[$activity->id]) === true) {
        $activity->teachers = array_values($teachers[$activity->id]);
    }

    // Times.
    $time = array();
    if ($activity->endtime <= '12:30') {
        // Matin.
        $time[] = $filters['times']->values[0];
    }

    if ($activity->starttime >= '11:30' && $activity->starttime < '14:00') {
        // Midi.
        $time[] = $filters['times']->values[1];
    }

    if ($activity->starttime >= '13:30' && $activity->starttime < '18:30') {
        // Après-midi.
        $time[] = $filters['times']->values[2];
    }

    if ($activity->starttime >= '18:30') {
        // Soir.
        $time[] = $filters['times']->values[3];
    }

    $activity->time = implode(' ', $time);

    if ($siteid === 0) {
        $activity->area = $activity->site.' - '.$activity->area;
    }

    $courses[$activity->sport]->courses[] = $activity;


    // Filtres.
    foreach (array('area', 'location', 'skill', 'site', 'sport') as $type) {
        if (in_array($activity->{$type}, $filters[$type.'s']->values, $strict = true) === false) {
            $filters[$type.'s']->values[] = $activity->{$type};
        }
    }

    foreach ($activity->roles as $role) {
        if (isset($filters['roles']->values[$role->id]) === false) {
            $filters['roles']->values[$role->id] = $role->name;
        }
    }

    foreach ($activity->teachers as $teacher) {
        if (isset($filters['teachers']->values[$teacher->lastname.' '.$teacher->firstname]) === false) {
            $filters['teachers']->values[$teacher->lastname.' '.$teacher->firstname] = $teacher->firstname.' '.$teacher->lastname;
        }
    }
}

ksort($courses);
$courses = array_values($courses);

$filters['roles']->values = array_values($filters['roles']->values);
ksort($filters['teachers']->values);
$filters['teachers']->values = array_values($filters['teachers']->values);
unset($filters['teachers'], $filters['roles'], $filters['locations'], $filters['sites'], $filters['skills']);
foreach ($filters as $name => $filter) {
    if (in_array($name, array('periods', 'weekdays', 'teachers', 'times'), $strict = true) === false) {
        sort($filter->values);
    }

    $filter->html = \html_writer::select($filter->values, $name, $selected = '', $nothing = false, $attributes = array('class' => 'apsolu-enrol-selects', 'multiple' => 'multiple'));
}

$data = array();
$data['courses'] = $courses;
$data['sites'] = array_values($sites);
$data['sites_count'] = count($data['sites']);
$data['filters'] = array_values($filters);

echo $OUTPUT->render_from_template('enrol_select/presentation_index', $data);

echo $OUTPUT->footer();
