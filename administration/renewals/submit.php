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
 * Page gérant la validation des renouvèlements de voeux.
 *
 * @package    enrol_select
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!$enrolselect = enrol_get_plugin('select')) {
    throw new coding_exception('Can not instantiate enrol_select');
}

$enrolments = $DB->get_records('enrol', ['enrol' => 'select']);

foreach ($_POST['enrols'] as $enrolid) {
    if (isset($enrolments[$enrolid]) === false) {
        continue;
    }

    $instance = $enrolments[$enrolid];
    $nextenrolid = $instance->customint6;

    if (isset($enrolments[$nextenrolid]) === false) {
        continue;
    }
    $nextinstance = $enrolments[$nextenrolid];

    $context = context_course::instance($instance->courseid, MUST_EXIST);
    $canenrol = has_capability('enrol/select:enrol', $context);
    $canunenrol = has_capability('enrol/select:unenrol', $context);

    if (!$canenrol && !$canunenrol) {
        // No need to invent new error strings here...
        require_capability('enrol/select:enrol', $context);
        require_capability('enrol/select:unenrol', $context);
    }

    // Get users list.
    foreach ($DB->get_records('user_enrolments', ['enrolid' => $enrolid]) as $userenrolment) {
        if (isset($_POST['targetlist'][$userenrolment->status]) === false) {
            continue;
        }

        if ($_POST['targetlist'][$userenrolment->status] === '-1') {
            continue;
        }

        $userid = $userenrolment->userid;
        $enrolselectplugin = new enrol_select_plugin();

        $roleassignment = $DB->get_record('role_assignments', ['component' => 'enrol_select', 'itemid' => $enrolid, 'userid' =>
            $userid]);
        if ($roleassignment === false) {
            continue;
        }

        $roleid = $roleassignment->roleid;
        $timestart = 0;
        $timeend = 0;
        $status = $_POST['targetlist'][$userenrolment->status];
        $recovergrades = null;
        $enrolselectplugin->enrol_user($nextinstance, $userid, $roleid, $timestart, $timeend, $status, $recovergrades);
    }
}

echo $OUTPUT->notification('Le ou les utilisateurs ont été correctement réinscrits.', 'notifysuccess');
