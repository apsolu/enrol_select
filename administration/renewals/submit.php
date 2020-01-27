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
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

foreach($_POST['uids'] as $uid) {
    if (isset($_POST[$uid.'_enrol'])) {
        $to = $_POST[$uid.'_action'];

        $enrolid = $_POST[$uid.'_enrol'];
        $instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'select'), '*', MUST_EXIST);

        $nextenrolid     = $instance->customint6;
        $nextinstance    = $DB->get_record('enrol', array('id' => $nextenrolid, 'enrol' => 'select'), '*', MUST_EXIST);

        $course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
        $context = context_course::instance($course->id, MUST_EXIST);
        $canenrol = has_capability('enrol/select:enrol', $context);
        $canunenrol = has_capability('enrol/select:unenrol', $context);

        if (!$canenrol and !$canunenrol) {
            // No need to invent new error strings here...
            require_capability('enrol/select:enrol', $context);
            require_capability('enrol/select:unenrol', $context);
        }

        if (!$enrolselect = enrol_get_plugin('select')) {
            throw new coding_exception('Can not instantiate enrol_select');
        }

        // Get users list.
        $sql = "SELECT u.*".
        " FROM {user} u".
        " JOIN {user_enrolments} ue ON u.id = ue.userid".
        " WHERE ue.enrolid = ?";
        $users = $DB->get_records_sql($sql, array($enrolid));
        foreach ($users as $userid => $user) {
            $enrolselectplugin = new enrol_select_plugin();
            $roleid = 0;

            $sql = "SELECT roleid FROM {role_assignments} WHERE component = 'enrol_select' AND itemid = :previousinstance_id AND userid = :userid";
            foreach ($DB->get_recordset_sql($sql, array('previousinstance_id' => $enrolid, 'userid' => $userid)) as $role) {
                $roleid = $role->roleid;
            }

            $enrolselectplugin->enrol_user($nextinstance, $userid, $roleid, 0, 0, $to, null, null);
        }
    }
}

$url = $CFG->wwwroot.'/enrol/select/administration.php?tab=renewals';
redirect($url, 'Le ou les utilisateurs ont été correctement réinscrits.', 5, \core\output\notification::NOTIFY_SUCCESS);
