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

/**
 * Adds new instance of enrol_select to specified course
 * or edits current instance.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;

require('../../config.php');
require_once(__DIR__.'/locallib.php');
require_once($CFG->dirroot.'/enrol/select/blocklib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$roleid = optional_param('roleid', 0, PARAM_INT);

require_login();

$capabilities = array(
    'moodle/category:manage',
    'moodle/course:create',
);

require_capability($capabilities[0], context_system::instance());
require_capability($capabilities[1], context_system::instance());

$context = context_user::instance($USER->id);

$PAGE->set_url('/enrol/select/overview_manager.php');
$PAGE->set_pagelayout('base');

$PAGE->set_context($context);

$PAGE->set_heading(get_string('overviewtitlemanager', 'enrol_select'));
$PAGE->set_title(get_string('pluginname', 'enrol_select'));

$select = enrol_get_plugin('select');

// Activities : get all visible courses for managers.
$courses = apsolu\get_potential_user_activities($manager = true);

$overviewactivitiesdata = (object) array('activities' => array(), 'count_activities' => 0);

$currentactivity = null;
foreach ($courses as $course) {
    $course->debug = ($CFG->debugdisplay == 1);
    if ($currentactivity !== $course->sport) {
        $currentactivity = $course->sport;

        $overviewactivitiesdata->activities[$overviewactivitiesdata->count_activities] = new stdClass();
        $overviewactivitiesdata->activities[$overviewactivitiesdata->count_activities]->sportid = $course->sportid;
        $overviewactivitiesdata->activities[$overviewactivitiesdata->count_activities]->name = $course->sport;
        $overviewactivitiesdata->activities[$overviewactivitiesdata->count_activities]->description = $course->description;
        $overviewactivitiesdata->activities[$overviewactivitiesdata->count_activities]->courses = array();
        $overviewactivitiesdata->count_activities++;
    }

    $overviewactivitiesdata->activities[$overviewactivitiesdata->count_activities - 1]->courses[] = $course;
}

$overviewactivitiesdata->roles = array_values(apsolu\get_custom_student_roles());
$overviewactivitiesdata->info_pix_url = $OUTPUT->pix_url('i/info');
$overviewactivitiesdata->marker_pix_url = $OUTPUT->pix_url('a/marker', 'enrol_select');
$overviewactivitiesdata->www_url = $CFG->wwwroot;
$overviewactivitiesdata->is_courses_creator = has_capability('moodle/course:create', context_system::instance());

// Complements : get all visible complement courses for current user.
$overviewcomplementsdata = new stdClass();
$overviewcomplementsdata->complements = array_values(apsolu\get_potential_user_complements());
$overviewcomplementsdata->count_complements = count($overviewcomplementsdata->complements);
$overviewcomplementsdata->www_url = $CFG->wwwroot;
$overviewcomplementsdata->is_courses_creator = has_capability('moodle/course:create', context_system::instance());

// CSS.
$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/enrol/select/styles/select2.min.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/enrol/select/styles/ol.css'));

// Javascript.
$PAGE->requires->js_call_amd('enrol_select/select_mapping', 'initialise');
$PAGE->requires->js_call_amd('enrol_select/select_overview', 'initialise');

// Navigation.
$PAGE->navbar->add(get_string('enrolment', 'enrol_select'));

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('enrol_select/overview_complements', $overviewcomplementsdata);
echo $OUTPUT->render_from_template('enrol_select/overview_activities', $overviewactivitiesdata);

echo $OUTPUT->footer();
