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
 * Page gérant le renouvellement des inscriptions.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/enrol/select/lib.php');
require_once($CFG->dirroot . '/enrol/select/locallib.php');

require_login($courseorid = null, $autologinguest = false);

$context = context_user::instance($USER->id);

$PAGE->set_url('/enrol/select/renew.php');
$PAGE->set_pagelayout('base');

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

$open = false;
$nextopen = 0;
$calendars = $DB->get_records('apsolu_calendars', $conditions = [], $sort = 'reenrolstartdate');
foreach ($calendars as $calendar) {
    if (empty($calendar->reenrolstartdate) === true) {
        continue;
    }

    if (empty($calendar->reenrolenddate) === true) {
        continue;
    }

    if ($calendar->reenrolstartdate <= $time && $calendar->reenrolenddate >= $time) {
        $open = true;

        // Détermine la prochaine phase d'inscription.
        $sql = "SELECT * FROM {apsolu_calendars} WHERE enrolstartdate > :now ORDER BY enrolstartdate";
        $nextenrols = $DB->get_records_sql($sql, ['now' => $time]);
        if (empty($nextenrols) === false) {
            $nextenrol = current($nextenrols);
            if (isset($CFG->is_siuaps_rennes) === false) {
                // TODO: problème avec Rennes où les UE commencent les inscriptions avant les autres (en décembre
                // au lieu de janvier). À voir comment mieux gérer ce cas.
                $calendar->nextenrol = $nextenrol->enrolstartdate;
            }
        }
        break;
    }

    if ($calendar->reenrolstartdate >= $time && ($calendar->reenrolstartdate < $nextopen || $nextopen === 0)) {
        // Affiche la prochaine date d'ouverture de réinscriptions la plus proche et n'étant pas déjà passée.
        $nextopen = $calendar->reenrolstartdate;
    }
}

if ($open === false) {
    echo $OUTPUT->header();

    echo get_string('closedreenrolment', 'enrol_select');

    if ($nextopen !== 0) {
        $strdate = get_string('strftimedaydatetime', 'enrol_select');

        $next = new stdClass();
        $next->from = userdate($nextopen, $strdate);

        echo get_string('nextreenrolment', 'enrol_select', $next);
    }

    echo $OUTPUT->footer();

    exit(0);
}

// Javascript.
$PAGE->requires->js_call_amd('enrol_select/select_renew', 'initialise');

// Détermine quelles sont les activités auxquelles peut se réinscrire l'étudiant.
$activities = [];
foreach (enrol_select_get_user_reenrolments() as $key => $enrolment) {
    if ($enrolment->status !== enrol_select_plugin::ACCEPTED) {
        // On ne conserve que les inscriptions validées.
        if (defined('BEHAT_SITE_RUNNING') === false) {
            $message = 'L\'inscription d\'inscription #' . $enrolment->enrolid .
                ' du cours #' . $enrolment->id . ' n\'est pas validée (status: ' . $enrolment->status . ').';
            debugging($message, $level = DEBUG_DEVELOPER);
        }
        continue;
    }

    $enrol = $DB->get_record('enrol', ['id' => $enrolment->enrolid]);

    if ($enrol === false) {
        // L'instance d'inscription n'existe pas.
        if (defined('BEHAT_SITE_RUNNING') === false) {
            $message = 'L\'instance d\'inscription #' . $enrolment->enrolid . ' du cours #' . $enrolment->id . ' n\'existe pas.';
            debugging($message, $level = DEBUG_DEVELOPER);
        }
        continue;
    }

    if ($select->can_reenrol($enrol) === false) {
        // L'utilisateur n'est pas autorisé à se réinscrire.
        if (defined('BEHAT_SITE_RUNNING') === false) {
            $message = 'L\'utilisateur #' . $USER->id . ' n\'est pas autorisé à se réinscrire' .
                ' via l\'instance #' . $enrolment->enrolid . ' du cours #' . $enrolment->id . '.';
            debugging($message, $level = DEBUG_DEVELOPER);
        }
        continue;
    }

    $targetenrol = $DB->get_record('enrol', ['id' => $enrol->customint6]);

    if ($targetenrol === false) {
        // L'instance de réinscription n'existe pas.
        if (defined('BEHAT_SITE_RUNNING') === false) {
            $message = 'L\'instance de réinscription #' . $targetenrol->id . ' du cours #' . $enrolment->id . ' n\'existe pas.';
            debugging($message, $level = DEBUG_DEVELOPER);
        }
        continue;
    }

    // Get all available roles for target enrol.
    $roles = [];
    foreach ($select->get_available_user_roles($targetenrol) as $role) {
        $roles[$role->id] = $role->name;
    }

    if ($roles === []) {
        // L'utilisateur ne peut pas s'incrire (problème de cohortes ou de rôles).
        if (defined('BEHAT_SITE_RUNNING') === false) {
            $message = 'L\'utilisateur #' . $USER->id . ' ne peut pas s\'inscrire (problème de cohortes ou de rôles).';
            debugging($message, $level = DEBUG_DEVELOPER);
        }
        continue;
    }

    // On enregistre ces données, car elles seront réutilisées plus loin.
    $enrolment->roles = $roles;
    $enrolment->targetenrol = $targetenrol;

    $activities[$key] = $enrolment;
}

