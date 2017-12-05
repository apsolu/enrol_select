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
 * Adds new instance of enrol_select to specified course
 * or edits current instance.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;

require('../../config.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');
require_once($CFG->dirroot.'/enrol/select/locallib.php');

require_login();

$context = context_user::instance($USER->id);

$PAGE->set_url('/enrol/select/renew.php');
$PAGE->set_pagelayout('course');

$PAGE->set_context($context);

$PAGE->set_heading(get_string('renewtitle', 'enrol_select'));
$PAGE->set_title(get_string('pluginname', 'enrol_select'));

// Navigation.
$PAGE->navbar->add(get_string('reenrolment', 'enrol_select'));

if (!$select = enrol_get_plugin('select')) {
    throw new coding_exception('Can not instantiate enrol_select');
}

// Vérifie que la période de réinscription est ouverte.
$time = time();
$semester1_reenrol_startdate = get_config('local_apsolu', 'semester1_reenrol_startdate');
$semester1_reenrol_enddate = get_config('local_apsolu', 'semester1_reenrol_enddate');

$open = ($semester1_reenrol_startdate <= $time && $semester1_reenrol_enddate >= $time);
if ($open === false) {
    echo $OUTPUT->header();

    echo get_string('closedreenrolment', 'enrol_select');

    if ($semester1_reenrol_startdate > $time) {
        $strdate = get_string('strftimedaydatetime', 'enrol_select');

        $next = new stdClass();
        $next->from = userdate($semester1_reenrol_startdate, $strdate);

        echo get_string('nextreenrolment', 'enrol_select', $next);
    }

    echo $OUTPUT->footer();

    exit(0);
}

// Javascript.
$PAGE->requires->js_call_amd('enrol_select/select_renew', 'initialise');

$notification = '';
if (isset($_POST['reenrol'])) {
    $logfile = '/log/applis/renew.log';

    // Parcours les réponses.
    foreach ($_POST['renew'] as $enrolid => $renew) {
        $instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'select'));

        $log = strftime('%c').' '.$USER->firstname.' '.$USER->lastname.' ('.$USER->email.' #id '.$USER->id.')';

        if (!$instance) {
            file_put_contents($logfile, 'ERROR: '.$log.' invalid instance #id '.$enrolid.PHP_EOL, FILE_APPEND);
            continue;
        }


        if ($renew === '0') {
            // Désinscrire l'utilisateur...
            $select->unenrol_user($instance, $USER->id);

            // Ajouter une ligne de log.
            file_put_contents($logfile, $log.' unenrol from '.$instance->id.PHP_EOL, FILE_APPEND);
        } else if ($renew === '1') {
            // Inscrire l'utilisateur...
            if (isset($_POST['role'][$enrolid])) {
                $roleid = $_POST['role'][$enrolid];

                $activities = apsolu\get_real_user_activity_enrolments();
                $roles = $select->get_available_user_roles($instance);
                if (isset($activities[$instance->courseid], $roles[$roleid])) {
                    $select->enrol_user($instance, $USER->id, $roleid, $instance->customint7, $instance->customint8, $status = ENROL_INSTANCE_ENABLED, $recovergrades = null);

                    // Ajouter une ligne de log.
                    file_put_contents($logfile, $log.' enrol into instanceid '.$instance->id.', roleid '.$roleid.PHP_EOL, FILE_APPEND);
                } else {
                    // Ajouter une ligne de log.
                    $reasons = array();
                    if (!isset($activities[$instance->courseid])) {
                        $reasons[] = 'non inscrit dans le cours #'.$instance->courseid;
                    }

                    if (!isset($roles[$roleid])) {
                        $reasons[] = 'non autorisé pour le rôle #'.$roleid;
                    }

                    file_put_contents($logfile, 'ERROR: '.$log.' can\'t enrol instanceid '.$instance->id.', roleid '.$roleid.' :: '.implode(', ', $reasons).PHP_EOL, FILE_APPEND);
                }
            }
        }
    }

    $notification = $OUTPUT->notification(get_string('savedreenrolment', 'enrol_select'), 'notifysuccess');
}

