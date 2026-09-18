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
 * Page gérant les inscriptions des étudiants.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\course;
use local_apsolu\core\federation\activity;
use local_apsolu\core\federation\course as FederationCourse;
use UniversiteRennes2\Apsolu\Payment;

define('APSOLU_FEDERATION_REQUIREMENT_FALSE', 0);
define('APSOLU_FEDERATION_REQUIREMENT_TRUE', 1);
define('APSOLU_FEDERATION_REQUIREMENT_OPTIONAL', 2);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/enrol_form.php');
require_once(__DIR__ . '/../locallib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/enrol/select/lib.php');
require_once($CFG->dirroot . '/local/apsolu/classes/apsolu/payment.php');
require_once($CFG->dirroot . '/local/apsolu/locallib.php');

// Get params.
$enrolid = required_param('enrolid', PARAM_INT);
$edit = optional_param('editenrol', null, PARAM_TEXT);
$ajax = optional_param('ajax', false, PARAM_BOOL);
$filtertime = null;
$filtercohorts = null;

$capabilities = [
    'moodle/category:manage',
    'moodle/course:create',
];

if (has_any_capability($capabilities, context_system::instance()) === true) {
    $filtertime = optional_param('time', null, PARAM_INT);
    $filtercohorts = optional_param('cohorts', '', PARAM_TEXT);

    $filtercohorts = explode(',', $filtercohorts);
    if (count($filtercohorts) === 0) {
        $filtertime = null;
        $filtercohorts = null;
    }
}

require_login($courseorid = null, $autologinguest = false);

$context = context_user::instance($USER->id);

$PAGE->set_url('/enrol/select/overview/enrol.php');
$PAGE->set_pagelayout('base');

$PAGE->set_context($context);

$enrol = $DB->get_record('enrol', ['enrol' => 'select', 'status' => 0, 'id' => $enrolid], '*', MUST_EXIST);

$federation = new FederationCourse();
$federationcourseid = $federation->get_courseid();

$course = Course::get_record(['id' => $enrol->courseid], $fields = '*', $strictness = MUST_EXIST);

$instance = new stdClass();
$instance->fullname = $course->fullname;
$instance->enrolid = $enrol->id;
$instance->showpolicy = ($course->customfields['show_policy']->get_value() === 1);

if (isset($course->customfields['location']) === true) {
    $location = $course->customfields['location']->export_value();

    preg_match('/^(\[.*\])(.+)/', $location, $matches);
    // Many cities : location + area.
    if (isset($matches[1]) === true) {
        $instance->site = $matches[1];
        $instance->location = $matches[2];
    } else {
        $instance->location = $location;
    }
}


// Détermine si l'utilisateur courant est déjà inscrit à ce cours.
// TODO: à modifer...
$instance->role = '';
foreach (enrol_select_get_potential_user_roles($userid = null, $enrol->courseid) as $role) {
    $instance->role = $role->id;
}

$federations = [];
$federationrequirement = APSOLU_FEDERATION_REQUIREMENT_FALSE;

// Vérifie que le cours est ouvert à cet utilisateur.
// TODO: vérifier que les inscriptions sont en cours...

