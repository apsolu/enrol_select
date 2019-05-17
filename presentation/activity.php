<?php

use UniversiteRennes2\Apsolu;

require __DIR__.'/../../../config.php';
require_once($CFG->dirroot.'/enrol/select/locallib.php');

$activityid = optional_param('id', 0, PARAM_INT);
$activityname = optional_param('name', '', PARAM_TAG);

if (empty($activityid) === true && empty($activityname) === true) {
    print_error('invalidrecordunknown');
}

$activities = UniversiteRennes2\Apsolu\get_activities($siteid = 0, $activityid, $activityname);

if (count($activities) === 0) {
    print_error('invalidrecordunknown');
}

$PAGE->set_url('/enrol/select/presentation/activity.php');

$title = current($activities)->sport;

$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
// $PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add(get_string('slots_of_service', 'enrol_select'), new moodle_url('/enrol/select/presentation/index.php'));
$PAGE->navbar->add($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$roles = UniversiteRennes2\Apsolu\get_activities_roles();
$teachers = UniversiteRennes2\Apsolu\get_activities_teachers();

// category, site, activity, period, jour, start, end, level, zone geo, zone, enroltype, enseignant
$courses = array();
foreach ($activities as $activity) {
    if (isset($courses[$activity->sport]) === false) {
        $courses[$activity->sport] = new \stdClass();
        $courses[$activity->sport]->name = $activity->sport;
        $courses[$activity->sport]->url = $activity->url;
        $courses[$activity->sport]->description = $activity->description;
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

    $activity->area = $activity->site.' - '.$activity->area;

    $courses[$activity->sport]->courses[] = $activity;
}

ksort($courses);
$courses = array_values($courses);

$data = array('courses' => $courses);
echo $OUTPUT->render_from_template('enrol_select/presentation_activity', $data);

echo $OUTPUT->footer();
