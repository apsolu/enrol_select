<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// phpcs:disable moodle.Commenting.TodoComment.MissingInfoInline

/**
 * Page pour afficher la vue d'ensemble du module enrol_select.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_select\enrol;
use enrol_select\output\overview;
use local_apsolu\core\course;

require('../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->dirroot . '/enrol/select/blocklib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);

require_login($courseorid = null, $autologinguest = false);

$context = context_user::instance($USER->id);

$PAGE->set_url('/enrol/select/overview.php');
$PAGE->set_pagelayout('base');

$PAGE->set_context($context);

$PAGE->set_heading(get_string('overviewtitle', 'enrol_select'));
$PAGE->set_title(get_string('pluginname', 'enrol_select'));

$select = enrol_get_plugin('select');

$capabilities = [
    'moodle/category:manage',
    'moodle/course:create',
];

$time = null;
$cohorts = null;
$managersfilters = '';
if (has_any_capability($capabilities, context_system::instance()) === true) {
    // TODO: déplacer cette page dans le répertoire classes/form.
    require_once(__DIR__ . '/overview_managers_filters_form.php');

    $mform = new overview_managers_filters_form();
    if ($data = $mform->get_data()) {
        $time = $data->now;
        $cohorts = $data->cohorts;

        if (count($cohorts) === 0) {
            $time = null;
            $cohorts = null;
        }
    }

    $managersfiltersdata = new stdClass();
    $managersfiltersdata->form = $mform->render();
    $managersfilters = $OUTPUT->render_from_template('enrol_select/overview_manager_filters', $managersfiltersdata);
}

// Activities : get all visible courses for current user.
$courses = [];
foreach ($DB->get_records('apsolu_courses_types', [], $sort = 'sortorder') as $coursetype) {
    $courses[$coursetype->id] = [];
}

foreach (Course::get_records(['visible' => 1]) as $course) {
    if (isset($course->customfields['type']) === false) {
        // Le champ "type" n'est pas défini.
        continue;
    }

    $coursetypeid = $course->customfields['type']->get('fieldid');

    if (isset($courses[$coursetypeid]) === false) {
        continue;
    }

    $courses[$coursetypeid][$course->id] = $course;
}

$enrols = [];
$invalidcourses = []; // Permet de stocker les cours qui proprosent 2 méthodes d'inscription à la fois.

$recordset = Enrol::get_available_enrol_methods($time, $cohorts);
foreach ($recordset as $enrol) {
    if (isset($invalidcourses[$enrol->courseid]) === true) {
        // Ce courseid a été marqué comme invalide.
        continue;
    }

    $course = false;
    foreach ($courses as $coursetypeid => $coursesbytype) {
        if (isset($coursesbytype[$enrol->courseid]) === false) {
            continue;
        }

        $course = $coursesbytype[$enrol->courseid];
        break;
    }

    if ($course === false) {
        // Ce cours n'existe pas ou n'est pas visible. Il est noté invalide.
        $invalidcourses[$enrol->courseid] = $enrol->courseid;
        continue;
    }

    if (isset($enrols[$enrol->courseid]) === true) {
        // Ce cours possède plusieurs méthodes d'inscription valident en même temps. Ce cas ne peut pas être traité actuellement.
        unset($courses[$coursetypeid][$enrol->courseid]);
        $invalidcourses[$enrol->courseid] = $enrol->courseid;
        continue;
    }

    $courses[$coursetypeid][$enrol->courseid] = $course;
    $enrols[$enrol->courseid] = $enrol;
}
$recordset->close();

// CSS.
$PAGE->requires->css('/enrol/select/styles/select2.min.css');
$PAGE->requires->css('/enrol/select/styles/ol.css');

// Javascript.
$PAGE->requires->js_call_amd('enrol_select/select_mapping', 'initialise');
$PAGE->requires->js_call_amd('enrol_select/select_filter', 'initialise');
$PAGE->requires->js_call_amd('enrol_select/select_overview', 'initialise');
$PAGE->requires->js_call_amd('enrol_select/select_enrol', 'initialise', ['url' => $CFG->wwwroot]);

// Navigation.
$PAGE->navbar->add(get_string('enrolment', 'enrol_select'));

$renderable = new Overview($courses, $enrols);
$output = $PAGE->get_renderer('enrol_select');

echo $OUTPUT->header();
echo $managersfilters;
echo $output->render($renderable);
echo $OUTPUT->footer();
