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
 * Page pour exporter les inscriptions.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\customfields;
use UniversiteRennes2\Apsolu;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/excellib.class.php');

// TODO: remplacer enrolid par courseid dans les paramètres de l'URL (bloc teachers et page manage).
$enrolid = required_param('enrolid', PARAM_INT);
$exportformat = optional_param('format', 'xls', PARAM_ALPHA);
$exportstatus = optional_param('status', null, PARAM_INT);

$instance = $DB->get_record('enrol', ['id' => $enrolid, 'enrol' => 'select'], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
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

$roles = role_fix_names($DB->get_records('role'));

$time = time();
$params = ['courseid' => $course->id, 'enrolid' => $instance->id];
if (isset($exportstatus)) {
    $conditions = ' AND ue.status = :status';
    $params['status'] = $exportstatus;
} else {
    $conditions = '';
}

$sql = 'SELECT DISTINCT u.*, ra.roleid, ue.timecreated, ue.status' .
    ' FROM {user} u' .
    ' JOIN {user_enrolments} ue ON u.id = ue.userid' .
    ' JOIN {role_assignments} ra ON u.id = ra.userid AND ue.enrolid = ra.itemid' .
    ' JOIN {role} r ON r.id = ra.roleid AND r.archetype = "student"' .
    ' JOIN {context} ctx ON ctx.id = ra.contextid' .
    ' JOIN {enrol} e ON e.id = ra.itemid AND e.id = ue.enrolid AND ctx.instanceid = e.courseid' .
    ' WHERE e.id = :enrolid' .
    ' AND e.enrol = "select"' .
    ' AND ctx.instanceid = :courseid' .
    ' AND ctx.contextlevel = 50' . $conditions .
    ' ORDER BY ue.status, ue.timecreated, u.lastname, u.firstname, u.institution, u.department';
$users = $DB->get_records_sql($sql, $params);

// Génération du fichier csv.
$instancename = get_string('pluginname', 'enrol_select');
if (empty($instance->name) === false) {
    $instancename = $instance->name;
}
$filename = clean_filename($course->fullname . '-' . $instancename);

$headers = [];
$headers['lastname'] = get_string('lastname');
$headers['firstname'] = get_string('firstname');
$headers['idnumber'] = get_string('idnumber');

// Récupère les champs additionnels pour l'exportation.
$extrafields = customfields::get_extra_fields('export');
foreach ($extrafields as $fieldname => $label) {
    $headers[$fieldname] = $label;
}

if (isset($exportstatus) === false) {
    $headers['list'] = get_string('list', 'enrol_select');
}

$headers['registertype'] = get_string('register_type', 'enrol_select');
$headers['registerdate'] = get_string('register_date', 'enrol_select');
$headers['empty1'] = 'texte libre';
$headers['empty2'] = 'texte libre';
$headers['empty3'] = 'texte libre';

$rows = [];
foreach ($users as $user) {
    $customfields = profile_user_record($user->id);

    $user->list = enrol_select_plugin::get_enrolment_list_name($user->status);
    $user->registertype = $roles[$user->roleid]->localname;
    $user->registerdate = userdate($user->timecreated);

    $row = [];
    foreach ($headers as $fieldname => $unused) {
        if (isset($user->$fieldname) === true) {
            $row[] = $user->$fieldname;
        } else if (isset($customfields->$fieldname) === true) {
            $row[] = $customfields->$fieldname;
        } else {
            $row[] = '';
        }
    }

    $rows[] = $row;
}

switch ($exportformat) {
    case 'xls':
        // Creating a workbook.
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers.
        $workbook->send($filename);
        // Adding the worksheet.
        $myxls = $workbook->add_worksheet();

        $excelformat = new MoodleExcelFormat(['border' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]);

        // Set headers.
        $i = 0;
        foreach ($headers as $value) {
            $myxls->write_string(0, $i, $value, $excelformat);
            $i++;
        }

        // Set data.
        foreach ($rows as $line => $row) {
            $line++;
            foreach ($row as $column => $value) {
                $myxls->write_string($line, $column, $value, $excelformat);
            }
        }
        $workbook->close();
        break;
    case 'csv':
    default:
        $csvexport = new \csv_export_writer();
        $csvexport->set_filename($filename);
        $csvexport->add_data($headers);
        foreach ($rows as $row) {
            $csvexport->add_data($row);
        }

        $csvexport->download_file();
}

exit(0);
