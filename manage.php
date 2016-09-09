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

use UniversiteRennes2\Apsolu as apsolu;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$enrolid = required_param('enrolid', PARAM_INT);

$instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'select'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
$canenrol = has_capability('enrol/select:enrol', $context);
$canunenrol = has_capability('enrol/select:unenrol', $context);

// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.

if (!$canenrol and !$canunenrol) {
    // No need to invent new error strings here...
    require_capability('enrol/select:enrol', $context);
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

$roles = role_fix_names($DB->get_records('role'));

$sql = 'SELECT u.*, ra.roleid, ue.timecreated'.
    ' FROM {user} u'.
    ' JOIN {user_enrolments} ue ON u.id = ue.userid'.
    ' JOIN {role_assignments} ra ON u.id = ra.userid'.
    ' JOIN {context} ctx ON ctx.id = ra.contextid'.
    ' WHERE ue.enrolid = :enrolid'.
    ' AND ue.status = :status'.
    ' AND ctx.instanceid = :courseid'.
    ' AND ctx.contextlevel = 50'.
    ' ORDER BY u.lastname, u.firstname, ue.timecreated';

$options = array();
foreach (enrol_select_plugin::$states as $code => $state) {
    $options[$code] = get_string('move_to_'.$state, 'enrol_select');
}
$options['notify'] = get_string('notify', 'enrol_select');
$options['editenroltype'] = get_string('editenroltype', 'enrol_select');
$options['changecourse'] = get_string('change_course', 'enrol_select');

$enrollinks = new stdClass();
$enrollinks->wwwroot = $CFG->wwwroot;
$enrollinks->enrolid = $enrolid;
$enrollinks->enrol_user_link = $CFG->wwwroot.'/enrol/select/enrol.php?enrolid='.$enrolid;
$enrollinks->unenrol_user_link = $CFG->wwwroot.'/enrol/select/unenrol.php?enrolid='.$enrolid;

$lists = array();
foreach (enrol_select_plugin::$states as $code => $state) {
    $selectoptions = $options;
    unset($selectoptions[$code]);

    $data = new stdClass();
    $data->name = get_string($state.'_list', 'enrol_select');
    $data->description = get_string($state.'_description', 'enrol_select');
    $data->roles = $roles;
    $data->status = $code;
    $data->form_action = $CFG->wwwroot.'/enrol/select/manage_handler.php?enrolid='.$enrolid;
    $data->enrol_user_link = $CFG->wwwroot.'/enrol/select/add.php?enrolid='.$enrolid.'&status='.$code;
    $data->users = array();

    $data->count_users = 0;
    foreach ($DB->get_recordset_sql($sql, array('enrolid' => $enrolid, 'status' => $code, 'courseid' => $course->id)) as $user) {
        if (!isset($roles[$user->roleid])) {
            continue;
        }

        if (isset($data->users[$user->id])) {
            $data->users[$user->id]->role .= ', '.$roles[$user->roleid]->localname;
        } else {
            $user->picture = $OUTPUT->user_picture($user, array('size' => 30, 'courseid' => $course->id));
            $user->role = $roles[$user->roleid]->localname;
            $user->timecreated = strftime('%a %d %b à %T', $user->timecreated);

            $data->users[$user->id] = $user;
            $data->count_users++;
        }
    }
    $data->users = array_values($data->users);

    foreach ($data->users as $user) {
        $enrolments = apsolu\get_user_activity_enrolments($user->id);

        $user->enrolments = array();
        $user->count_enrolments = 0;
        foreach ($enrolments as $enrolment) {
            $enrolment->state = get_string(enrol_select_plugin::$states[$enrolment->status].'_list', 'enrol_select');
            $enrolment->role = $roles[$enrolment->roleid]->localname;
            $user->enrolments[] = $enrolment;
            $user->count_enrolments++;
        }
    }

    $htmlselectattributes = array('id' => 'to-'.$state, 'class' => 'select_options');
    $data->actions = '<p>'.html_writer::tag('label', get_string("withselectedusers"), array('for' => 'to-'.$state)).
        html_writer::select($selectoptions, 'actions', '', array('' => 'choosedots'), $htmlselectattributes).
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'from', 'value' => $code)).
        '</p>';

    $lists[] = $data;
}

$pluginname = get_string('pluginname', 'enrol_select');

$PAGE->navbar->add(get_string('users'));
$PAGE->navbar->add(get_string('enrolmentinstances', 'enrol'), new moodle_url('/enrol/instances.php', array('id' => $course->id)));
$PAGE->navbar->add($pluginname);

$PAGE->requires->js_call_amd('enrol_select/select_manage_user_selection', 'initialise');

echo $OUTPUT->header();
echo $OUTPUT->heading('Gestion des voeux');

if (isset($notification)) {
    echo $notification;
}

$enrollinks->lists = $lists;

echo $OUTPUT->render_from_template('enrol_select/manage', $enrollinks);

echo $OUTPUT->footer();
