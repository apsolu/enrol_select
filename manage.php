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
 * Page de gestion des inscriptions du module enrol_select.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu\Payment;
use local_apsolu\core\customfields;
use local_apsolu\core\role;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');

$enrolid = optional_param('enrolid', null, PARAM_INT);

if ($enrolid !== null) {
    $instance = $DB->get_record('enrol', ['id' => $enrolid, 'enrol' => 'select'], '*', MUST_EXIST);
    $courseid = $instance->courseid;
} else {
    $courseid = required_param('courseid', PARAM_INT);
}
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$canenrol = has_capability('enrol/select:enrol', $context);
$canunenrol = has_capability('enrol/select:unenrol', $context);

$ismanager = $DB->get_record('role_assignments', ['contextid' => 1, 'roleid' => 1, 'userid' => $USER->id]);

// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.

if (!$canenrol) {
    // No need to invent new error strings here...
    require_capability('enrol/select:enrol', $context);
}

if (!$enrolselect = enrol_get_plugin('select')) {
    throw new coding_exception('Can not instantiate enrol_select');
}

$instances = $DB->get_records('enrol', ['enrol' => 'select', 'courseid' => $course->id], $sort = 'name');

if (count($instances) === 0) {
    throw new moodle_exception('listnoitem', 'error');
}

$roles = Role::get_records();
$extrafields = [];
foreach (customfields::get_extra_fields('display') as $fieldname => $label) {
    $extrafield = new stdClass();
    $extrafield->fieldname = $fieldname;
    $extrafield->label = $label;

    $extrafields[$fieldname] = $extrafield;
}

$enrols = [];
$activeenrolid = false;

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->canunenrol = $canunenrol;
$data->extrafields = array_values($extrafields);
$data->enrols = [];

// Initialise chaque instance du cours utilisant la méthode enrol_select.
foreach ($instances as $instance) {
    $enrol = new stdClass();
    $enrol->name = $enrolselect->get_instance_name($instance);
    $enrol->enrolid = $instance->id;
    $enrol->enrol_user_link = $CFG->wwwroot . '/enrol/select/enrol.php?enrolid=' . $instance->id;
    $enrol->unenrol_user_link = $CFG->wwwroot . '/enrol/select/unenrol.php?enrolid=' . $instance->id;
    $enrol->lists = [];
    $enrol->lock = ($instance->customint8 < time());
    if ($ismanager !== false || is_siteadmin() === true) {
        // Les gestionnaires et les administrateurs peuvent modifier les inscriptions toute l'année.
        // TODO: créer une permission pour gérer ce point.
        $enrol->lock = false;
    }

    // On initialise chaque liste (LP, LC, etc).
    foreach (enrol_select_plugin::$states as $code => $state) {
        $nextinstance = $DB->get_record('enrol', ['id' => $instance->customint6, 'enrol' => 'select'], '*', IGNORE_MISSING);

        $selectoptions = [];
        $mainoptions   = [];
        $suboptions    = [];
        $otheroptions  = [];

        foreach (enrol_select_plugin::$states as $scode => $sstate) {
            $mainoptions[$scode] = get_string('move_to_' . $sstate, 'enrol_select');
            if ($nextinstance !== false) {
                $suboptions['99' . $scode] = get_string('move_to_next_' . $sstate, 'enrol_select');
            }
        }

        $otheroptions['notify'] = get_string('notify', 'enrol_select');
        $otheroptions['editenroltype'] = get_string('editenroltype', 'enrol_select');
        $otheroptions['changecourse'] = get_string('change_course', 'enrol_select');

        // Current instance.
        unset($mainoptions[$code]);
        $selectoptions[] = ['Déplacement au sein de ' . $instance->name => $mainoptions];

        // Other actions than manage_move.
        $selectoptions[] = ['Autres actions' => $otheroptions];

        // Renewal instance, semester 2.
        if ($nextinstance !== false) {
            $selectoptions[] = ['Réinscription vers ' . $nextinstance->name => $suboptions];
        }

        $togglegroup = 'enrol-' . $enrol->enrolid . '-status-' . $code . '-togglegroup';
        $mastercheckbox = new \core\output\checkbox_toggleall($togglegroup, $ismaster = true, [
            'id' => 'select-all-users-from-enrol-' . $enrol->enrolid . '-status-' . $code,
            'name' => 'select-all-users-from-enrol-' . $enrol->enrolid . '-status-' . $code,
            'label' => get_string('selectall'),
            'labelclasses' => 'visually-hidden',
            'classes' => 'm-1',
            'checked' => false,
            ]);

        $list = new stdClass();
        $list->mastercheckbox = $OUTPUT->render($mastercheckbox);
        $list->name = get_string($state . '_list', 'enrol_select');
        $list->description = get_string($state . '_description', 'enrol_select');
        $list->status = $code;
        $list->form_action = $CFG->wwwroot . '/enrol/select/manage_handler.php?enrolid=' . $instance->id;
        $list->enrol_user_link = $CFG->wwwroot . '/enrol/select/add.php?enrolid=' . $instance->id . '&status=' . $code;
        $list->users = [];
        $list->count_users = 0;

        switch ($code) {
            case enrol_select_plugin::MAIN:
                $list->max_users = $instance->customint1;
                break;
            case enrol_select_plugin::WAIT:
                $list->max_users = $instance->customint2;
                break;
            default:
                $list->max_users = false;
        }

        $htmlselectattributes = ['id' => 'to-' . $state, 'class' => 'select_options'];
        $list->actions = '<p>' . html_writer::tag('label', get_string("withselectedusers"), ['for' => 'to-' . $state]) .
            html_writer::select($selectoptions, 'actions', '', ['' => 'choosedots'], $htmlselectattributes) .
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'from', 'value' => $code]) .
            '</p>';

        $enrol->lists[$code] = $list;
    }

    if ($enrolid !== null) {
        // Le paramètre "enrolid" a été passé dans l'URL. On veut afficher l'onglet de cette méthode d'inscription par défaut.
        $activeenrolid = $enrolid;
    } else if (time() > $instance->enrolstartdate && (time() < $instance->customint8 || empty($instance->customint8) === true)) {
        // Si les inscriptions ont débuté et que le cours n'est pas terminé ou n'a pas de date de fin, on sélectionne cette méthode
        // pour devenir l'onglet actif dans la liste des méthodes d'inscription du cours.
        $activeenrolid = $instance->id;
    }

    $enrols[$instance->id] = $enrol;
}

