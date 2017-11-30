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

namespace UniversiteRennes2\Apsolu;

/**
 * Renvoie tous les groupements d'activités visibles (Sports de raquettes, sports aquatiques, etc)
 * @return array
 */
function get_visible_activities_domains() {
    global $DB;

    $sql = "SELECT *".
        " FROM {course_categories} cc".
        " JOIN {apsolu_courses_groupings} acg ON cc.id = acg.id".
        " WHERE cc.visible = 1".
        " ORDER BY cc.name";
    return $DB->get_records_sql($sql);
}

/**
 * Renvoie toutes les activités visibles (Tennis, Natation, etc)
 * @return array
 */
function get_visible_sports() {
    global $DB;

    $sql = "SELECT *".
        " FROM {course_categories} cc".
        " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id".
        " WHERE cc.visible = 1".
        " ORDER BY cc.name";
    return $DB->get_records_sql($sql);
}

/**
 * Renvoie toutes les activités complémentaires visibles (Musculation, FFSU, etc)
 * @return array
 */
function get_visible_complements() {
    global $DB;

    $sql = "SELECT *, FORMAT(ac.price, 2, 'fr_FR') AS price".
        " FROM {course} c".
        " JOIN {apsolu_complements} ac ON c.id = ac.id".
        " WHERE c.visible = 1".
        " ORDER BY c.fullname";
    return $DB->get_records_sql($sql);
}

/**
 * Renvoie tous les rôles basés sur le type STUDENT (sauf le rôle student de base)
 * @return array
 */
function get_custom_student_roles() {
    global $DB;

    $roles = role_fix_names($DB->get_records('role', array('archetype' => 'student'), 'sortorder'));
    unset($roles[5]);

    return $roles;
}

/**
 * Renvoie toutes les activités dans lesquelles un utilisateur est inscrit.
 * @param int userid (si null, on prend l'id de l'utilisateur courant)
 * @return array
 */
function get_user_activity_enrolments($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $time = time();

    $sql = "SELECT DISTINCT c.*, cc.name AS sport, FORMAT(acol.userprice, 2) AS price, ac.paymentcenterid,".
        " e.id AS enrolid, ue.status, ra.roleid".
        " FROM {course} c".
        " JOIN {course_categories} cc ON cc.id = c.category".
        " JOIN {apsolu_courses} ac ON c.id=ac.id".
        // Check cohorts.
        " JOIN {enrol} e ON c.id = e.courseid".
        " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid".
        " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid".
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = cm.userid".
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id".
        " JOIN {apsolu_colleges} acol ON acol.roleid = ra.roleid".
        " JOIN {apsolu_colleges_members} acm ON acol.id = acm.collegeid AND acm.cohortid = cm.cohortid".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND cm.userid = :userid".
        " AND c.visible = 1".
        " AND (ue.timestart = 0 OR ue.timestart <= :timestart)".
        " AND (ue.timeend = 0 OR ue.timeend >= :timeend)".
        " ORDER BY c.fullname";
    return $DB->get_records_sql($sql, array('userid' => $userid, 'timestart' => $time, 'timeend' => $time));
}

/**
 * Renvoie toutes les activités dans lesquelles un utilisateur est inscrit (sans vérifier les cohortes).
 * @param int userid (si null, on prend l'id de l'utilisateur courant)
 * @return array
 */
function get_real_user_activity_enrolments($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $time = time();

    $sql = "SELECT DISTINCT c.*, cc.name AS sport, e.id AS enrolid, ue.status, ra.roleid, ac.paymentcenterid".
        " FROM {course} c".
        " JOIN {course_categories} cc ON cc.id = c.category".
        " JOIN {apsolu_courses} ac ON c.id = ac.id".
        " JOIN {enrol} e ON c.id = e.courseid".
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid".
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.itemid = e.id".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND c.visible = 1".
        " AND ue.userid = :userid".
        " AND e.enrolstartdate <= :timestart". // Date de début des inscriptions.
        " AND e.customint8 >= :timeend". // Date de fin des cours.
        " ORDER BY c.fullname";
    return $DB->get_records_sql($sql, array('userid' => $userid, 'timestart' => $time, 'timeend' => $time));
}