$notification = '';
if (isset($_POST['reenrol'])) {
    // Parcours les réponses.
    $mailcontent = [];
    foreach ($_POST['renew'] as $enrolid => $renew) {
        $instance = $DB->get_record('enrol', ['id' => $enrolid, 'enrol' => 'select']);

        $log = userdate(time(), '%c') . ' ' . $USER->firstname . ' ' . $USER->lastname . ' (' . $USER->email .
            ' #id ' . $USER->id . ')';

        if (!$instance) {
            debugging('ERROR: ' . $log . ' invalid instance #id ' . $enrolid, $level = NO_DEBUG_DISPLAY);
            continue;
        }

        $course = $DB->get_record('course', ['id' => $instance->courseid]);
        if ($course === false) {
            continue;
        }

        if ($renew === '0') {
            // Désinscrire l'utilisateur...
            $select->unenrol_user($instance, $USER->id);

            $mailcontent[] = get_string('reenrolmentstop', 'enrol_select', $course);

            // Ajouter une ligne de log.
            debugging($log . ' unenrol from instanceid ' . $instance->id, $level = NO_DEBUG_DISPLAY);
        } else if ($renew === '1') {
            // Inscrire l'utilisateur...
            if (isset($_POST['role'][$enrolid])) {
                $roleid = $_POST['role'][$enrolid];

                if (isset($activities[$instance->courseid], $activities[$instance->courseid]->roles[$roleid])) {
                    if (isset($CFG->is_siuaps_rennes) === true) {
                        // Inscription liste définitive.
                        $select->enrol_user(
                            $instance,
                            $USER->id,
                            $roleid,
                            $instance->customint7,
                            $instance->customint8,
                            $status = ENROL_INSTANCE_ENABLED,
                            $recovergrades = null
                        );
                    } else {
                        // Inscription liste principale.
                        $select->enrol_user(
                            $instance,
                            $USER->id,
                            $roleid,
                            $instance->customint7,
                            $instance->customint8,
                            $status = 2,
                            $recovergrades = null
                        );
                    }

                    $mailcontent[] = get_string('reenrolmentcontinue', 'enrol_select', $course);

                    // Ajouter une ligne de log.
                    debugging($log . ' enrol into instanceid ' . $instance->id . ', roleid ' . $roleid, $level = NO_DEBUG_DISPLAY);
                } else {
                    // Ajouter une ligne de log.
                    $reasons = [];
                    if (!isset($activities[$instance->courseid])) {
                        $reasons[] = 'non inscrit dans le cours #' . $instance->courseid;
                    } else if (!isset($activities[$instance->courseid]->roles[$roleid])) {
                        $reasons[] = 'non autorisé pour le rôle #' . $roleid;
                    }

                    $message = 'ERROR: ' . $log . ' can\'t enrol instanceid ' . $instance->id .
                        ', roleid ' . $roleid . ' :: ' . implode(', ', $reasons);
                    debugging($message, $level = NO_DEBUG_DISPLAY);
                }
            }
        }
    }

    if (isset($mailcontent[0]) === true) {
        // Notifie l'utilisateur.
        sort($mailcontent);

        $list = new stdClass();
        $list->choices = ' - ' . implode(PHP_EOL . ' - ', $mailcontent);
        $body = get_string('reenrolmentnotification', 'enrol_select', $list);
        $subject = get_string('reenrolmentnotificationsubject', 'enrol_select');

        email_to_user($USER, '', $subject, $body);
    }

    $strdate = get_string('strftimedaydatetime', 'enrol_select');
    $savedreenrolment = new stdClass();
    $savedreenrolment->date = userdate($calendar->reenrolenddate, $strdate);

    $notification = $OUTPUT->notification(get_string('savedreenrolment', 'enrol_select', $savedreenrolment), 'notifysuccess');
}

