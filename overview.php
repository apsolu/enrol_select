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
 * Page pour afficher la vue d'ensemble du module enrol_select.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\federation\course as FederationCourse;

require('../../config.php');
require_once(__DIR__.'/locallib.php');
require_once($CFG->dirroot.'/enrol/select/blocklib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$roleid = optional_param('roleid', 0, PARAM_INT);

require_login();

$context = context_user::instance($USER->id);

$PAGE->set_url('/enrol/select/overview.php');
$PAGE->set_pagelayout('admin');

$PAGE->set_context($context);

$PAGE->set_heading(get_string('overviewtitle', 'enrol_select'));
$PAGE->set_title(get_string('pluginname', 'enrol_select'));

$select = enrol_get_plugin('select');

$capabilities = array(
    'moodle/category:manage',
    'moodle/course:create',
);

$time = null;
$cohorts = null;
$managersfilters = '';
if (has_any_capability($capabilities, context_system::instance()) === true) {
    require_once(__DIR__.'/overview_managers_filters_form.php');

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
ob_start();
$courses = enrol_select_get_potential_user_activities($time, $cohorts);
$debugging = ob_get_clean();

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

$overviewactivitiesdata->roles = array_values(enrol_select_get_custom_student_roles());
$overviewactivitiesdata->info_pix_url = $OUTPUT->image_url('i/info');
$overviewactivitiesdata->marker_pix_url = $OUTPUT->image_url('a/marker', 'enrol_select');
$overviewactivitiesdata->www_url = $CFG->wwwroot;
$overviewactivitiesdata->is_courses_creator = has_capability('moodle/course:create', context_system::instance());
$overviewactivitiesdata->filters = '';
if (isset($time, $cohorts) === true) {
    $overviewactivitiesdata->filters = '&time='.$time.'&cohorts='.implode(',', $cohorts);
}

// Complements : get all visible complement courses for current user.
$overviewcomplementsdata = new stdClass();
$overviewcomplementsdata->complements = array_values(enrol_select_get_potential_user_complements());
$overviewcomplementsdata->count_complements = count($overviewcomplementsdata->complements);
$overviewcomplementsdata->www_url = $CFG->wwwroot;
$overviewcomplementsdata->is_siuaps_rennes = isset($CFG->is_siuaps_rennes);
$overviewcomplementsdata->is_courses_creator = has_capability('moodle/course:create', context_system::instance());

// Set remaining choices block.
$PAGE->blocks->add_fake_block(enrol_select_get_remaining_choices_block(), BLOCK_POS_LEFT);

// Set enrolments block.
$PAGE->blocks->add_fake_block(enrol_select_get_enrolments_block(), BLOCK_POS_LEFT);

// Set filters block.
$filters = enrol_select_get_filters_block($courses);
$overviewactivitiesdata->more_than_one_site = $filters->more_than_one_site;
$PAGE->blocks->add_fake_block($filters, BLOCK_POS_LEFT);

// CSS.
$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/enrol/select/styles/select2.min.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/enrol/select/styles/ol.css'));

// Javascript.
$PAGE->requires->js_call_amd('enrol_select/select_mapping', 'initialise');
$PAGE->requires->js_call_amd('enrol_select/select_filter', 'initialise');
$PAGE->requires->js_call_amd('enrol_select/select_overview', 'initialise');
$PAGE->requires->js_call_amd('enrol_select/select_enrol', 'initialise', array('url' => $CFG->wwwroot));

// Navigation.
$PAGE->navbar->add(get_string('enrolment', 'enrol_select'));

echo $OUTPUT->header();
echo $managersfilters;
echo $OUTPUT->render_from_template('enrol_select/overview_complements', $overviewcomplementsdata);
echo $OUTPUT->render_from_template('enrol_select/overview_activities', $overviewactivitiesdata);
echo $debugging;
echo $OUTPUT->footer();