/**
 * Renvoie toutes les activités dans lesquelles un utilisateur est inscrit (sans vérifier les cohortes).
 * @param int userid (si null, on prend l'id de l'utilisateur courant)
 * @return array
 */
function get_recordset_user_activity_enrolments($userid = null, $onlyactive = true) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if ($onlyactive === true) {
        $time = time();
    }

    $params = array('userid' => $userid);
    $sql = "SELECT DISTINCT c.*, cc.name AS sport, e.id AS enrolid, e.name AS enrolname, ue.status, ra.roleid, ac.paymentcenterid".
        " FROM {course} c".
        " JOIN {course_categories} cc ON cc.id = c.category".
        " JOIN {apsolu_courses} ac ON c.id = ac.id".
        " JOIN {enrol} e ON c.id = e.courseid".
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid".
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.itemid = e.id".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND c.visible = 1".
        " AND ue.userid = :userid";

    if ($onlyactive === true) {
        $sql .= " AND (ue.timestart = 0 OR ue.timestart <= :timestart)".
            " AND (ue.timeend = 0 OR ue.timeend >= :timeend)";
        $params['timestart'] = $time;
        $params['timeend'] = $time;
    }

    $sql .= " ORDER BY c.fullname";

    return $DB->get_recordset_sql($sql, $params);
}

/**
 * Renvoie toutes les activités dans lesquelles un utilisateur est inscrit (avec les informations de paiement).
 * @param int userid (si null, on prend l'id de l'utilisateur courant)
 * @return array
 */
function get_user_activity_enrolments_for_payment($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT DISTINCT c.*, cc.name AS sport, FORMAT(acol.userprice, 2) AS price, ac.paymentcenterid,".
        " e.id AS enrolid, ue.status, ra.roleid".
        " FROM {course} c".
        " JOIN {course_categories} cc ON cc.id = c.category".
        " JOIN {apsolu_courses} ac ON c.id=ac.id".
        " JOIN {enrol} e ON c.id = e.courseid".
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid".
        " JOIN {cohort_members} cm ON cm.userid = ue.userid".
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid=c.id".
        " JOIN {apsolu_colleges} acol ON acol.roleid = ra.roleid".
        " JOIN {apsolu_colleges_members} acm ON acol.id = acm.collegeid AND acm.cohortid = cm.cohortid".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND ue.userid=?".
        " AND c.visible=1".
        " ORDER BY c.fullname";
    return $DB->get_recordset_sql($sql, array($userid));
}


/**
 * Renvoie toutes les activités complémentaires dans lesquelles un utilisateur est inscrit et validé.
 * @param int userid (si null, on prend l'id de l'utilisateur courant)
 * @return array
 */
function get_user_complement_enrolments($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT DISTINCT c.*, FORMAT(ac.price, 2) AS price, ac.federation, ac.paymentcenterid, e.id AS enrolid, ue.status".
        " FROM {course} c".
        " JOIN {apsolu_complements} ac ON c.id=ac.id".
        // Check cohorts.
        " JOIN {enrol} e ON c.id = e.courseid".
        " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid".
        " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid".
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = cm.userid".
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid=c.id".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND cm.userid=?".
        " AND c.visible=1".
        " ORDER BY c.fullname";

    return $DB->get_records_sql($sql, array($userid));
}

/**
 * Renvoie toutes les activités complémentaires dans lesquelles un utilisateur est inscrit et validé.
 * @param int userid (si null, on prend l'id de l'utilisateur courant)
 * @return array
 */
function get_user_complement_enrolments_for_payment($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT DISTINCT c.*, FORMAT(ac.price, 2) AS price, ac.federation, ac.paymentcenterid, e.id AS enrolid, ue.status, ra.roleid".
        " FROM {course} c".
        " JOIN {apsolu_complements} ac ON c.id=ac.id".
        " JOIN {enrol} e ON c.id = e.courseid".
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid".
        " JOIN {cohort_members} cm ON cm.userid = ue.userid".
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid=c.id".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND cm.userid=?".
        " AND c.visible=1".
        " ORDER BY c.fullname";

    return $DB->get_records_sql($sql, array($userid));
}