$enrolments = [];
$enrolmentscount = 0;
foreach ($activities as $enrolment) {
    $roles = $enrolment->roles;
    $targetenrol = $enrolment->targetenrol;

    // Contact teachers.
    $enrolment->teachers = [];
    $enrolment->count_teachers = 0;

    $sql = "SELECT ra.*" .
        " FROM {role_assignments} ra" .
        " JOIN {context} c ON c.id = ra.contextid" .
        " WHERE c.instanceid = :courseid" .
        " AND c.contextlevel = 50" .
        " AND ra.roleid = 3";
    $assignments = $DB->get_records_sql($sql, ['courseid' => $targetenrol->courseid]);
    foreach ($assignments as $assignment) {
        $teacher = $DB->get_record('user', ['id' => $assignment->userid, 'deleted' => 0]);
        if ($teacher) {
            if (stripos($teacher->email, '@uhb.fr') === false) {
                $enrolment->teachers[] = $teacher;
                $enrolment->count_teachers++;
            }
        }
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
    foreach ([1 => get_string('yes'), 0 => get_string('no')] as $value => $label) {
        $checked = ($renew == $value);
        $checkbox = html_writer::checkbox($name = 'renew[' . $targetenrol->id . ']', $value, $checked, $label, $attributes);
        $enrolment->renew .= str_replace('checkbox', 'radio', $checkbox);
    }

    $enrolment->roles = '';
    foreach ($roles as $value => $label) {
        $checked = ($role == $value);
        $checkbox = html_writer::checkbox($name = 'role[' . $targetenrol->id . ']', $value, $checked, $label, $attributes);
        $enrolment->roles .= str_replace('checkbox', 'radio', $checkbox);
    }

    $enrolments[] = $enrolment;
    $enrolmentscount++;
}

$data = new stdClass();
$data->action = $CFG->wwwroot . '/enrol/select/renew.php';
$data->enrolments = $enrolments;
$data->enrolments_count = $enrolmentscount;
$data->notification = $notification;

if ($enrolmentscount === 0) {
    $strdate = get_string('strftimedaydatetime', 'enrol_select');

    $data->nextenrolment = '';
    if (isset($calendar->nextenrol) === true) {
        $next = new stdClass();
        $next->from = userdate($calendar->nextenrol, $strdate);
        $data->nextenrolment = get_string('nextenrolment', 'enrol_select', $next);
    }
} else {
    $strdate = get_string('strftimedaydatetime', 'enrol_select');

    $explanation = new stdClass();
    $explanation->limit = userdate($calendar->reenrolenddate, $strdate);
    if (isset($calendar->nextenrol) === true) {
        $explanation->from = userdate($calendar->nextenrol, $strdate);
        $data->explanation = get_string('reenrolmentexplanationcase', 'enrol_select', $explanation);
    } else {
        $data->explanation = get_string('reenrolmentexplanationcasenoenrol', 'enrol_select', $explanation);
    }
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('enrol_select/reenrol', $data);

echo $OUTPUT->footer();
