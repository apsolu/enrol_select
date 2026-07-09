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
 * Page d'affichage de la vue d'ensemble des méthodes d'inscription.
 *
 * @package   enrol_select
 * @copyright 2020 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Fichier chargé automatiquement pour les administrateurs, mais pas pour les gestionnaires visiblement.
require_once($CFG->dirroot . '/enrol/select/lib.php');
require_once($CFG->dirroot . '/enrol/select/locallib.php');
require_once($CFG->dirroot . '/enrol/select/administration/enrolment_methods_overview/view_filter_form.php');

$PAGE->requires->js_call_amd('enrol_select/administration_overview', 'initialise');

// Récupère la liste des enseignants.
$sql = "SELECT u.*, ctx.instanceid" .
    " FROM {user} u" .
    " JOIN {role_assignments} ra ON u.id = ra.userid AND ra.roleid = 3" . // Teacher.
    " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50" . // Course context.
    " JOIN {apsolu_courses} ac ON ac.id = ctx.instanceid" .
    " ORDER BY u.lastname, u.firstname";
$recordset = $DB->get_recordset_sql($sql);

$teachers = [0 => get_string('choosedots')];
$courseteachers = [];
foreach ($recordset as $teacher) {
    // Collecte les enseignants.
    if (isset($teachers[$teacher->id]) === false) {
        $teachers[$teacher->id] = fullname($teacher);
    }

    // Collecte les enseignants indexés par cours.
    if (isset($courseteachers[$teacher->instanceid]) === false) {
        $courseteachers[$teacher->instanceid] = [];
    }
    $courseteachers[$teacher->instanceid][] = $teachers[$teacher->id];
}
$recordset->close();

// Récupère la liste des calendriers.
$calendars = $DB->get_records('apsolu_calendars');

$mform = new apsolu_overview_filter_form($PAGE->url->out(false), [$calendars, $teachers]);
$mdata = $mform->get_data();

// Liste des cours.
$sql = "SELECT c.id, c.fullname, c.idnumber, '0' AS count_enrols, '1' AS anomalies,
               l.name AS location, aa.name AS area, city.name AS city
          FROM {course} c
          JOIN {apsolu_courses} ac ON ac.id = c.id
          JOIN {apsolu_locations} l ON l.id = ac.locationid
          JOIN {apsolu_areas} aa ON aa.id = l.areaid
          JOIN {apsolu_cities} city ON city.id = aa.cityid
          JOIN {course_categories} cc ON cc.id = c.category
         WHERE c.visible = 1
      ORDER BY cc.sortorder, ac.numweekday, ac.starttime";
$courses = $DB->get_records_sql($sql);

// Liste des inscriptions.
$sql = "SELECT CONCAT(enrolid, '-', status), COUNT(*) AS count" .
    " FROM {user_enrolments}" .
    " GROUP BY enrolid, status";
$enrolments = $DB->get_records_sql($sql);

// Liste des méthodes d'inscription.
$sql = "SELECT e.id, e.name, e.courseid, e.enrolstartdate, e.enrolenddate, e.customint1, e.customint2, e.customint3 AS quota," .
    " ac.id AS calendarid, ac.name AS calendar, ac.enrolstartdate AS calendar_enrolstartdate," .
    " ac.enrolenddate AS calendar_enrolenddate" .
    " FROM {enrol} e" .
    " LEFT JOIN {apsolu_calendars} ac ON e.customchar1 = ac.id" .
    " WHERE e.enrol = 'select'" .
    " AND e.status = 0" .
    " ORDER BY e.courseid, e.name";
$enrols = $DB->get_records_sql($sql);