/**
 * Renvoie tous les collèges auxquels appartient l'utilisateur (nombre de voeux possibles, roles, prix, etc).
 * @param int userid : si null, on prend l'id de l'utilisateur courant
 * @param bool count : ajoute le nombre de voeux fait par l'utilisateur pour chaque collège
 * @return array
 */
function get_user_colleges($userid = null, $count = false) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT DISTINCT ac.*".
        " FROM {apsolu_colleges} ac".
        // Check cohorts.
        " JOIN {apsolu_colleges_members} acm ON ac.id = acm.collegeid".
        " JOIN {cohort} ct ON ct.id = acm.cohortid".
        " JOIN {cohort_members} cm ON ct.id = cm.cohortid".
        " WHERE cm.userid = ?";
    $colleges = $DB->get_records_sql($sql, array($userid));

    if ($count === true) {
        $countuserroles = get_count_user_role_assignments();
        foreach ($colleges as $college) {
            if (isset($countuserroles[$college->roleid])) {
                $college->count = $countuserroles[$college->roleid]->count;
            } else {
                $college->count = 0;
            }
        }
    }

    return $colleges;
}

/**
 * Renvoie le total d'inscription par rôle d'un utilisateur.
 * @param int userid : si null, on prend l'id de l'utilisateur courant
 * @return array
 */
function get_count_user_role_assignments($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $time = time();

    $sql = "SELECT ra.roleid, COUNT(c.id) AS count".
        " FROM {role_assignments} ra".
        " JOIN {context} ctx ON ctx.id = ra.contextid".
        " JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = 50".
        " JOIN {apsolu_courses} ac ON ac.id = c.id".
        " JOIN {enrol} e ON c.id = e.courseid AND ra.itemid = e.id".
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = ra.userid".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND c.visible = 1".
        " AND ue.userid = :userid".
        " AND e.enrolstartdate <= :timestart". // Date de début des inscriptions.
        " AND e.customint8 >= :timeend". // Date de fin des cours.
        " GROUP BY ra.roleid";
    return $DB->get_records_sql($sql, array('userid' => $userid, 'timestart' => $time, 'timeend' => $time));
}

/**
 * Renvoie tous les rôles auxquels un utilisateur peut prétendre.
 * @param int userid : si null, on prend l'id de l'utilisateur courant
 * @param int courseid : si null, on prend tous les rôles possibles
 * @return array
 */
function get_potential_user_roles($userid = null, $courseid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if ($courseid === null) {
        $sql = "SELECT DISTINCT r.*".
            " FROM {role} r".
            " JOIN {apsolu_colleges} ac ON r.id = ac.roleid".
            " JOIN {apsolu_colleges_members} acm ON ac.id = acm.collegeid".
            " JOIN {cohort_members} cm ON cm.cohortid = acm.cohortid".
            " WHERE cm.userid=?";
        $params = array($userid);
    } else {
        $time = time();

        $sql = "SELECT r.*".
            " FROM {role} r".
            " JOIN {role_assignments} ra ON r.id = ra.roleid".
            " JOIN {context} ctx ON ctx.id = ra.contextid".
            " JOIN {course} c ON c.id = ctx.instanceid".
            " JOIN {enrol} e ON c.id = e.courseid AND ra.itemid = e.id".
            " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = ra.userid".
            " WHERE e.enrol = 'select'".
            " AND e.status = 0". // Active.
            " AND ue.userid = :userid".
            " AND (ue.timestart = 0 OR ue.timestart <= :timestart)".
            " AND (ue.timeend = 0 OR ue.timeend >= :timeend)".
            " AND c.id = :courseid".
            " AND ctx.contextlevel = 50";
        $params = array('userid' => $userid, 'timestart' => $time, 'timeend' => $time, 'courseid' => $courseid);
    }

    $roles = role_fix_names($DB->get_records_sql($sql, $params));

    uasort($roles, function($a, $b) {
        return $a->sortorder > $b->sortorder;
    });

    return $roles;
}

