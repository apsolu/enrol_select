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
require_once($CFG->dirroot.'/local/apsolu/locallib.php');

// Get params.
$enrolid = required_param('enrolid', PARAM_INT);
$edit = optional_param('editenrol', null, PARAM_TEXT);
$filtertime = null;
$filtercohorts = null;

$capabilities = array(
    'moodle/category:manage',
    'moodle/course:create',
);

if (has_any_capability($capabilities, context_system::instance()) === true) {
    $filtertime = optional_param('time', null, PARAM_INT);
    $filtercohorts = optional_param('cohorts', '', PARAM_TEXT);

    $filtercohorts = explode(',', $filtercohorts);
    if (count($filtercohorts) === 0) {
        $filtertime = null;
        $filtercohorts = null;
    }
}

require_login();

$context = context_user::instance($USER->id);

$PAGE->set_url('/enrol/select/overview/enrol.php');
$PAGE->set_pagelayout('base');

$PAGE->set_context($context);

$enrol = $DB->get_record('enrol', array('enrol' => 'select', 'status' => 0, 'id' => $enrolid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $enrol->courseid), '*', MUST_EXIST);

$instance = new stdClass();
$instance->fullname = $course->fullname;
$instance->enrolid = $enrol->id;

// Détermine si l'utilisateur courant est déjà inscrit à ce cours.
// TODO: à modifer...
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
        // TODO: ne pas hardcoder le rôle "libre".
        $roles = array(11 => 'Adhérent de l\'association sportive');
    } else {
        $roles = array(11 => 'Libre accès');
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
        $userchoices = apsolu\get_sum_user_choices($userid = null, $count = true);
        $unavailableuserroles = array();
        foreach ($userchoices as $choice) {
            if ($choice->maxwish > 0 && $choice->count >= $choice->maxwish) {
                $unavailableuserroles[$choice->roleid] = $choice->roleid;
            }
        }

        $enrolselectplugin = new enrol_select_plugin(); // TODO: factoriser, et ne déclarer qu'une seule fois cette variable.
        if (isset($filtertime, $filtercohorts) === false) {
            // Pour un étudiant.
            $availableuserroles = $enrolselectplugin->get_available_user_roles($enrol, $USER->id);

        } else {
            // Pour un gestionnaire qui utiliserait les filtres.
            $sql = "SELECT DISTINCT r.*".
                " FROM {role} r".
                " JOIN {apsolu_colleges} ac ON r.id = ac.roleid".
                " JOIN {apsolu_colleges_members} acm ON ac.id = acm.collegeid".
                " WHERE acm.cohortid IN (".substr(str_repeat('?,', count($filtercohorts)), 0, -1).")";
            $availableuserroles = role_fix_names($DB->get_records_sql($sql, $filtercohorts));

            // Collèges.
            $unavailableuserroles = apsolu\get_custom_student_roles();
            foreach ($availableuserroles as $role) {
                unset($unavailableuserroles[$role->id]);
            }
        }

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
                $roles[$roleid] = $rolename->name;
            }
        }

        if (count($availableuserroles) === 0) {
            if (empty($instance->role) === false) {
                $role = $DB->get_record('role', array('id' => $instance->role));
                print_error('error_reach_wishes_role_limit', 'enrol_select', '', $role->name);
            } else {
                print_error('error_reach_wishes_limit', 'enrol_select');
            }
        }
    } else {
        // Si l'utilisateur est déjà inscrit à ce cours.
        $enrolselectplugin = new enrol_select_plugin(); // TODO: factoriser, et ne déclarer qu'une seule fois cette variable.
        $roles = $enrolselectplugin->get_available_user_roles($enrol, $USER->id);

        foreach ($roles as $roleid => $role) {
            $enrolselectplugin = new enrol_select_plugin(); // TODO: à sortir de la boucle.

            if ($enrolselectplugin->can_enrol($enrol, $USER, $roleid) === false) { // TODO: factoriser pour rendre la lecture plus facile...
                if ($roleid != $instance->role) {
                    unset($roles[$roleid]);
                } else {
                    // Affiche le rôle déjà affecté, même si l'étudiant n'est pas autorisé à avoir ce rôle.
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
        // file_put_contents('/applis/logs/apsolu_enrol.log', strftime('%c').' user: '.$USER->id.', course: '.$instance->courseid.', action: unenrol course'.PHP_EOL, FILE_APPEND);

        echo '<div class="alert alert-success"><p>'.get_string('unenrolmentsaved', 'enrol_select').'</p></div>';

        $href = $CFG->wwwroot.'/enrol/select/overview.php';
        echo '<p class="text-center"><a class="btn btn-default apsolu-cancel-a" href="'.$href.'">'.get_string('continue').'</a></p>';
    } else {
        // Enrol.
        if (ctype_digit((string) $data->role) === false) {
            print_error('error_cannot_enrol', 'enrol_select');
        } else if ($enrolselectplugin->can_enrol($instance, $USER, $data->role)) {
            $timestart = time();
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
            // file_put_contents('/applis/logs/apsolu_enrol.log', strftime('%c').' user: '.$USER->id.', course: '.$instance->courseid.', role: '.$data->role.', action: enrol course'.PHP_EOL, FILE_APPEND);

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
                    $federationrole = 11; // Libre.
                    $enrolselectplugin->enrol_user($federationinstance, $USER->id, $federationrole, $timestart = 0, $timeend = 0, $status = 0, $recovergrades);
                    // file_put_contents('/applis/logs/apsolu_enrol.log', strftime('%c').' user: '.$USER->id.', course: '.$federationcourse->id.', action: enrol course (federation)'.PHP_EOL, FILE_APPEND);
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
                            // file_put_contents('/applis/logs/apsolu_enrol.log', strftime('%c').' user: '.$USER->id.', course: '.$group->courseid.', action: unenrol group #'.$group->id.PHP_EOL, FILE_APPEND);
                        } else {
                            $ismembersomewhere = true;
                        }
                    }
                }

                if ($ismember === false && $ismembersomewhere === false) {
                    groups_add_member($group->id, $USER->id);
                    // file_put_contents('/applis/logs/apsolu_enrol.log', strftime('%c').' user: '.$USER->id.', course: '.$group->courseid.', action: enrol group #'.$group->id.PHP_EOL, FILE_APPEND);
                }
            }

            $message = get_string('enrolmentsaved', 'enrol_select');
            if (isset($CFG->is_siuaps_rennes) === true && in_array($data->role, array('9', '10'), true) === true) {
                $message .= '<br /><strong>Attention il faut aussi faire votre inscription pédagogique dans votre scolarité.</strong>';
            }

            echo '<div class="alert alert-success text-center"><p>'.$message.'</p></div>';

            $href = $CFG->wwwroot.'/enrol/select/overview.php';
            echo '<p class="text-center"><a class="btn btn-default apsolu-cancel-a" href="'.$href.'">'.get_string('continue').'</a></p>';
        } else {
            print_error('error_cannot_enrol', 'enrol_select');
        }
    }
} else {
    // Display form.
    $mform->display();
}

echo $OUTPUT->footer();
