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

use local_apsolu\core\customfields as CustomFields;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

$enrolid = optional_param('enrolid', null, PARAM_INT);

if ($enrolid !== null) {
    $instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'select'), '*', MUST_EXIST);
    $courseid = $instance->courseid;
} else {
    $courseid = required_param('courseid', PARAM_INT);
}
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$canenrol = has_capability('enrol/select:enrol', $context);
$canunenrol = has_capability('enrol/select:unenrol', $context);

$ismanager = $DB->get_record('role_assignments', array('contextid' => 1, 'roleid' => 1, 'userid' => $USER->id));

// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.

if (!$canenrol) {
    // No need to invent new error strings here...
    require_capability('enrol/select:enrol', $context);
}

if (!$enrolselect = enrol_get_plugin('select')) {
    throw new coding_exception('Can not instantiate enrol_select');
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->canunenrol = $canunenrol;
$data->enrols = array();

$roles = role_fix_names($DB->get_records('role'));
$instances = $DB->get_records('enrol', array('enrol' => 'select', 'courseid' => $course->id), $sort = 'name');
$customfields = CustomFields::getCustomFields();

$enrols = array();
$semester2 = false;

// Initialise chaque instance du cours utilisant la méthode enrol_select.
foreach ($instances as $instance) {
    $enrol = new stdClass();
    $enrol->name = $enrolselect->get_instance_name($instance);
    $enrol->enrolid = $instance->id;
    $enrol->enrol_user_link = $CFG->wwwroot.'/enrol/select/enrol.php?enrolid='.$instance->id;
    $enrol->unenrol_user_link = $CFG->wwwroot.'/enrol/select/unenrol.php?enrolid='.$instance->id;
    $enrol->lists = array();
    $enrol->lock = ($instance->customint8 < time());
    if ($ismanager !== false || is_siteadmin() === true) {
        // Les gestionnaires et les administrateurs peuvent modifier les inscriptions toute l'année.
        // TODO: créer une permission pour gérer ce point.
        $enrol->lock = false;
    }

    // On initialise chaque liste (LP, LC, etc).
    foreach (enrol_select_plugin::$states as $code => $state) {
        $nextinstance = $DB->get_record('enrol', array('id' => $instance->customint6, 'enrol' => 'select'), '*', IGNORE_MISSING);

        $selectoptions = array();
        $mainoptions   = array();
        $suboptions    = array();
        $otheroptions  = array();

        foreach (enrol_select_plugin::$states as $scode => $sstate) {
            $mainoptions[$scode] = get_string('move_to_'.$sstate, 'enrol_select');
            if ($nextinstance !== false) {
                $suboptions['99'.$scode] = get_string('move_to_next_'.$sstate, 'enrol_select');
            }
        }

        $otheroptions['notify'] = get_string('notify', 'enrol_select');
        $otheroptions['editenroltype'] = get_string('editenroltype', 'enrol_select');
        $otheroptions['changecourse'] = get_string('change_course', 'enrol_select');

        // Current instance.
        unset($mainoptions[$code]);
        $selectoptions[] = array('Déplacement au sein de ' . $instance->name => $mainoptions);

        // Other actions than manage_move.
        $selectoptions[] = array('Autres actions' => $otheroptions);

        // Renewal instance, semester 2.
        if ($nextinstance !== false) {
            $selectoptions[] = array('Réinscription vers ' . $nextinstance->name => $suboptions);
        }

        $list = new stdClass();
        $list->name = get_string($state.'_list', 'enrol_select');
        $list->description = get_string($state.'_description', 'enrol_select');
        $list->status = $code;
        $list->form_action = $CFG->wwwroot.'/enrol/select/manage_handler.php?enrolid='.$instance->id;
        $list->enrol_user_link = $CFG->wwwroot.'/enrol/select/add.php?enrolid='.$instance->id.'&status='.$code;
        $list->users = array();
        $list->count_users = 0;

        switch ($code) {
            case 2:
                $list->max_users = $instance->customint1;
                break;
            case 3:
                $list->max_users = $instance->customint2;
                break;
            default:
                $list->max_users = false;
        }

        $htmlselectattributes = array('id' => 'to-'.$state, 'class' => 'select_options');
        $list->actions = '<p>'.html_writer::tag('label', get_string("withselectedusers"), array('for' => 'to-'.$state)).
            html_writer::select($selectoptions, 'actions', '', array('' => 'choosedots'), $htmlselectattributes).
            html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'from', 'value' => $code)).
            '</p>';

        $enrol->lists[$code] = $list;
    }

    if (time() > $instance->customint8) {
        // Si la date courante est supérieure à au moins une des dates de fin de cours d'une des instances,
        // c'est que nous sommes au 2ème semestre.
        $semester2 = true;
    }

    $enrols[$instance->id] = $enrol;
}