function get_potential_user_activities($manager = false) {
    global $DB, $USER;

    $groupings = get_visible_activities_domains();
    $categories = get_visible_sports();
    $availableuserroles = get_potential_user_roles();
    $skills = $DB->get_records('apsolu_skills');
    $locations = $DB->get_records('apsolu_locations');
    $usercolleges = get_user_colleges($userid = null, $count = true);
    $useractivityenrolments = get_user_activity_enrolments();

    $unavailableuserroles = array();
    foreach ($usercolleges as $college) {
        if ($college->maxwish > 0 && $college->count >= $college->maxwish) {
            $unavailableuserroles[$college->roleid] = $college->roleid;
        }
    }

    $currentactivity = null;

    $now = time();

    $roles = get_custom_student_roles();

    // Récupère toutes les activités.
    $sql = "SELECT DISTINCT c.*, ac.*, cc.id AS sportid, cc.description, grp.id AS groupingid".
        " FROM {course} c".
        " JOIN {apsolu_courses} ac ON c.id=ac.id".
        " JOIN {course_categories} cc ON cc.id=c.category". // Sport category.
        " JOIN {course_categories} grp ON grp.id=cc.parent". // Parent category.
        // Check cohorts.
        " AND c.visible=1".
        " AND cc.visible=1".
        " ORDER BY cc.name, ac.numweekday, ac.starttime, ac.endtime";
    $courses = $DB->get_records_sql($sql);

    // Récupère toutes les méthodes d'inscription valides concernant l'utilisateur courant.
    $sql = "SELECT DISTINCT e.*".
        " FROM {enrol} e".
        " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid".
        " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0".
        " AND (e.enrolstartdate = 0 OR e.enrolstartdate < ?)".
        " AND (e.enrolenddate = 0 OR e.enrolenddate > ?)";
    $params = array($now, $now);

    if ($manager === false) {
        $sql .= " AND cm.userid = ?";
        $params[] = $USER->id;
    }

    $enrols = $DB->get_records_sql($sql, $params);
    foreach ($enrols as $enrol) {
        if (!isset($courses[$enrol->courseid])) {
            continue;
        }

        if (!isset($courses[$enrol->courseid]->enrols)) {
            $courses[$enrol->courseid]->enrols = array();
        }

        $courses[$enrol->courseid]->enrols[] = $enrol;
    }

    foreach ($courses as $courseid => $course) {
        if (!isset($categories[$course->category])) {
            unset($courses[$courseid]);
            continue;
        }

        // L'utilisateur ne semble pas avoir le droit de s'inscrire à ce cours.
        if (!isset($courses[$courseid]->enrols)) {
            unset($courses[$courseid]);
            continue;
        }

        // Il y a trop de méthodes !
        if (isset($courses[$courseid]->enrols[1]) && $manager === false) {
            debugging('Course #'.$course->id.': too much enrol ! (user #'.$USER->id.')');
            unset($courses[$courseid]);
            continue;
        }

        $enrol = $courses[$courseid]->enrols[0];
        $course->enrolid = $enrol->id;
        $course->enrolname = $enrol->name;

        $time = time();

        if ($enrol->customint3 == 1) {
            $sql = "SELECT userid".
                " FROM {user_enrolments}".
                " WHERE enrolid = :enrolid".
                " AND status IN (0, 2)".
                " AND (timestart <= :timestart OR timestart = :startenrol)". // TODO: régler ce problème de date de début !
                " AND (timeend = 0 OR timeend >= :timeend)";
            $mainlistenrolements = $DB->get_records_sql($sql, array('enrolid' => $enrol->id, 'timestart' => $time, 'startenrol' => $enrol->customint7, 'timeend' => $time));

            $course->count_main_list = count($mainlistenrolements);
            $course->max_main_list = $enrol->customint1;
            $course->user_main_list = isset($mainlistenrolements[$USER->id]);
            $countmainslots = $course->max_main_list - $course->count_main_list;
            if ($countmainslots > 1) {
                $course->left_main_list_str = $countmainslots.' places restantes sur liste principale';
            } else {
                $course->left_main_list_str = $countmainslots.' place restante sur liste principale';
            }

            $sql = "SELECT userid".
                " FROM {user_enrolments}".
                " WHERE enrolid = :enrolid".
                " AND status IN (3)".
                " AND (timestart <= :timestart OR timestart = :startenrol)". // TODO: régler ce problème de date de début !
                " AND (timeend = 0 OR timeend >= :timeend)";
            $waitlistenrolements = $DB->get_records_sql($sql, array('enrolid' => $enrol->id, 'timestart' => $time, 'startenrol' => $enrol->customint7, 'timeend' => $time));
            $course->count_wait_list = count($waitlistenrolements);
            $course->max_wait_list = $enrol->customint2;
            $course->user_wait_list = isset($waitlistenrolements[$USER->id]);
            $countwaitslots = $course->max_wait_list - $course->count_wait_list;
            // À quoi sert $course->left_wait_list_str ??? Ça ne semble être utilisé nulle part ailleurs...
            if ($countwaitslots > 1) {
                $course->left_wait_list_str = $countwaitslots.' places restantes sur liste complémentaire';
            } else {
                $course->left_wait_list_str = $countwaitslots.' place restante sur liste complémentaire';
            }

            $course->user_no_list = !$course->user_main_list && !$course->user_wait_list;

            $ismainlistfull = $course->count_main_list >= $course->max_main_list;
            $iswaitlistfull = $course->count_wait_list >= $course->max_wait_list;
            $course->full_registration = $ismainlistfull && $iswaitlistfull;

            if ($course->max_main_list > $course->count_main_list) {
                $count = $course->max_main_list - $course->count_main_list;
                $course->left_places_str = $count.' places restantes sur liste principale';
                $course->left_places_style = 'success';
            } else if ($course->max_wait_list > $course->count_wait_list) {
                $course->left_places_str = 'Il reste des places sur liste complémentaire';
                $course->left_places_style = 'warning';
            } else {
                $course->left_places_str = 'Aucune place disponible';
                $course->left_places_style = 'danger';
            }
        } else {
            // Aucun quota.
            $course->left_places_str = 'Aucune restriction de places';
            $course->left_places_style = 'success';
            $course->full_registration = false;
        }

        // TODO: est-ce que l'utilisateur peut accéder à tous les types ?

        // Récupère tous les rôles acceptés par ce cours.
        $selectroles = $DB->get_records('enrol_select_roles', array('enrolid' => $enrol->id), '', 'roleid');
        $course->role_options = array();
        foreach ($selectroles as $role) {
            if (isset($roles[$role->roleid])) {
                $course->role_options[$role->roleid] = $roles[$role->roleid];
            }
        }

        if ($course->role_options === array()) {
            debugging('Course #'.$course->id.': no role for enrol #'.$enrol->id);
            unset($courses[$courseid]);
            continue;
        } else {
            if (isset($useractivityenrolments[$course->id])) {
                // L'utilisateur est déjà inscrit à ce cours...
                $course->allow_enrolment = true;
                $course->enroled = true;
            } else if ($course->full_registration) {
                // Le cours est plein...
                $course->allow_enrolment = false;
                $course->enroled = false;
            } else {
                $availableuserrolescopy = $availableuserroles;
                foreach ($availableuserroles as $roleid => $rolename) {
                    if (!isset($course->role_options[$roleid])) {
                        // L'utilisateur peut s'inscrire à un type d'inscription qui n'est pas proposé dans ce cours.
                        unset($availableuserrolescopy[$roleid]);
                    } else if (isset($unavailableuserroles[$roleid])) {
                        // L'utilisateur a déjà atteint le quota pour ce type d'inscription.
                        unset($availableuserrolescopy[$roleid]);
                    }
                }

                $course->allow_enrolment = (count($availableuserrolescopy) !== 0);
                $course->enroled = false;
            }
        }

        usort($course->role_options, function($a, $b) {
            return $a->sortorder > $b->sortorder;
        });

        $course->grouping = $groupings[$categories[$course->category]->parent]->name;
        $course->sport = $categories[$course->category]->name;
        $course->weekday_locale = get_string(strtolower($course->weekday), 'calendar');
        $course->skill = $skills[$course->skillid]->name;
        $course->location = $locations[$course->locationid]->name;
        if (isset($locations[$course->locationid]->longitude, $locations[$course->locationid]->latitude)) {
            $course->longitude = $locations[$course->locationid]->longitude;
            $course->latitude = $locations[$course->locationid]->latitude;
        }
    }

    return $courses;
}

