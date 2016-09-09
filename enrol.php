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
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/enrol/manual/locallib.php');

$enrolid = required_param('enrolid', PARAM_INT);

$instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'select'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$canenrol = has_capability('enrol/select:enrol', $context);

// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.

if (!$canenrol) {
    // No need to invent new error strings here...
    require_capability('enrol/select:enrol', $context);
}

if (!$enrolselect = enrol_get_plugin('select')) {
    throw new coding_exception('Can not instantiate enrol_select');
}

$instancename = $enrolselect->get_instance_name($instance);

$PAGE->set_url('/enrol/select/manage.php', array('enrolid' => $instance->id));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($enrolselect->get_instance_name($instance));
$PAGE->set_heading($course->fullname);

$pluginname = get_string('pluginname', 'enrol_select');

$PAGE->navbar->add(get_string('users'));
$PAGE->navbar->add(get_string('enrolmentinstances', 'enrol'), new moodle_url('/enrol/instances.php', array('id' => $course->id)));
$PAGE->navbar->add($pluginname, new moodle_url('/enrol/select/manage.php', array('enrolid' => $instance->id)));

if (isset($_POST['role'], $_POST['status'], $_POST['addselect']) && ctype_digit($_POST['role']) && ctype_digit($_POST['status'])) {
    $enrolselectplugin = new enrol_select_plugin();

    $count = 0;
    foreach ($_POST['addselect'] as $userid) {
        $timestart = time();
        $timeend = 0;
        $recovergrades = null;

        $enrolselectplugin->enrol_user($instance, $userid, $_POST['role'], $timestart, $timeend, $_POST['status'], $recovergrades);
        $count++;
    }

    if ($count > 1) {
        $notification = $OUTPUT->notification($count.' utilisateurs inscrits.', 'notifysuccess');
    } else {
        $notification = $OUTPUT->notification($count.' utilisateur inscrit.', 'notifysuccess');
    }
}

// Create the user selector objects.
$options = array('enrolid' => $enrolid, 'accesscontext' => $context);
$potentialuserselector = new enrol_manual_potential_participant('addselect', $options);
ob_start();
$potentialuserselector->display();
$userselector = ob_get_contents();
ob_end_clean();


echo $OUTPUT->header();
echo $OUTPUT->heading('Gestion des voeux');

if (isset($notification)) {
    echo $notification;
}

$enroldata = new stdClass();
$enroldata->action = $CFG->wwwroot.'/enrol/select/enrol.php?enrolid='.$enrolid;
$enroldata->roles = $enrolselect->get_roles($instance, $context);

if ($enroldata->roles === array()) {
    print_error('error_no_role', 'enrol_select', $CFG->wwwroot.'/enrol/select/manage.php?enrolid='.$instance->id);
}

$enroldata->status = array();
$enroldata->status[] = (object) array('id' => 0, 'name' => get_string('accepted_list', 'enrol_select'));
$enroldata->status[] = (object) array('id' => 2, 'name' => get_string('main_list', 'enrol_select'));
$enroldata->status[] = (object) array('id' => 3, 'name' => get_string('wait_list', 'enrol_select'));
$enroldata->potential_users_selector = $userselector;

echo $OUTPUT->render_from_template('enrol_select/manage_enrol', $enroldata);

echo $OUTPUT->footer();