// L'utilisateur n'est pas inscrit à ce cours...
$enrolselectplugin = new enrol_select_plugin();
if ($instance->role === '') {
    // Est-ce que le cours est plein ?
    $status = $enrolselectplugin->get_available_status($enrol, $USER);
    if ($status === false) {
        // Le cours est plein...
        throw new moodle_exception('error_no_left_slot', 'enrol_select');
    }

    // Est-ce que l'utilisateur n'a pas dépassé son quota de voeux...
    $userchoices = enrol_select_get_sum_user_choices($userid = null, $count = true);
    $unavailableuserroles = [];
    foreach ($userchoices as $choice) {
        if ($choice->maxwish == 0 || $choice->count >= $choice->maxwish) {
            $unavailableuserroles[$choice->roleid] = $choice->roleid;
        }
    }

    if (isset($filtertime, $filtercohorts) === false) {
        // Pour un étudiant.
        $availableuserroles = $enrolselectplugin->get_available_user_roles($enrol, $USER->id);
    } else {
        // Pour un gestionnaire qui utiliserait les filtres.
        $sql = "SELECT DISTINCT r.*
                  FROM {role} r
                  JOIN {apsolu_colleges} ac ON r.id = ac.roleid
                  JOIN {apsolu_colleges_members} acm ON ac.id = acm.collegeid
                 WHERE acm.cohortid IN (" . substr(str_repeat('?,', count($filtercohorts)), 0, -1) . ")
              ORDER BY r.sortorder";
        $availableuserroles = role_fix_names($DB->get_records_sql($sql, $filtercohorts));

        // Collèges.
        $unavailableuserroles = enrol_select_get_custom_student_roles();
        foreach ($availableuserroles as $role) {
            unset($unavailableuserroles[$role->id]);
        }
    }

    $courseroles = $DB->get_records('enrol_select_roles', ['enrolid' => $enrol->id], '', 'roleid');
    $roles = [];
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
            $role = $DB->get_record('role', ['id' => $instance->role]);
            throw new moodle_exception('error_reach_wishes_role_limit', 'enrol_select', '', $role->name);
        } else {
            throw new moodle_exception('error_reach_wishes_limit', 'enrol_select');
        }
    }
} else {
    // Si l'utilisateur est déjà inscrit à ce cours.
    $roles = $enrolselectplugin->get_available_user_roles($enrol, $USER->id);

    foreach ($roles as $roleid => $role) {
        if ($enrolselectplugin->can_enrol($enrol, $USER, $roleid) === false && $roleid != $instance->role) {
            unset($roles[$roleid]);
            continue;
        }

        $roles[$roleid] = $role->localname;
    }
}

// Détermine si il possible/obligatoire de s'inscrire à la FFSU.
if (isset($course->customfields['federation']) === true && $course->customfields['federation']->get_value() === 1) {
    // FFSU obligatoire.
    $federationrequirement = APSOLU_FEDERATION_REQUIREMENT_TRUE;
    $instance->federation = 1;
} else {
    $category = $DB->get_record('apsolu_federation_activities', ['categoryid' => $course->category]);
    if ($category === false) {
        // FFSU non disponible.
        $federationrequirement = APSOLU_FEDERATION_REQUIREMENT_FALSE;
    } else {
        // FFSU facultatif.
        $federationrequirement = APSOLU_FEDERATION_REQUIREMENT_OPTIONAL;
    }
}

if (isset($edit)) {
    $instance->edit = true;
}

// Build form.
$customdata = [$instance, $roles, $federationrequirement];
$actionurl = $CFG->wwwroot . '/enrol/select/overview/enrol.php?enrolid=' . $enrolid;
$mform = new enrol_select_form($actionurl, $customdata);

// Formulaire sur une page indépendante appelée directement via l'url.
if (!$ajax) {
    $PAGE->navbar->add(get_string('enrolment', 'enrol_select'), new moodle_url('/enrol/select/overview.php'));
    $PAGE->navbar->add($instance->fullname);
    echo $OUTPUT->header();
}