function get_potential_user_complements() {
    global $DB, $USER;

    $usercomplementenrolments = get_user_complement_enrolments();

    $now = time();

    $sql = "SELECT DISTINCT c.*, ac.*, format(ac.price, 2, 'fr_FR') AS price".
        " FROM {course} c".
        " JOIN {apsolu_complements} ac ON c.id = ac.id".
        // Check cohorts.
        " JOIN {enrol} e ON c.id = e.courseid".
        " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid".
        " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND (e.enrolstartdate = 0 OR e.enrolstartdate < ?)".
        " AND (e.enrolenddate = 0 OR e.enrolenddate > ?)".
        " AND cm.userid=?".
        " AND c.visible=1".
        " ORDER BY c.fullname";
    $courses = $DB->get_records_sql($sql, array($now, $now, $USER->id));

    foreach ($courses as $index => $course) {
        $enrol = $DB->get_record('enrol', array('enrol' => 'select', 'status' => 0, 'courseid' => $course->id));
        if ($enrol === false) {
            unset($courses[$index]);
            continue;
        }

        $course->enrolid = $enrol->id;
        $course->enrolname = $enrol->name;
        $course->enroled = isset($usercomplementenrolments[$course->id]);
    }

    return $courses;
}

/*
 * Retourne les activités pour lesquelles l'utilisateur peut potentiellement se réinscrire.
 */
