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
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;

require(__DIR__.'/../../../config.php');
require(__DIR__.'/enrol_form.php');
require(__DIR__.'/../locallib.php');
require_once($CFG->dirroot.'/lib/enrollib.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');

// Get params.
$enrolid = required_param('enrolid', PARAM_INT);
$edit = optional_param('editenrol', null, PARAM_TEXT);

require_login();

$context = context_user::instance($USER->id);

$PAGE->set_url('/enrol/select/overview/enrol.php');
$PAGE->set_pagelayout('course');

$PAGE->set_context($context);

$enrol = $DB->get_record('enrol', array('enrol' => 'select', 'status' => 0, 'id' => $enrolid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $enrol->courseid), '*', MUST_EXIST);

$instance = new stdClass();
$instance->fullname = $course->fullname;
$instance->enrolid = $enrol->id;

$instance->role = '';
foreach (apsolu\get_potential_user_roles($userid = null, $enrol->courseid) as $role) {
    $instance->role = $role->id;
}

$iscomplement = $DB->get_record('apsolu_complements', array('id' => $enrol->courseid));

// Vérifie que le cours est ouvert à cet utilisateur.
if ($instance->role === '') {
    // L'utilisateur n'est pas inscrit à ce cours...
    if (!$iscomplement) {
        // Est-ce que le cours est plein ?
        $sql = "SELECT userid FROM {user_enrolments} WHERE enrolid = ? AND status IN (0, 2)";
        $mainlistenrolements = $DB->get_records_sql($sql, array($enrol->id));
        if ($enrol->customint1 <= count($mainlistenrolements)) {
            $waitlistenrolements = $DB->get_records('user_enrolments', array('enrolid' => $enrol->id, 'status' => 3), '', 'userid');
            if ($enrol->customint2 <= count($waitlistenrolements)) {
                // Le cours est plein...
                print_error('error_no_left_slot', 'enrol_select');
            }
        }

        // Est-ce que l'utilisateur n'a pas dépassé son quota de voeux...
        $userchoices = apsolu\get_user_colleges($userid = null, $count = true);
        $prices = array();
        $unavailableuserroles = array();
        foreach ($userchoices as $choice) {
            if ($choice->count >= $choice->maxwish) {
                $unavailableuserroles[$choice->roleid] = $choice->roleid;
            } else {
                if ($choice->userprice === '0') {
                    $prices[$choice->roleid] = $choice->userprice;
                } else {
                    $prices[$choice->roleid] = money_format('%i', $choice->userprice);
                }
            }
        }

        $availableuserroles = apsolu\get_potential_user_roles();
        $courseroles = $DB->get_records('enrol_select_roles', array('enrolid' => $enrol->id), '', 'roleid');
        $roles = array();
        foreach ($availableuserroles as $roleid => $rolename) {
            if (!isset($courseroles[$roleid])) {
                // L'utilisateur peut s'inscrire à un type d'inscription qui n'est pas proposé dans ce cours.
                unset($availableuserroles[$roleid]);
            } else if (isset($unavailableuserroles[$roleid])) {
                // L'utilisateur a déjà atteint le quota pour ce type d'inscription.
                unset($availableuserroles[$roleid]);
            } else {
                if (isset($prices[$roleid])) {
                    if ($prices[$roleid] === '0') {
                        $roles[$roleid] = get_string('rolename_and_price_free', 'enrol_select', $rolename->name);
                    } else {
                        $params = (object) ['rolename' => $rolename->name, 'price' => $prices[$roleid], 'currency' => '€'];
                        $roles[$roleid] = get_string('rolename_and_price', 'enrol_select', $params);
                    }
                } else {
                    $roles[$roleid] = $rolename->name;
                }
            }
        }

        if (count($availableuserroles) === 0) {
            $role = $DB->get_record('role', array('id' => $instance->role));
            print_error('error_reach_wishes_limit', 'enrol_select', '', $role->name);
        }
    } else {
        // Génère un tableau de type array(5 => 'Étudiant').
        $roles = array(5 => current(role_fix_names(array(5 => $DB->get_record('role', array('id' => 5)))))->localname);
    }
} else {
    // Si l'utilisateur est déjà inscrit à ce cours.

    if (!$iscomplement) {
        $roles = apsolu\get_potential_user_roles();

        foreach ($roles as $roleid => $role) {
            $enrolselectplugin = new enrol_select_plugin();
            if ($enrolselectplugin->can_enrol($enrol, $USER, $roleid) === false) {
                if ($roleid != $instance->role) {
                    unset($roles[$roleid]);
                } else {
                    $roles[$roleid] = $role->localname;
                }
            } else {
                $roles[$roleid] = $role->localname;
            }
        }
    } else {
        // Génère un tableau de type array(5 => 'Étudiant').
        $roles = array(5 => current(role_fix_names(array(5 => $DB->get_record('role', array('id' => 5)))))->localname);
    }
}

if (isset($edit)) {
    $instance->edit = true;
}

// Build form.
$customdata = array($instance, $roles);
$actionurl = $CFG->wwwroot.'/enrol/select/overview/enrol.php?enrolid='.$enrolid;
$mform = new enrol_select_form($actionurl, $customdata);

$PAGE->navbar->add(get_string('enrolment', 'enrol_select'), new moodle_url('/enrol/select/overview.php'));
$PAGE->navbar->add($instance->fullname);

echo $OUTPUT->header();

if (($data = $mform->get_data()) && !isset($instance->edit)) {
    // Save data.
    $instance = $enrol;
    $enrolselectplugin = new enrol_select_plugin();

    if (isset($data->unenrolbutton)) {
        // Unenrol.
        $enrolselectplugin->unenrol_user($instance, $USER->id);

        echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
        if (defined('AJAX_SCRIPT')) {
            echo '<div class="alert alert-success"><p>'.get_string('changessaved').'</p></div>';
        }

        $href = $CFG->wwwroot.'/enrol/select/overview.php';
        echo '<p class="text-center"><a class="btn btn-default apsolu-cancel-a" href="'.$href.'">Continuer</a></p>';
    } else {
        // Enrol.
        if ($enrolselectplugin->can_enrol($instance, $USER, $data->role)) {
            $timestart = time();
            $timeend = 0;
            $status = current($enrolselectplugin->available_status);
            $recovergrades = null;
            $enrolselectplugin->enrol_user($instance, $USER->id, $data->role, $timestart, $timeend, $status, $recovergrades);

            echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
            if (defined('AJAX_SCRIPT')) {
                echo '<div class="alert alert-success"><p>'.get_string('changessaved').'</p></div>';
            }

            $href = $CFG->wwwroot.'/enrol/select/overview.php';
            echo '<p class="text-center"><a class="btn btn-default apsolu-cancel-a" href="'.$href.'">Continuer</a></p>';
        } else {
            print_error('error_cannot_enrol', 'enrol_select');
        }
    }
} else {
    // Display form.
    $mform->display();
}

echo $OUTPUT->footer();
