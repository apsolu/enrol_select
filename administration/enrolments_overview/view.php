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
 * Page permettant de lister les inscriptions.
 *
 * @package   enrol_select
 * @copyright 2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\customfields;

defined('MOODLE_INTERNAL') || die;

// Fichier chargé automatiquement pour les administrateurs, mais pas pour les gestionnaires visiblement.
require_once($CFG->dirroot . '/enrol/select/lib.php');
require_once($CFG->dirroot . '/enrol/select/locallib.php');
require_once($CFG->dirroot . '/enrol/select/administration/enrolments_overview/view_filter_form.php');

// Liste des populations.
$colleges = [];
foreach ($DB->get_records('apsolu_colleges', null, $sort = 'name') as $college) {
    $colleges[$college->id] = $college->name;
}

// Liste les calendriers APSOLU.
$calendars = ['' => ''];
foreach ($DB->get_records('apsolu_calendars', null, 'name') as $calendar) {
    $calendars[$calendar->id] = $calendar->name;
}

$rows = [];

// Traite le formulaire.
$mform = new apsolu_enrolments_overview_filter_form(null, [$colleges, $calendars]);
if ($mdata = $mform->get_data()) {
    $college = $DB->get_record('apsolu_colleges', ['id' => $mdata->college], '*', MUST_EXIST);
    $fields = CustomFields::getCustomFields();
    $users = [];

    // Récupère tous les utilisateurs qui ne devraient pas avoir le rôle défini par la population sélectionnée.
    // Exemple: un personnel ne devrait pas être évalué.
    $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.idnumber, u.department, uid.data AS ufr
              FROM {user} u
         LEFT JOIN {user_info_data} uid ON u.id = uid.userid AND uid.fieldid = :ufrfieldid
              JOIN {role_assignments} ra ON u.id = ra.userid AND ra.component = 'enrol_select'
              JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
              JOIN {course} c ON c.id = ctx.instanceid
              JOIN {apsolu_courses} ac ON ac.id = c.id
              JOIN {enrol} e ON c.id = e.courseid AND ra.itemid = e.id
              JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = ra.userid AND u.id = ue.userid
             WHERE e.enrol = 'select'
               AND e.status = 0 -- Active.
               AND ue.status = :acceptedstatus
               AND ra.roleid = :roleid1
               AND c.visible = 1
               AND u.deleted = 0
               AND u.id NOT IN (SELECT cm.userid
                                  FROM {cohort_members} cm
                                  JOIN {cohort} c ON c.id = cm.cohortid
                                  JOIN {apsolu_colleges_members} acm ON cm.cohortid = acm.cohortid
                                  JOIN {apsolu_colleges} ac ON ac.id = acm.collegeid
                                 WHERE ac.roleid = :roleid2)
          ORDER BY u.lastname, u.firstname";
    $recordset = $DB->get_recordset_sql($sql, ['ufrfieldid' => $fields['apsoluufr']->id,
        'acceptedstatus' => enrol_select_plugin::ACCEPTED, 'roleid1' => $college->roleid, 'roleid2' => $college->roleid]);
    foreach ($recordset as $record) {
        $users[$record->id] = clone $record;
        $users[$record->id]->cohorts = [];
    }
    $recordset->close();

    // Récupère tous les utilisateurs appartenant à la population sélectionnée.
    $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.idnumber, u.department, uid.data AS ufr, c.name AS cohort
              FROM {user} u
         LEFT JOIN {user_info_data} uid ON u.id = uid.userid AND uid.fieldid = :ufrfieldid
              JOIN {cohort_members} cm ON u.id = cm.userid
              JOIN {cohort} c ON c.id = cm.cohortid
              JOIN {apsolu_colleges_members} acm ON cm.cohortid = acm.cohortid
              JOIN {apsolu_colleges} ac ON ac.id = acm.collegeid
             WHERE ac.id = :collegeid
          ORDER BY u.lastname, u.firstname, c.name";

    $recordset = $DB->get_recordset_sql($sql, ['collegeid' => $college->id, 'ufrfieldid' => $fields['apsoluufr']->id]);
    foreach ($recordset as $record) {
        if (isset($users[$record->id]) === false) {
            $users[$record->id] = clone $record;
            $users[$record->id]->cohorts = [];
        }

        $users[$record->id]->cohorts[] = $record->cohort;
    }
    $recordset->close();

    // Récupère tous les inscriptions en fonction du rôle utilisé par la population.
    $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.idnumber,
                   e.customchar1 AS calendar, ra.roleid, c.id AS courseid, c.fullname
              FROM {user} u
              JOIN {role_assignments} ra ON u.id = ra.userid AND ra.component = 'enrol_select'
              JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
              JOIN {course} c ON c.id = ctx.instanceid
              JOIN {apsolu_courses} ac ON ac.id = c.id
              JOIN {enrol} e ON c.id = e.courseid AND ra.itemid = e.id
              JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = ra.userid AND u.id = ue.userid
         LEFT JOIN {apsolu_calendars} cal ON cal.id = e.customchar1
             WHERE e.enrol = 'select'
               AND e.status = 0 -- Active.
               AND ue.status = :acceptedstatus
               AND ra.roleid = :roleid
               AND c.visible = 1
               AND u.deleted = 0
          ORDER BY u.lastname, u.firstname, c.fullname, cal.name";
    $params = [];
    $params['acceptedstatus'] = enrol_select_plugin::ACCEPTED;
    $params['roleid'] = $college->roleid;

    // Regroupe tous les inscriptions par calendrier.
    foreach ($DB->get_recordset_sql($sql, $params) as $user) {
        if (isset($users[$user->id]) === false) {
            continue;
        }

        if (isset($users[$user->id]->calendars) === false) {
            $users[$user->id]->calendars = [];
        }

        if (empty($user->calendar) === true) {
            $user->calendar = 0;
        }

        if (isset($users[$user->id]->calendars[$user->calendar]) === false) {
            $users[$user->id]->calendars[$user->calendar] = [];
        }

        $course = (object) ['id' => $user->courseid, 'fullname' => $user->fullname];
        $users[$user->id]->calendars[$user->calendar][] = $course;
    }

    // Controle les anomalies pour tous les utilisateurs.
    foreach ($users as $userid => $user) {
        if (isset($users[$user->id]->calendars) === false) {
            // L'utilisateur n'est pas inscrit alors qu'il appartient à une population.
            $users[$user->id]->calendars = [0 => []];
        }

        foreach ($user->calendars as $calendarid => $courses) {
            if (empty($mdata->calendar) === false && $mdata->calendar != $calendarid) {
                // Applique le filtre par calendrier.
                continue;
            }

            $minregister = $college->minregister;
            $maxregister = $college->maxregister;
            if (count($user->cohorts) === 0) {
                $minregister = 0;
                $maxregister = 0;
            }

            $row = new stdClass();
            $row->id = $user->id;
            $row->firstname = $user->firstname;
            $row->lastname = $user->lastname;
            $row->idnumber = $user->idnumber;
            $row->email = $user->email;
            $row->department = $user->department;
            $row->ufr = $user->ufr;
            $row->minregister = $minregister;
            $row->maxregister = $maxregister;
            $row->cohorts = $user->cohorts;

            $row->calendar = '';
            if (isset($calendars[$calendarid]) === true) {
                $row->calendar = $calendars[$calendarid];
            }

            $row->courses = [];
            $row->count_courses = 0;
            foreach ($courses as $course) {
                $row->courses[] = $course;
                $row->count_courses++;
            }

            $row->count_warnings = 0;
            if ($college->minregister > $row->count_courses) {
                $row->count_warnings++;
            }

            if ($college->maxregister < $row->count_courses) {
                $row->count_warnings++;
            }

            if ($row->count_warnings === 0 && isset($mdata->errors) === true) {
                // N'intègre pas les utilisateurs sans anomalie lorsqu'on veut seulement les erreurs.
                continue;
            }

            $rows[] = $row;
        }
    }
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->rows = array_values($rows);
$data->count_rows = count($rows);
$data->form = $mform->render();
$data->submit = isset($mdata);

if (isset($mdata->exportcsv) === true) {
    // Exporte les données au format CSV.
    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = 'extraction_des_inscriptions';

    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);

    // Définit les entêtes.
    $headers = [];
    $headers[] = get_string('user');
    $headers[] = get_string('idnumber');
    $headers[] = get_string('email');
    $headers[] = get_string('department');
    $headers[] = get_string('ufr', 'local_apsolu');
    $headers[] = get_string('calendar', 'local_apsolu');
    $headers[] = get_string('enrolments', 'enrol_select');
    $headers[] = get_string('minimum_enrolments', 'enrol_select');
    $headers[] = get_string('maximum_enrolments', 'enrol_select');
    $headers[] = get_string('courses');
    $csvexport->add_data($headers);

    // Définit le contenu principal.
    foreach ($rows as $row) {
        $count = 0;
        $courses = [];
        foreach ($row->courses as $course) {
            $count++;
            $courses[] = $course->fullname;
        }

        $data = [];
        $data[] = $row->firstname . ' ' . $row->lastname;
        $data[] = $row->idnumber;
        $data[] = $row->email;
        $data[] = $row->department;
        $data[] = $row->ufr;
        $data[] = $row->calendar;
        $data[] = $count;
        $data[] = $row->minregister;
        $data[] = $row->maxregister;
        $data[] = implode(', ', $courses);

        $csvexport->add_data($data);
    }

    $csvexport->download_file();
    exit();
}