function get_user_reenrolments($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $time = time();

    $sql = "SELECT DISTINCT c.*, cc.name AS sport, e.id AS enrolid, ue.status, ra.roleid, ac.paymentcenterid".
        " FROM {course} c".
        " JOIN {course_categories} cc ON cc.id = c.category".
        " JOIN {apsolu_courses} ac ON c.id = ac.id".
        " JOIN {enrol} e ON c.id = e.courseid".
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid".
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.itemid = e.id".
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id".
        " WHERE e.enrol = 'select'".
        " AND e.status = 0". // Active.
        " AND c.visible = 1".
        " AND ue.userid = :userid".
        " AND e.customint6 != 0". // Reenrolement available.
        " AND (e.customint4 = 0 OR e.customint4 <= :timestart)". // Date de début des réinscriptions.
        " AND (e.customint5 = 0 OR e.customint5 >= :timeend)". // Date de fin des réinscriptions.
        " ORDER BY c.fullname";
    return $DB->get_records_sql($sql, array('userid' => $userid, 'timestart' => $time, 'timeend' => $time));
}

function generate_filters($courses = array()) {
    $filters = array();

    $elements = array(
        'category' => array(),
        'sport' => array(),
        'skill' => array(),
        'area' => array(),
        'weekday' => array(),
        'starttime' => array(),
        'endtime' => array(),
        'role' => array(),
    );

    foreach ($courses as $course) {
        // Set elements.
        $elements['category'][$course->groupingid] = $course->grouping;
        $elements['sport'][$course->category] = $course->sport;
        $elements['skill'][$course->skillid] = $course->skill;
        $elements['area'][$course->locationid] = $course->location;
        $elements['weekday'][$course->numweekday] = get_string($course->weekday, 'calendar');
        $starttime = substr($course->starttime, 0, 2).'h';
        $elements['starttime'][$starttime] = $starttime;
        $endtime = substr($course->endtime, 0, 2).'h';
        $elements['endtime'][$endtime] = $endtime;
        $elements['role'] = $elements['role'] + $course->role_options;
    }

    foreach ($elements as $type => $element) {
        if ($type === 'weekday') {
            ksort($element);
        } else if ($type === 'role') {
            $roles = array();
            foreach ($element as $role) {
                $roles[$role->id] = $role->localname;
            }
            $element = $roles;
        } else {
            asort($element);
        }
        $attributes = array(
            'data-column-name' => $type,
            'data-placeholder' => get_string($type, 'local_apsolu_courses'),
            'data-allow-clear' => 'true',
            'style' => 'margin: 0 1em',
            'multiple' => 'true',
            'class' => 'filters'
        );
        $filters[] = \html_writer::select($element, 'filters['.$type.']', $selected = '', $nothing = '', $attributes);
    }

    return $filters;
}