if (($data = $mform->get_data()) && !isset($instance->edit)) {
    // Save data.
    $instance = $enrol;

    $result = new stdClass();
    $result->wwwroot = $CFG->wwwroot;

    if (isset($data->unenrolbutton)) {
        // Unenrol.
        $enrolselectplugin->unenrol_user($instance, $USER->id);
        $result->unenroled = true;
    } else {
        // Enrol.
        if (ctype_digit((string) $data->role) === false) {
            throw new moodle_exception('error_cannot_enrol', 'enrol_select');
        } else if ($enrolselectplugin->can_enrol($instance, $USER, $data->role)) {
            $timestart = time();
            $timeend = 0;
            $status = $enrolselectplugin->get_available_status($instance, $USER);
            if ($status === false) {
                throw new moodle_exception('error_no_left_slot', 'enrol_select');
            }

            $recovergrades = null;
            $enrolselectplugin->enrol_user($instance, $USER->id, $data->role, $timestart, $timeend, $status, $recovergrades);

            if (
                $federationrequirement === APSOLU_FEDERATION_REQUIREMENT_TRUE ||
                ($federationrequirement === APSOLU_FEDERATION_REQUIREMENT_OPTIONAL &&
                isset($data->federation) && $data->federation === '1')
            ) {
                $data->federation = $course->category;

                // Récupère la liste des activités FFSU.
                $federations = [];
                foreach (Activity::get_records() as $federation) {
                    $federations[$federation->id] = $federation->name;
                }

                $federationinstance = false;
                if ($federationcourseid !== false) {
                    $conditions = ['enrol' => 'select', 'status' => 0, 'courseid' => $federationcourseid];
                    $federationinstance = $DB->get_record('enrol', $conditions);
                }

                if ($federationcourseid === false || $federationinstance === false) {
                    // Do not process.
                    unset($data->federation);
                } else {
                    $conditions = ['enrolid' => $federationinstance->id];
                    $federationrole = $DB->get_record('enrol_select_roles', $conditions, '*', MUST_EXIST);
                    $enrolselectplugin->enrol_user(
                        $federationinstance,
                        $USER->id,
                        $federationrole->roleid,
                        $timestart = 0,
                        $timeend = 0,
                        $status = 0,
                        $recovergrades
                    );
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
                        } else {
                            $ismembersomewhere = true;
                        }
                    }
                }

                if ($ismember === false && $ismembersomewhere === false) {
                    groups_add_member($group->id, $USER->id);
                }
            }

            $result->enroled = true;
            $result->enroledstyle = 'success';

            $result->enroledmessage = get_string('your_wish_has_been_registered', 'enrol_select');
            switch ($status) {
                case enrol_select_plugin::MAIN:
                    $result->enroledlist = get_string_on_list_x(enrol_select_plugin::MAIN, 'you_are_on_listname_X');
                    break;
                case enrol_select_plugin::WAIT:
                    $result->enroledstyle = 'warning';
                    $result->enroledlist = get_string_on_list_x(enrol_select_plugin::WAIT, 'you_are_on_listname_X');
                    break;
                case enrol_select_plugin::ACCEPTED:
                    $result->enroledmessage = get_string('your_enrolment_has_been_registered', 'enrol_select');
                    break;
                default:
            }

            if (
                isset($CFG->is_siuaps_rennes) === true &&
                in_array($data->role, ['9', '10'], true) === true &&
                in_array($status, [enrol_select_plugin::MAIN, enrol_select_plugin::ACCEPTED], true) === true
            ) {
                $result->complementaryinfo =
                    'Attention il faut aussi faire votre inscription pédagogique dans votre scolarité';
            }

            // Détermine si les délais sont activés sur la méthode d'inscription et que l'utilisateur est accepté.
            $result->countpayments = 0;
            $instance->customdec1 = intval($instance->customdec1);
            if (empty($instance->customdec1) === false && $status === enrol_select_plugin::ACCEPTED) {
                // Calcule si au moins une carte est due et affiche un message d'avertissement.
                foreach (Payment::get_user_cards_status_per_course($course->id, $USER->id) as $card) {
                    if ($card->status !== Payment::DUE) {
                        continue;
                    }

                    $functionalcontact = get_config('local_apsolu', 'functional_contact');
                    $params = ['deadline' => format_time($instance->customdec1), 'contact' => $functionalcontact];
                    $result->paymentmessage = get_string('payment_deadline_warning', 'enrol_select', $params);

                    $result->countpayments++;
                    break;
                }
            }

            if (empty($course->information) === false) {
                // Affiche une information complémentaire.
                $context = context_course::instance($course->id);
                $result->courseinformation = file_rewrite_pluginfile_urls(
                    $course->information,
                    'pluginfile.php',
                    $context->id,
                    'local_apsolu',
                    'information',
                    $course->id
                );
            }
        } else {
            throw new moodle_exception('error_cannot_enrol', 'enrol_select');
        }
    }

    // Générer le template du récapitulatif de l'inscription.
    $html = $OUTPUT->render_from_template('enrol_select/overview_enrolment_result', $result);

    if ($ajax) {
        echo json_encode([
            'success' => true,
            'html'    => $html,
            'error'   => null,
            'unenrol' => isset($data->unenrolbutton),
        ]);
        exit;
    }

    echo $html;
} else {
    if ($ajax) {
        echo json_encode([
            'success' => true,
            'html'    => $mform->render(),
            'error'   => null,
        ]);
        exit;
    }

    // Display form.
    $mform->display();
}

echo $OUTPUT->footer();