if ($activeenrolid === false) {
    $activeenrolid = $instance->id;
}

// On récupère l'état de paiement des utilisateurs du cours.
$payments = Payment::get_users_cards_status_per_course($course->id);
$paymentspix = Payment::get_statuses_images();

// On récupère tous les types de calendriers.
$calendartypes = $DB->get_records('apsolu_calendars_types');

// On récupère toutes les inscriptions de tous les étudiants inscrits à ce cours.
$sql = 'SELECT u.*, ra.roleid, e.name AS enrolname, e.courseid, ue.enrolid, ue.status, ue.timecreated,
               c.fullname, cc.name AS sport, ac.typeid AS calendartypeid
          FROM {user} u
          JOIN {user_enrolments} ue ON u.id = ue.userid
          JOIN {role_assignments} ra ON u.id = ra.userid AND ra.itemid = ue.enrolid
          JOIN {enrol} e ON e.id = ue.enrolid
     LEFT JOIN {apsolu_calendars} ac ON ac.id = e.customchar1
          JOIN {course} c ON c.id = e.courseid
          JOIN {course_categories} cc ON cc.id = c.category
         WHERE u.deleted = 0
           AND e.enrol = "select"
           AND e.status = 0
           AND u.id IN (SELECT ue.userid
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON e.id = ue.enrolid
                         WHERE e.enrol = "select"
                           AND e.courseid = :courseid
               )
      ORDER BY ue.timecreated, u.lastname, u.firstname';