foreach ($enrols as $enrol) {
    if (isset($courses[$enrol->courseid]) === false) {
        // Le cours n'existe pas ou n'est pas une activité APSOLU.
        continue;
    }

    if (isset($courses[$enrol->courseid]->enrols) === false) {
        $courses[$enrol->courseid]->enrols = [];
        $courses[$enrol->courseid]->count_enrols = 0;
        $courses[$enrol->courseid]->anomalies = 0;
    }

    if (empty($enrol->name) === true) {
        $enrol->name = get_string('pluginname', 'enrol_select');
    }

    // Calcule les différences avec le calendrier.
    $enrol->invalid_enrolstartdate = false;
    $enrol->invalid_enrolenddate = false;
    if (empty($enrol->calendar) === false) {
        $enrol->invalid_enrolstartdate = ($enrol->calendar_enrolstartdate !== $enrol->enrolstartdate);
        $enrol->invalid_enrolenddate = ($enrol->calendar_enrolenddate !== $enrol->enrolenddate);
    }

    // Répartis les inscriptions par statut.
    foreach (enrol_select_plugin::$states as $statusid => $statusname) {
        $key = $enrol->id . '-' . $statusid;
        if (isset($enrolments[$key]) === false) {
            $enrolments[$key] = new stdClass();
            $enrolments[$key]->count = 0;
        }

        $variable = 'count_' . $statusname . '_list';
        $enrol->{$variable} = $enrolments[$key]->count;
    }

    $enrol->available_places = false;
    if (empty($enrol->quota) === true || ($enrol->count_accepted_list + $enrol->count_main_list) < $enrol->customint1) {
        $enrol->available_places = true;
    }

    if ($courses[$enrol->courseid]->anomalies === 0) {
        $courses[$enrol->courseid]->anomalies = intval($enrol->invalid_enrolstartdate ||
            $enrol->invalid_enrolenddate || empty($enrol->quota));
    }

    $courses[$enrol->courseid]->enrols[] = $enrol;
    $courses[$enrol->courseid]->count_enrols++;
}

$teachers = enrol_select_get_activities_teachers();

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->courses = [];
$data->count_courses = 0;
$data->form = $mform->render();
$data->filters = isset($mdata->course);

// Filtre les cours.
foreach ($courses as $course) {
    if (empty($course->idnumber) === false) {
        // Ajoute l'identifiant au nom complet du cours, si il n'est pas vide.
        $course->fullname = sprintf('%s (%s)', $course->fullname, $course->idnumber);
    }

    if ($data->filters === false) {
        // Aucun filtre n'a été sélectionné.
        $data->courses[] = $course;
        $data->count_courses++;
        continue;
    }

    // Filtre par nom de créneau horaire.
    if (empty($mdata->course) === false && stripos($course->fullname, $mdata->course) === false) {
        // Le filtre ne correspond pas au nom du cours.
        continue;
    }

    // Filtre par enseignant.
    if (empty($mdata->teacherid) === false && isset($teachers[$course->id][$mdata->teacherid]) === false) {
        // Le filtre ne correspond pas à l'enseignant sélectionné.
        continue;
    }

    if (empty($course->count_enrols) === true) {
        // Le créneau n'a pas de méthodes d'inscription. On le garde.
        $data->courses[] = $course;
        $data->count_courses++;
        continue;
    }

    foreach ($course->enrols as $id => $enrol) {
        // Filtre par calendrier.
        if (empty($mdata->calendarid) === false && $enrol->calendarid != $mdata->calendarid) {
            // Le filtre ne correspond pas au calendrier sélectionné.
            unset($course->enrols[$id]);
            $course->count_enrols--;
            continue;
        }

        // Filtre par date de début d'inscription.
        if (
            empty($mdata->enrolstartdate) === false &&
            userdate($enrol->enrolstartdate, '%F') !== userdate($mdata->enrolstartdate, '%F')
        ) {
            // Le filtre ne correspond pas à la date de début d'inscription sélectionné.
            unset($course->enrols[$id]);
            $course->count_enrols--;
            continue;
        }

        // Filtre par date de fin d'inscription.
        if (
            empty($mdata->enrolenddate) === false &&
            userdate($enrol->enrolenddate, '%F') !== userdate($mdata->enrolenddate, '%F')
        ) {
            // Le filtre ne correspond pas à la date de fin d'inscription sélectionné.
            unset($course->enrols[$id]);
            $course->count_enrols--;
            continue;
        }
    }

    if (empty($course->count_enrols) === false) {
        // Le créneau a toujours au moins une méthode d'inscription. On le garde.
        $course->enrols = array_values($course->enrols);
        $data->courses[] = $course;
        $data->count_courses++;
        continue;
    }
}