$enrolments = array();
$enrolments_count = 0;
foreach (apsolu\get_user_reenrolments() as $enrolment) {
    if ($enrolment->status !== enrol_select_plugin::ACCEPTED) {
        // On ne conserve que les inscriptions validées.
        continue;
    }

    $enrol = $DB->get_record('enrol', array('id' => $enrolment->enrolid));

    if ($enrol && $select->can_reenrol($enrol)) {
        $targetenrol = $DB->get_record('enrol', array('id' => $enrol->customint6));
        if ($targetenrol) {
            // Contact teachers.
            $enrolment->teachers = array();
            $enrolment->count_teachers = 0;

            $sql = "SELECT ra.*".
                " FROM {role_assignments} ra".
                " JOIN {context} c ON c.id = ra.contextid".
                " WHERE c.instanceid = :courseid".
                " AND c.contextlevel = 50".
                " AND ra.roleid = 3";
            $assignments = $DB->get_records_sql($sql, array('courseid' => $targetenrol->courseid));
            foreach ($assignments as $assignment) {
                $teacher = $DB->get_record('user', array('id' => $assignment->userid, 'deleted' => 0));
                if ($teacher) {
                    if (stripos($teacher->email, '@uhb.fr') === false) {
                        $enrolment->teachers[] = $teacher;
                        $enrolment->count_teachers++;
                    }
                }
            }

            // Get all available roles for target enrol.
            $roles = array();
            foreach ($select->get_available_user_roles($targetenrol) as $role) {
                $roles[$role->id] = $role->name;
            }

            if ($roles === array()) {
                // L'utilisateur ne peut pas s'incrire (problème de cohortes ou de rôles).
                continue;
            }

            // Set current role for user.
            $role = $select->get_user_role($targetenrol);
            if ($role) {
                // Si défini, le rôle choisi.
                $role = $role->id;
                $renew = 1;
            } else {
                // Si non défini, le rôle précédent.
                $role = $enrolment->roleid;
                $renew = 0;
            }

            // Build form.
            $attributes = null;

            $enrolment->renew = '';
            foreach (array(1 => get_string('yes'), 0 => get_string('no')) as $value => $label) {
                $checked = ($renew == $value);
                $checkbox = html_writer::checkbox($name = 'renew['.$targetenrol->id.']', $value, $checked, $label, $attributes);
                $enrolment->renew .= str_replace('checkbox', 'radio', $checkbox);
            }

            $enrolment->roles = '';
            foreach ($roles as $value => $label) {
                $checked = ($role == $value);
                $checkbox = html_writer::checkbox($name = 'role['.$targetenrol->id.']', $value, $checked, $label, $attributes);
                $enrolment->roles .= str_replace('checkbox', 'radio', $checkbox);
            }

            $enrolments[] = $enrolment;
            $enrolments_count++;
        }
    }
}

$data = new stdClass();
$data->action = $CFG->wwwroot.'/enrol/select/renew.php';
$data->enrolments = $enrolments;
$data->enrolments_count = $enrolments_count;
$data->notification = $notification;

if ($enrolments_count === 0) {
    $strdate = get_string('strftimedaydatetime', 'enrol_select');
    $semester2_enrol_startdate = get_config('local_apsolu', 'semester2_enrol_startdate');

    $next = new stdClass();
    $next->from = userdate($semester2_enrol_startdate, $strdate);
    $data->nextenrolment = get_string('nextenrolment', 'enrol_select', $next);
} else {
    $strdate = get_string('strftimedaydatetime', 'enrol_select');
    $semester1_reenrol_enddate = get_config('local_apsolu', 'semester1_reenrol_enddate');
    $semester2_enrol_startdate = get_config('local_apsolu', 'semester2_enrol_startdate');

    $explanation = new stdClass();
    $explanation->limit = userdate($semester1_reenrol_enddate, $strdate);
    $explanation->from = userdate($semester2_enrol_startdate, $strdate);
    $data->explanation = get_string('reenrolmentexplanationcase', 'enrol_select', $explanation);
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('enrol_select/reenrol', $data);

echo $OUTPUT->footer();
