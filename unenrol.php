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
require_once($CFG->dirroot.'/enrol/select/locallib.php');

$enrolid = required_param('enrolid', PARAM_INT);

$instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'select'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$canunenrol = has_capability('enrol/select:unenrol', $context);

// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.

if (!$canunenrol) {
    // No need to invent new error strings here...
    require_capability('enrol/select:unenrol', $context);
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
$PAGE->navbar->add($instancename);

if (isset($_POST['removeselect'])) {
    $enrolselectplugin = new enrol_select_plugin();

    $count = 0;
    foreach ($_POST['removeselect'] as $userid) {
        $enrolselectplugin->unenrol_user($instance, $userid);
        $count++;
    }

    if ($count > 1) {
        $notification = $OUTPUT->notification($count.' utilisateurs désinscrits.', 'notifysuccess');
    } else {
        $notification = $OUTPUT->notification($count.' utilisateur désinscrit.', 'notifysuccess');
    }
}

// Create the user selector objects.
$options = array('enrolid' => $enrolid, 'accesscontext' => $context);
$currentuserselector = new enrol_manual_current_participant('removeselect', $options);
ob_start();
$currentuserselector->display();
$userselector = ob_get_contents();
ob_end_clean();

echo $OUTPUT->header();
echo $OUTPUT->heading('Gestion des voeux');

if (isset($notification)) {
    echo $notification;
}

$enroldata = new stdClass();
$enroldata->action = $CFG->wwwroot.'/enrol/select/unenrol.php?enrolid='.$enrolid;
$enroldata->current_users_selector = $userselector;
$enroldata->cancel = new moodle_url('/enrol/select/manage.php', array('enrolid' => $instance->id));

echo $OUTPUT->render_from_template('enrol_select/manage_unenrol', $enroldata);

echo $OUTPUT->footer();
