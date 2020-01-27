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

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/enrol/select/locallib.php');
require_once($CFG->dirroot.'/enrol/select/manage_move_form.php');
require_once($CFG->dirroot.'/local/apsolu/locallib.php');

// managing semester 2 move
$previousenrolid = false;

$enrolid = required_param('enrolid', PARAM_INT);
$from = required_param('from', PARAM_INT);
$to = required_param('actions', PARAM_INT);
if (!isset($_POST['users'])) {
    $_POST['users'] = array();
}

$instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'select'), '*', MUST_EXIST);

// case of semester 2 enrolment + move
if (strpos($to, '99') === 0 && $instance->customint6 !== null) {
    $previousenrolid = $enrolid;
    $enrolid         = $instance->customint6;
    $instance        = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'select'), '*', MUST_EXIST);
    $to              = str_replace('99', '', $to);
}

if (isset($_POST['previousenrolid']) && $_POST['previousenrolid'] > 0) {
    $previousenrolid  = $_POST['previousenrolid'];
    $previousinstance = $DB->get_record('enrol', array('id' => $previousenrolid, 'enrol' => 'select'), '*', MUST_EXIST);
}

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

$url = new moodle_url('/enrol/select/manage_move.php', array('enrolid' => $instance->id, 'from' => $from, 'to' => $to));

$PAGE->set_url($url->out());
$PAGE->set_pagelayout('base');
$PAGE->set_title($enrolselect->get_instance_name($instance));
$PAGE->set_heading($course->fullname);

$usersenrolid = ($previousenrolid) ? $previousenrolid : $enrolid;

// Get users list.
$sql = "SELECT u.*".
    " FROM {user} u".
    " JOIN {user_enrolments} ue ON u.id = ue.userid".
    " WHERE ue.enrolid = ?";
$users = $DB->get_records_sql($sql, array($usersenrolid));
foreach ($users as $userid => $user) {
    $index = array_search((string)$userid, $_POST['users'], true);

    if ($index === false) {
        unset($users[$userid]);
    }
}

$mform = new enrol_select_manage_move_form($url->out(false), array($instance, $users, $from, $to, $previousenrolid));

if ($mform->is_cancelled()) {
    redirect($return);

} else if ($data = $mform->get_data()) {
    if (isset(enrol_select_plugin::$states[$to])) {
        foreach ($data->users as $userid) {
            if ($previousenrolid === false) {
                $sql = "UPDATE {user_enrolments} SET status=? WHERE userid=? AND enrolid=?";
                $DB->execute($sql, array($to, $userid, $enrolid));
            }

            if ($previousenrolid && $previousinstance) {
                $enrolselectplugin = new enrol_select_plugin();
                $roleid = 0;

                $sql = "SELECT roleid FROM {role_assignments} WHERE component = 'enrol_select' AND itemid = :previousinstance_id AND userid = :userid";
                foreach ($DB->get_recordset_sql($sql, array('previousinstance_id' => $previousenrolid, 'userid' => $userid)) as $role) {
                    $roleid = $role->roleid;
                }

                $enrolselectplugin->enrol_user($instance, $userid, $roleid, 0, 0, $to, null, null);
            }

            $event = \enrol_select\event\user_moved::create(array(
                'relateduserid' => $userid,
                'other' => array('status' => $to),
                'context' => $context
            ));
            $event->trigger();

            if ($data->notify == 1 && !empty($data->message)) {
                $eventdata = new \core\message\message();
                $eventdata->courseid = $course->id;
                $eventdata->component = 'enrol_select';
                $eventdata->name = 'select_notification';
                $eventdata->userfrom = $USER;
                $eventdata->userto = $DB->get_record('user', array('id' => $userid));
                $eventdata->subject = get_string('enrolcoursesubject', 'enrol_select', $course);
                $eventdata->fullmessage = $data->message;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml = '';
                $eventdata->smallmessage = '';
                $eventdata->notification = 1;

                message_send($eventdata);
            }
        }

        $url = $CFG->wwwroot.'/enrol/select/manage.php?enrolid='.$enrolid;
        redirect($url, 'Le ou les utilisateurs ont été correctement déplacés.', 5, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$pluginname = get_string('pluginname', 'enrol_select');

$PAGE->navbar->add(get_string('users'));
$PAGE->navbar->add(get_string('enrolmentinstances', 'enrol'), new moodle_url('/enrol/instances.php', array('id' => $enrolid)));
$PAGE->navbar->add($pluginname, new moodle_url('/enrol/select/manage.php', array('enrolid' => $enrolid)));
$PAGE->navbar->add(get_string('move_to', 'enrol_select'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_select'));
$mform->display();
echo $OUTPUT->footer();