// Définit les noms des listes pour la section 'Gestion des listes'.
$data->accepted_list = ucfirst(get_accepted_listname());
$data->main_list = ucfirst(get_main_listname());
$data->wait_list = ucfirst(get_wait_listname());
$data->deleted_list = ucfirst(get_deleted_listname());

$data->accepted_list_abbr = get_enrol_list_fieldvalue(enrol_select_plugin::ACCEPTED, 'statusabbr');
$data->main_list_abbr = get_enrol_list_fieldvalue(enrol_select_plugin::MAIN, 'statusabbr');
$data->wait_list_abbr = get_enrol_list_fieldvalue(enrol_select_plugin::WAIT, 'statusabbr');
$data->deleted_list_abbr = get_enrol_list_fieldvalue(enrol_select_plugin::DELETED, 'statusabbr');

if (isset($mdata->exportcsv) === true || isset($mdata->exportexcel) === true) {
    // Définit les entêtes du tableau d'export (csv et excel).
    $headers = [];
    $headers[] = get_string('courses', 'local_apsolu');
    $headers[] = get_string('teachers', 'local_apsolu');
    $headers[] = get_string('locations', 'local_apsolu');
    $headers[] = get_string('enrolname', 'enrol_select');
    $headers[] = get_string('calendars', 'local_apsolu');
    $headers[] = get_string('enrolstartdate', 'enrol_select');
    $headers[] = get_string('enrolenddate', 'enrol_select');
    $headers[] = $data->accepted_list;
    $headers[] = $data->main_list;
    $headers[] = get_string_on_list_x(
        [enrol_select_plugin::ACCEPTED, enrol_select_plugin::MAIN],
        'max_places',
        'listname',
    );
    $headers[] = $data->wait_list;
    $headers[] = get_string_on_list_x(
        enrol_select_plugin::WAIT,
        'max_places_on_listname_X'
    );
    $headers[] = $data->deleted_list;
}
if (isset($mdata->exportcsv) === true) {
    // Exporte les données au format CSV.
    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = 'extraction_des_methodes_d_inscription';

    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);
    $csvexport->add_data($headers);

    // Définit le contenu principal.
    foreach ($data->courses as $course) {
        if (isset($courseteachers[$course->id]) === false) {
            $courseteachers[$course->id] = [];
        }

        foreach ($course->enrols as $enrol) {
            $row = [];
            $row[] = $course->fullname;
            $row[] = implode(', ', $courseteachers[$course->id]);
            $row[] = sprintf('%s, %s, %s', $course->location, $course->area, $course->city);
            $row[] = $enrol->name;
            $row[] = $enrol->calendar;
            $row[] = userdate($enrol->enrolstartdate, get_string('strftimedatetime'));
            $row[] = userdate($enrol->enrolenddate, get_string('strftimedatetime'));
            $row[] = $enrol->count_accepted_list;
            $row[] = $enrol->count_main_list;
            if (empty($enrol->quota) === true) {
                $row[] = get_string('no_quotas', 'enrol_select');
            } else {
                $row[] = $enrol->customint1;
            }
            $row[] = $enrol->count_wait_list;
            if (empty($enrol->quota) === true) {
                $row[] = get_string('no_quotas', 'enrol_select');
            } else {
                $row[] = $enrol->customint2;
            }
            $row[] = $enrol->count_deleted_list;

            $csvexport->add_data($row);
        }

        if (empty($course->count_enrols) === true) {
            $row = [];
            $row[] = $course->fullname;
            $row[] = implode(', ', $courseteachers[$course->id]);
            $row[] = sprintf('%s, %s, %s', $course->location, $course->area, $course->city);
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';

            $csvexport->add_data($row);
        }
    }

    $csvexport->download_file();
    exit();
}