if (isset($mdata->exportexcel) === true) {
    // Export au format excel.
    require_once($CFG->libdir . '/excellib.class.php');

    $workbook = new MoodleExcelWorkbook("-");
    $workbook->send('extraction_des_inscriptions.xls');
    $myxls = $workbook->add_worksheet();

    if (class_exists('PHPExcel_Style_Border') === true) {
        // Jusqu'à Moodle 3.7.x.
        $properties = ['border' => PHPExcel_Style_Border::BORDER_THIN];
    } else {
        // Depuis Moodle 3.8.x.
        $properties = ['border' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN];
    }

    $excelformat = new MoodleExcelFormat($properties);

    // Définit les entêtes.
    $headers = [];
    $headers[] = get_string('user');
    $headers[] = get_string('idnumber');
    $headers[] = get_string('email');
    $headers[] = get_string('department');
    $headers[] = get_string('ufr', 'local_apsolu');
    $headers[] = get_string('calendar', 'local_apsolu');
    $headers[] = get_string('enrolments', 'enrol_select');
    $headers[] = get_string('minimum_enrolments', 'enrol_select');
    $headers[] = get_string('maximum_enrolments', 'enrol_select');
    $headers[] = get_string('courses');
    foreach ($headers as $position => $value) {
        $myxls->write_string(0, $position, $value, $excelformat);
    }

    // Définit le contenu principal.
    $line = 1;
    foreach ($rows as $row) {
        $count = 0;
        $courses = [];
        foreach ($row->courses as $course) {
            $count++;
            $courses[] = $course->fullname;
        }

        $myxls->write_string($line, 0, $row->firstname . ' ' . $row->lastname, $excelformat);
        $myxls->write_string($line, 1, $row->idnumber, $excelformat);
        $myxls->write_string($line, 2, $row->email, $excelformat);
        $myxls->write_string($line, 3, $row->department, $excelformat);
        $myxls->write_string($line, 4, $row->ufr, $excelformat);
        $myxls->write_string($line, 5, $row->calendar, $excelformat);
        $myxls->write_string($line, 6, $count, $excelformat);
        $myxls->write_string($line, 7, $row->minregister, $excelformat);
        $myxls->write_string($line, 8, $row->maxregister, $excelformat);
        $myxls->write_string($line, 9, implode(', ', $courses), $excelformat);

        $line++;
    }

    // MDL-83543: positionne un cookie pour qu'un script js déverrouille le bouton submit après le téléchargement.
    setcookie('moodledownload_' . sesskey(), time());

    // Transmet le fichier au navigateur.
    $workbook->close();
    exit(0);
}

$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise');

echo $OUTPUT->render_from_template('enrol_select/administration_enrolments_overview', $data);
