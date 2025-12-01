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
 * Page gérant les changements des cours.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/enrol/select/locallib.php');
require_once($CFG->dirroot . '/enrol/select/manage_change_course_form.php');

$enrolid = required_param('enrolid', PARAM_INT);
$from = required_param('from', PARAM_INT);
$to = required_param('actions', PARAM_INT);
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

$url = new moodle_url('/enrol/select/manage_change_course.php', ['enrolid' => $instance->id, 'from' => $from, 'to' => $to]);

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

// Cherche les cours contenant une méthode 'select' et où l'utilisateur courant est enseignant...
// Ne pas faire de jointure sur ra.itemid et e.id.
$sql = "SELECT e.id, e.name, c.id AS courseid, c.fullname" .
    " FROM {course} c" .
    " JOIN {apsolu_courses} apc ON apc.id = c.id" .
    " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50" .
    " JOIN {role_assignments} ra ON ctx.id = ra.contextid AND ra.roleid = 3" .
    " JOIN {enrol} e ON c.id = e.courseid AND e.status = 0 AND e.enrol = 'select'" .
    " WHERE ra.userid = ?" .
    " ORDER BY c.fullname";
$courses = [];
foreach ($DB->get_records_sql($sql, [$USER->id]) as $courseid => $enrolcourse) {
    if ($course->id == $enrolcourse->courseid) {
        continue;
    }

    if (!empty($enrolcourse->name)) {
        $courses[$courseid] = $enrolcourse->fullname . ' (' . $enrolcourse->name . ')';
    } else {
        $courses[$courseid] = $enrolcourse->fullname;
    }
}

if ($courses === []) {
    $url = $CFG->wwwroot . '/enrol/select/manage.php?enrolid=' . $enrolid;
    $message = 'Vous n\'enseignez que dans un seul cours. Vous ne pouvez pas utiliser cette fonction.';
    redirect($url, $message, 5, \core\output\notification::NOTIFY_ERROR);
}

$mform = new enrol_select_manage_change_course_form($url->out(false), [$instance, $users, $from, $to, $courses]);

if ($mform->is_cancelled()) {
    redirect($return);
} else if ($data = $mform->get_data()) {
    if ($data->courseid == $course->id) {
        $url = $CFG->wwwroot . '/enrol/select/manage.php?enrolid=' . $enrolid;
        redirect($url, 'Impossible de déplacer dans le même cours', 5, \core\output\notification::NOTIFY_ERROR);
    } else if (isset($courses[$data->courseid])) {
        $newinstance = $DB->get_record('enrol', ['id' => $data->courseid, 'enrol' => 'select'], '*', MUST_EXIST);

        $badmoves = 0;
        $goodmoves = 0;
        foreach ($data->users as $userid) {
            $newenrol = $DB->get_record('user_enrolments', ['enrolid' => $newinstance->id, 'userid' => $userid]);
            if ($newenrol) {
                $badmoves++;
                continue;
            }

            $currentenrol = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid]);
            $coursecontext = context_course::instance($instance->courseid);
            $sql = "UPDATE {role_assignments} SET roleid = ? WHERE component = 'enrol_select' AND userid = ? AND contextid = ?";
            $params = ['component' => 'enrol_select', 'userid' => $userid, 'contextid' => $coursecontext->id];
            $roleassignment = $DB->get_record('role_assignments', $params);
            if ($roleassignment) {
                $enrolselect->unenrol_user($instance, $userid);
                $enrolselect->enrol_user(
                    $newinstance,
                    $userid,
                    $roleassignment->roleid,
                    $timestart = 0,
                    $timeend = 0,
                    $currentenrol->status,
                    $recovergrades = null
                );
                $goodmoves++;
            } else {
                $badmoves++;
            }
        }

        $notification = [];
        if ($goodmoves === 1) {
            $notification[] = '1 utilisateur a été déplacé';
        } else if ($goodmoves > 1) {
            $notification[] = $goodmoves . ' utilisateurs ont été déplacés';
        }

        if ($badmoves === 1) {
            $notification[] = '1 utilisateur n\'a pas pu être déplacé (soit il est déjà inscrit au cours,' .
                ' soit une erreur est survenue)';
        } else if ($badmoves > 1) {
            $notification[] = $badmoves . ' utilisateurs n\'ont pas pu être déplacés (soit ils sont déjà inscrits au cours,' .
                ' soit une erreur est survenue)';
        }

        $url = $CFG->wwwroot . '/enrol/select/manage.php?enrolid=' . $enrolid;
        redirect($url, implode(' et ', $notification) . '.', 5, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        $url = $CFG->wwwroot . '/enrol/select/manage.php?enrolid=' . $enrolid;
        $message = 'Ce cours ne semble pas être valide pour cette méthode d\'inscription';
        redirect($url, $message, 5, \core\output\notification::NOTIFY_ERROR);
    }
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