if (isset($mdata->exportexcel) === true) {
    // Export au format excel.
    require_once($CFG->libdir . '/excellib.class.php');

    $workbook = new MoodleExcelWorkbook("-");
    $workbook->send('extraction_des_methodes_d_inscription.xls');
    $myxls = $workbook->add_worksheet();

    if (class_exists('PHPExcel_Style_Border') === true) {
        // Jusqu'à Moodle 3.7.x.
        $properties = ['border' => PHPExcel_Style_Border::BORDER_THIN];
    } else {
        // Depuis Moodle 3.8.x.
        $properties = ['border' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN];
    }

    $excelformat = new MoodleExcelFormat($properties);

    foreach ($headers as $position => $value) {
        $myxls->write_string(0, $position, $value, $excelformat);
    }

    // Définit le contenu principal.
    $line = 1;
    foreach ($data->courses as $course) {
        if (isset($courseteachers[$course->id]) === false) {
            $courseteachers[$course->id] = [];
        }

        foreach ($course->enrols as $enrol) {
            $n = 0;
            $myxls->write_string($line, $n++, $course->fullname, $excelformat);
            $myxls->write_string($line, $n++, implode(', ', $courseteachers[$course->id]), $excelformat);
            $myxls->write_string($line, $n++, sprintf('%s, %s, %s', $course->location, $course->area, $course->city), $excelformat);
            $myxls->write_string($line, $n++, $enrol->name, $excelformat);
            $myxls->write_string($line, $n++, $enrol->calendar, $excelformat);
            $myxls->write_date($line, $n++, $enrol->enrolstartdate, $excelformat);
            $myxls->write_date($line, $n++, $enrol->enrolenddate, $excelformat);
            $myxls->write_string($line, $n++, $enrol->count_accepted_list, $excelformat);
            $myxls->write_string($line, $n++, $enrol->count_main_list, $excelformat);
            if (empty($enrol->quota) === true) {
                $myxls->write_string($line, $n++, get_string('no_quotas', 'enrol_select'), $excelformat);
            } else {
                $myxls->write_string($line, $n++, $enrol->customint1, $excelformat);
            }
            $myxls->write_string($line, $n++, $enrol->count_wait_list, $excelformat);
            if (empty($enrol->quota) === true) {
                $myxls->write_string($line, $n++, get_string('no_quotas', 'enrol_select'), $excelformat);
            } else {
                $myxls->write_string($line, $n++, $enrol->customint2, $excelformat);
            }
            $myxls->write_string($line, $n++, $enrol->count_deleted_list, $excelformat);

            $line++;
        }

        if (empty($course->count_enrols) === true) {
            $n = 0;
            $myxls->write_string($line, $n++, $course->fullname, $excelformat);
            $myxls->write_string($line, $n++, implode(', ', $courseteachers[$course->id]), $excelformat);
            $myxls->write_string($line, $n++, sprintf('%s, %s, %s', $course->location, $course->area, $course->city), $excelformat);
            $myxls->write_string($line, $n++, '', $excelformat);
            $myxls->write_string($line, $n++, '', $excelformat);
            $myxls->write_string($line, $n++, '', $excelformat);
            $myxls->write_string($line, $n++, '', $excelformat);
            $myxls->write_string($line, $n++, '', $excelformat);
            $myxls->write_string($line, $n++, '', $excelformat);
            $myxls->write_string($line, $n++, '', $excelformat);
            $myxls->write_string($line, $n++, '', $excelformat);
            $myxls->write_string($line, $n++, '', $excelformat);
            $myxls->write_string($line, $n++, '', $excelformat);

            $line++;
        }
    }

    // MDL-83543: positionne un cookie pour qu'un script js déverrouille le bouton submit après le téléchargement.
    setcookie('moodledownload_' . sesskey(), time());

    // Transmet le fichier au navigateur.
    $workbook->close();
    exit(0);
}

echo $OUTPUT->render_from_template('enrol_select/administration_enrolment_methods_overview', $data);
