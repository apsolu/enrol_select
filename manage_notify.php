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
 * Page gérant les notifications.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/enrol/select/locallib.php');
require_once($CFG->dirroot . '/local/apsolu/forms/notification_form.php');

$enrolid = required_param('enrolid', PARAM_INT);
if (!isset($_POST['users'])) {
    $_POST['users'] = [];
}

$instance = $DB->get_record('enrol', ['id' => $enrolid, 'enrol' => 'select'], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course, $autologinguest = false);
$canenrol = has_capability('enrol/select:enrol', $context);
$canunenrol = has_capability('enrol/select:unenrol', $context);

// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.

if (!$canenrol && !$canunenrol) {
    // No need to invent new error strings here...
    require_capability('enrol/select:enrol', $context);
    require_capability('enrol/select:unenrol', $context);
}

if (!$enrolselect = enrol_get_plugin('select')) {
    throw new coding_exception('Can not instantiate enrol_select');
}

$instancename = $enrolselect->get_instance_name($instance);

$url = new moodle_url('/enrol/select/manage_notify.php', ['enrolid' => $instance->id]);

$PAGE->set_url($url->out());
$PAGE->set_pagelayout('base');
$PAGE->set_title($enrolselect->get_instance_name($instance));
$PAGE->set_heading($course->fullname);

// Get users list.
$sql = "SELECT u.*" .
    " FROM {user} u" .
    " JOIN {user_enrolments} ue ON u.id = ue.userid" .
    " WHERE ue.enrolid = ?";
$users = $DB->get_records_sql($sql, [$enrolid]);
foreach ($users as $userid => $user) {
    $index = array_search((string)$userid, $_POST['users'], true);

    if ($index === false) {
        unset($users[$userid]);
    }
}

$actionurl = new moodle_url('/enrol/select/manage_handler.php', ['actions' => $actions, 'enrolid' => $enrolid]);
$redirecturl = new moodle_url('/enrol/select/manage.php', ['enrolid' => $enrolid]);

$customdata = [];
$customdata[] = (object) ['subject' => get_string('enrolcoursesubject', 'enrol_select', $course)];
$customdata[] = $users;
$customdata[] = $redirecturl;
$mform = new local_apsolu_notification_form($actionurl, $customdata);

if ($mform->is_cancelled()) {
    redirect($return);
} else if ($data = $mform->get_data()) {
    $mform->local_apsolu_notify($data->users, $course->id);

    $message = get_string('notifications_have_been_sent', 'local_apsolu');
    redirect($redirecturl, $message, 5, \core\output\notification::NOTIFY_SUCCESS);
}

$pluginname = get_string('pluginname', 'enrol_select');

$PAGE->navbar->add(get_string('users'));
$PAGE->navbar->add(get_string('enrolmentinstances', 'enrol'), new moodle_url('/enrol/instances.php', ['id' => $enrolid]));
$PAGE->navbar->add($pluginname, new moodle_url('/enrol/select/manage.php', ['enrolid' => $enrolid]));
$PAGE->navbar->add(get_string('notifications'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_select'));
$mform->display();
echo $OUTPUT->footer();