$users = [];
$recordset = $DB->get_recordset_sql($sql, ['courseid' => $course->id]);
foreach ($recordset as $record) {
    if (isset($roles[$record->roleid]) === false) {
        continue;
    }

    if (isset($users[$record->id]) === false) {
        // On initialise le profile utilisateur (photo, inscriptions, etc).
        $record->picture = $OUTPUT->user_picture($record, ['size' => 30, 'courseid' => $course->id]);
        $record->accepted_enrolments = [];
        $record->count_accepted_enrolments = 0;
        $record->other_enrolments = [];
        $record->count_other_enrolments = 0;
        $record->payments = [];
        $record->count_payments = 0;

        if (isset($payments[$record->id]) === true) {
            foreach ($payments[$record->id] as $payment) {
                $record->payments[] = $paymentspix[$payment->status]->image . ' ' . $payment->name;
                $record->count_payments++;
            }
        }

        $record->extrafields = [];
        if ($extrafields !== []) {
            $customfields = profile_user_record($record->id);
            foreach ($extrafields as $extrafield => $unused) {
                if (isset($record->$extrafield) === true) {
                    $record->extrafields[] = $record->$extrafield;
                } else if (isset($customfields->$extrafield) === true) {
                    $record->extrafields[] = $customfields->$extrafield;
                }
            }
        }

        $users[$record->id] = $record;
    }

    $enrolment = new stdClass();
    $enrolment->fullname = $record->fullname;
    $enrolment->sport = $record->sport;
    $enrolment->role = $roles[$record->roleid]->name;

    if (isset($calendartypes[$record->calendartypeid]->shortname) === true) {
        $enrolment->enrolname = $calendartypes[$record->calendartypeid]->shortname;
    } else {
        $enrolment->enrolname = $record->enrolname;
    }

    $enrolment->state = get_string(enrol_select_plugin::$states[$record->status] . '_list_abbr', 'enrol_select');
    $enrolment->status = $record->status;
    $enrolment->timecreated = userdate($record->timecreated, '%a %d %b à %T');
    $enrolment->datecreated_sortable = userdate($record->timecreated, '%F');
    $enrolment->timecreated_sortable = userdate($record->timecreated, '%T');
    if ($ismanager === false) {
        $enrolment->course_url = '';
    } else {
        $enrolment->course_url = new moodle_url('/course/view.php', ['id' => $record->courseid]);
    }

    if ($enrolment->status === enrol_select_plugin::ACCEPTED) {
        $users[$record->id]->accepted_enrolments[$record->enrolid] = $enrolment;
        $users[$record->id]->count_accepted_enrolments++;
    } else {
        $users[$record->id]->other_enrolments[$record->enrolid] = $enrolment;
        $users[$record->id]->count_other_enrolments++;
    }
}
$recordset->close();

// On affecte chaque utilisateur dans le ou les méthodes d'inscription auxquelles il est inscrit.
foreach ($users as $user) {
    $enrolments = $user->accepted_enrolments + $user->other_enrolments;
    $user->accepted_enrolments = array_values($user->accepted_enrolments);
    $user->other_enrolments = array_values($user->other_enrolments);

    foreach ($enrolments as $enrolid => $enrolment) {
        if (isset($enrols[$enrolid]) === false) {
            continue;
        }

        // On stocke le rôle et la date d'inscription pour cet utilisateur.
        $togglegroup = 'enrol-' . $enrolid . '-status-' . $enrolment->status . '-togglegroup';
        $checkbox = new \core\output\checkbox_toggleall($togglegroup, $ismaster = false, [
            'classes' => 'apsolu-select-manage-users-input-checkbox usercheckbox m-1',
            'id' => 'enrol-' . $enrolid . '-list-' . $enrolment->status . '-' . $user->id,
            'name' => 'users[]',
            'value' => $user->id,
            'checked' => false,
            'label' => get_string('selectitem', 'moodle', fullname($user)),
            'labelclasses' => 'accesshide',
            ]);

        $user->checkbox = $OUTPUT->render($checkbox);

        $user->role = $enrolment->role;
        $user->timecreated = $enrolment->timecreated;
        $user->timecreated_sortable = $enrolment->timecreated_sortable;
        $user->datecreated_sortable = $enrolment->datecreated_sortable;

        $enrols[$enrolid]->lists[$enrolment->status]->users[] = clone $user;
        $enrols[$enrolid]->lists[$enrolment->status]->count_users++;
    }
}

foreach ($enrols as $enrolid => $enrol) {
    $enrols[$enrolid]->id = $enrolid;
    $enrols[$enrolid]->lists = array_values($enrol->lists);
}

$data->enrols = array_values($enrols);

$PAGE->set_url('/enrol/select/manage.php', ['enrolid' => $instance->id]);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('manage_select_enrolments', 'enrol_select'));
$PAGE->set_heading($course->fullname);

$pluginname = get_string('pluginname', 'enrol_select');

$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add(get_string('enrolmentinstances', 'enrol'), new moodle_url('/enrol/instances.php', ['id' => $course->id]));
$PAGE->navbar->add($pluginname);

$options = [];
$options['sortLocaleCompare'] = true;
$options['widthFixed'] = true;
$options['widgets'] = ['filter', 'stickyHeaders'];
$options['widgetOptions'] = ['stickyHeaders_filteredToTop' => true, 'stickyHeaders_offset' => '50px'];
$PAGE->requires->js_call_amd('local_apsolu/sort', 'initialise', [$options]);

$PAGE->requires->js_call_amd('enrol_select/select_manage_user_selection', 'initialise', [$activeenrolid]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_select_enrolments', 'enrol_select'));

if (isset($notification)) {
    echo $notification;
}

echo $OUTPUT->render_from_template('enrol_select/manage', $data);

echo $OUTPUT->footer();
