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

define('APSOLU_FEDERATION_REQUIREMENT_FALSE', 0);
define('APSOLU_FEDERATION_REQUIREMENT_TRUE', 1);
define('APSOLU_FEDERATION_REQUIREMENT_OPTIONAL', 2);

require(__DIR__.'/../../../config.php');
require(__DIR__.'/enrol_form.php');
require(__DIR__.'/../locallib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/lib/enrollib.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');
require_once($CFG->dirroot.'/local/apsolu_payment/locallib.php');

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

$federations = array();
$federationrequirement = APSOLU_FEDERATION_REQUIREMENT_FALSE;
$complement = $DB->get_record('apsolu_complements', array('id' => $enrol->courseid));

// Vérifie que le cours est ouvert à cet utilisateur.
if ($complement !== false) {
    $instance->complement = true;

    if ($complement->federation === '1') {
        $sql = "SELECT cc.id, cc.name".
            " FROM {course_categories} cc".
            " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id".
            " WHERE acc.federation = 1".
            " ORDER BY cc.name";

        $federations = array();
        foreach ($DB->get_records_sql($sql) as $federation) {
            $federations[$federation->id] = $federation->name;
        }

        // Génère un tableau de type array(5 => 'Adhérent de l\'association').
        $roles = array(5 => 'Adhérent de l\'association sportive');
    } else {
        $roles = array(5 => 'Libre accès');
    }
} else {
    $instance->complement = false;

    // TODO: vérifier que les inscriptions sont en cours...

    // L'utilisateur n'est pas inscrit à ce cours...
    if ($instance->role === '') {
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
        // Si l'utilisateur est déjà inscrit à ce cours.
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
    }

    // Détermine si il possible/obligatoire de s'inscrire à la FFSU.
    $apsolucourse = $DB->get_record('apsolu_courses', array('id' => $enrol->courseid));
    if ($apsolucourse->license === '1') {
        // FFSU obligatoire.
        $federationrequirement = APSOLU_FEDERATION_REQUIREMENT_TRUE;
        $instance->federation = 1;
    } else {
        $category = $DB->get_record('apsolu_courses_categories', array('id' => $course->category, 'federation' => 1));
        if ($category === false) {
            // FFSU non disponible.
            $federationrequirement = APSOLU_FEDERATION_REQUIREMENT_FALSE;
        } else {
            // FFSU facultatif.
            $federationrequirement = APSOLU_FEDERATION_REQUIREMENT_OPTIONAL;
        }
    }
}

if (isset($edit)) {
    $instance->edit = true;
}

// Build form.
$customdata = array($instance, $roles, $federations, $federationrequirement);
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
        file_put_contents('/applis/logs/apsolu_enrol.log', strftime('%c').' user: '.$USER->id.', course: '.$instance->courseid.', action: unenrol course'.PHP_EOL, FILE_APPEND);

        echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
        if (defined('AJAX_SCRIPT')) {
            echo '<div class="alert alert-success"><p>'.get_string('changessaved').'</p></div>';
        }

        $href = $CFG->wwwroot.'/enrol/select/overview.php';
        echo '<p class="text-center"><a class="btn btn-default apsolu-cancel-a" href="'.$href.'">Continuer</a></p>';
    } else {
        // Enrol.
        if (ctype_digit((string) $data->role) === false) {
            print_error('error_cannot_enrol', 'enrol_select');
        } else if ($enrolselectplugin->can_enrol($instance, $USER, $data->role)) {
            $timestart = 0;
            $timeend = 0;
            if (in_array($instance->courseid, array(249, 250))) {
                // Pour la musculation et la licence FFSU, on accepte d'office les inscriptions.
                $status = 0;
            } else {
                $enrolselectplugin->set_available_status($instance, $USER);
                $status = current($enrolselectplugin->available_status);
            }
            $recovergrades = null;
            $enrolselectplugin->enrol_user($instance, $USER->id, $data->role, $timestart, $timeend, $status, $recovergrades);
            file_put_contents('/applis/logs/apsolu_enrol.log', strftime('%c').' user: '.$USER->id.', course: '.$instance->courseid.', role: '.$data->role.', action: enrol course'.PHP_EOL, FILE_APPEND);

            if ($federationrequirement === APSOLU_FEDERATION_REQUIREMENT_TRUE ||
                ($federationrequirement === APSOLU_FEDERATION_REQUIREMENT_OPTIONAL &&
                isset($data->federation) && $data->federation === '1')) {

                $data->federation = $course->category;

                $sql = "SELECT cc.id, cc.name".
                    " FROM {course_categories} cc".
                    " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id".
                    " WHERE acc.federation = 1".
                    " ORDER BY cc.name";

                $federations = array();
                foreach ($DB->get_records_sql($sql) as $federation) {
                    $federations[$federation->id] = $federation->name;
                }

                $federationcourse = $DB->get_record('apsolu_complements', array('federation' => 1));
                $conditions = array('enrol' => 'select', 'status' => 0, 'courseid' => $federationcourse->id);
                $federationinstance = $DB->get_record('enrol', $conditions);
                if ($federationcourse === false || $federationinstance === false) {
                    // Do not process.
                    unset($data->federation);
                } else {
                    $federationcourseid = $federationcourse->id;
                    $federationrole = 5; // Student.
                    $enrolselectplugin->enrol_user($federationinstance, $USER->id, $federationrole, $timestart, $timeend, $status, $recovergrades);
                    file_put_contents('/applis/logs/apsolu_enrol.log', strftime('%c').' user: '.$USER->id.', course: '.$federationcourse->id.', action: enrol course (federation)'.PHP_EOL, FILE_APPEND);
                }
            } else if (isset($data->federation)) {
                $federationcourseid = $enrol->courseid;
            }

            if (isset($data->federation, $federations[$data->federation])) {
                // Inscrire dans le groupe de FFSU.
                $group = new stdClass();
                $group->name = $federations[$data->federation];
                $group->courseid = $federationcourseid;
                $group->id = groups_get_group_by_name($group->courseid, $federations[$data->federation]);
                if ($group->id === false) {
                    $group->id = groups_create_group($group);
                }

                $ismember = false;
                $ismembersomewhere = false;
                $groups = groups_get_user_groups($federationcourseid, $USER->id);
                foreach ($groups as $groupsid) {
                    foreach ($groupsid as $groupid) {
                        if ($groupid === $group->id) {
                            $ismember = true;
                        } else if ($federationrequirement === APSOLU_FEDERATION_REQUIREMENT_FALSE) {
                            // On désinscrit uniquement du groupe, si on modifie via le formulaire de la licence FFSU.
                            groups_delete_group_members($group->courseid, $USER->id);
                            file_put_contents('/applis/logs/apsolu_enrol.log', strftime('%c').' user: '.$USER->id.', course: '.$group->courseid.', action: unenrol group #'.$group->id.PHP_EOL, FILE_APPEND);
                        } else {
                            $ismembersomewhere = true;
                        }
                    }
                }

                if ($ismember === false && $ismembersomewhere === false) {
                    groups_add_member($group->id, $USER->id);
                    file_put_contents('/applis/logs/apsolu_enrol.log', strftime('%c').' user: '.$USER->id.', course: '.$group->courseid.', action: enrol group #'.$group->id.PHP_EOL, FILE_APPEND);
                }
            }

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