// On récupère toutes les inscriptions de tous les étudiants inscrits à ce cours.
$sql = 'SELECT u.*, ra.roleid, e.name AS enrolname, e.courseid, ue.enrolid, ue.status, ue.timecreated,'.
    ' c.fullname, cc.name AS sport, uid1.data AS apsolucycle'.
    ' FROM {user} u'.
    ' LEFT JOIN {user_info_data} uid1 ON u.id = uid1.userid AND uid1.fieldid = :fieldid'.
    ' JOIN {user_enrolments} ue ON u.id = ue.userid'.
    ' JOIN {role_assignments} ra ON u.id = ra.userid AND ra.itemid = ue.enrolid'.
    ' JOIN {enrol} e ON e.id = ue.enrolid'.
    " JOIN {course} c ON c.id = e.courseid".
    " JOIN {course_categories} cc ON cc.id = c.category".
    ' WHERE u.deleted = 0'.
    ' AND e.enrol = "select"'.
    ' AND e.status = 0'.
    ' AND u.id IN ('.
        ' SELECT ue.userid'.
        ' FROM {user_enrolments} ue'.
        ' JOIN {enrol} e ON e.id = ue.enrolid'.
        ' WHERE e.enrol = "select"'.
        ' AND e.courseid = :courseid'.
    ')'.
    ' ORDER BY ue.timecreated, u.lastname, u.firstname';
$users = array();
$recordset = $DB->get_recordset_sql($sql, array('fieldid' => $customfields['apsolucycle']->id, 'courseid' => $course->id));
foreach ($recordset as $record) {
    if (isset($roles[$record->roleid]) === false) {
        continue;
    }

    if (isset($users[$record->id]) === false) {
        // On initialise le profile utilisateur (photo, inscriptions, etc).
        $record->picture = $OUTPUT->user_picture($record, array('size' => 30, 'courseid' => $course->id));
        $record->enrolments = array();
        $record->count_enrolments = 0;

        $users[$record->id] = $record;
    }

    $enrolment = new stdClass();
    $enrolment->fullname = $record->fullname;
    $enrolment->sport = $record->sport;
    $enrolment->role = $roles[$record->roleid]->localname;

    // TODO: utiliser la nouvelle table.
    if (stripos($record->enrolname, 'semestre 1') !== false) {
        $enrolment->enrolname = 'S1';
    } else if (stripos($record->enrolname, 'semestre 2') !== false) {
        $enrolment->enrolname = 'S2';
    } else {
        $enrolment->enrolname = $record->enrolname;
    }
    $enrolment->state = get_string(enrol_select_plugin::$states[$record->status].'_list_abbr', 'enrol_select');
    $enrolment->status = $record->status;
    $enrolment->role = $roles[$record->roleid]->localname;
    $enrolment->timecreated = userdate($record->timecreated, '%a %d %b à %T');
    $enrolment->timecreated_sortable = userdate($record->timecreated, '%F %T');
    if ($ismanager === false) {
        $enrolment->course_url = '';
    } else {
        $enrolment->course_url = new moodle_url('/course/view.php', array('id' => $record->courseid));
    }

    $users[$record->id]->enrolments[$record->enrolid] = $enrolment;
    $users[$record->id]->count_enrolments++;
}
$recordset->close();

// On affecte chaque utilisateur dans le ou les méthodes d'inscription auxquelles il est inscrit.
foreach ($users as $user) {
    $enrolments = $user->enrolments;
    $user->enrolments = array_values($user->enrolments);

    foreach ($enrolments as $enrolid => $enrolment) {
        if (isset($enrols[$enrolid]) === true) {
            // On stocke le rôle et la date d'inscription pour cet utilisateur.
            $user->role = $enrolment->role;
            $user->timecreated = $enrolment->timecreated;
            $user->timecreated_sortable = $enrolment->timecreated_sortable;

            $enrols[$enrolid]->lists[$enrolment->status]->users[] = clone $user;
            $enrols[$enrolid]->lists[$enrolment->status]->count_users++;
        }
    }
}

foreach ($enrols as $enrolid => $enrol) {
    $enrols[$enrolid]->id = $enrolid;
    $enrols[$enrolid]->lists = array_values($enrol->lists);
}

$data->enrols = array_values($enrols);

$PAGE->set_url('/enrol/select/manage.php', array('enrolid' => $instance->id));
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('manage_select_enrolments', 'enrol_select'));
$PAGE->set_heading($course->fullname);

$pluginname = get_string('pluginname', 'enrol_select');

$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('enrolmentinstances', 'enrol'), new moodle_url('/enrol/instances.php', array('id' => $course->id)));
$PAGE->navbar->add($pluginname);

$PAGE->requires->js_call_amd('enrol_select/select_manage_user_selection', 'initialise', array($semester2));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_select_enrolments', 'enrol_select'));

if (isset($notification)) {
    echo $notification;
}

echo $OUTPUT->render_from_template('enrol_select/manage', $data);

echo $OUTPUT->footer();
