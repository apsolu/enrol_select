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
 * Fonctions pour le module enrol_select.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Une fonction à documenter (TODO).
 *
 * @param int  $siteid       Identifiant du site de pratique.
 * @param int  $categoryid   Identifiant de l'activité physique.
 * @param int  $categoryname Nom de l'activité physique.
 * @param bool $onhomepage   Témoin indique si l'activité est visible sur la page d'accueil.
 *
 * @return array Un tableau d'activités.
 */
function enrol_select_get_activities($siteid = 0, $categoryid = 0, $categoryname = '', $onhomepage = true) {
    global $DB;

    $params = [];
    $conditions = [];

    if (empty($siteid) === false) {
        $params['siteid'] = $siteid;
        $conditions[] = " AND aci.id = :siteid";
    }

    if (empty($categoryid) === false) {
        $params['categoryid'] = $categoryid;
        $conditions[] = " AND cc.id = :categoryid";
    }

    if (empty($categoryname) === false) {
        $params['categoryname'] = $categoryname;
        $conditions[] = " AND cc.name LIKE :categoryname";
    }

    if ($onhomepage !== null) {
        $params['on_homepage'] = intval($onhomepage);
        $conditions[] = " AND ac.on_homepage = :on_homepage";
    }

    $sql = "SELECT c.id, c.fullname, ac.event, ac.weekday, ac.starttime, ac.endtime," .
        " cc0.id AS domainid, cc0.name AS domain, cc.id AS sportid, cc.name AS sport, acc.url, cc.description," .
        " ac.skillid, ask.name AS skill, ac.locationid, al.name AS location, aa.name AS area," .
        " aci.name AS site, ac.periodid, ap.generic_name" .
        " FROM {course} c" .
        " JOIN {apsolu_courses} ac ON c.id = ac.id" .
        " JOIN {course_categories} cc ON cc.id = c.category" .
        " JOIN {apsolu_courses_categories} acc ON acc.id = cc.id" .
        " JOIN {course_categories} cc0 ON cc0.id = cc.parent" .
        " JOIN {apsolu_skills} ask ON ask.id = ac.skillid" .
        " JOIN {apsolu_locations} al ON al.id = ac.locationid" .
        " JOIN {apsolu_areas} aa ON aa.id = al.areaid" .
        " JOIN {apsolu_cities} aci ON aci.id = aa.cityid" .
        " JOIN {apsolu_periods} ap ON ap.id = ac.periodid" .
        " WHERE cc0.visible = 1" .
        " AND cc.visible = 1" .
        " AND c.visible = 1" .
        implode(' ', $conditions) .
        " ORDER BY domain, sport, numweekday, starttime, event";
    return $DB->get_records_sql($sql, $params);
}

/**
 * Une fonction à documenter (TODO).
 *
 * @return array Un tableau d'activités.
 */
function enrol_select_get_activities_roles() {
    global $DB;

    $sql = "SELECT r.id, r.name, r.shortname, r.description, r.sortorder, r.archetype, ar.color, ar.fontawesomeid" .
        " FROM {role} r" .
        " LEFT JOIN {apsolu_roles} ar ON r.id = ar.id" .
        " ORDER BY sortorder";
    $roles = role_fix_names($DB->get_records_sql($sql));

    $activities = [];

    $sql = "SELECT e.courseid, esr.roleid" .
        " FROM {enrol} e" .
        " JOIN {apsolu_courses} ac ON ac.id = e.courseid" .
        " JOIN {enrol_select_roles} esr ON e.id = esr.enrolid";
    $recordset = $DB->get_recordset_sql($sql);
    foreach ($recordset as $record) {
        if (isset($roles[$record->roleid]) === false) {
            continue;
        }

        if (isset($activities[$record->courseid]) === false) {
            $activities[$record->courseid] = [];
        }
        $activities[$record->courseid][$record->roleid] = $roles[$record->roleid];
    }
    $recordset->close();

    return $activities;
}

/**
 * Une fonction à documenter (TODO).
 *
 * @return array Un tableau d'activités.
 */
function enrol_select_get_activities_teachers() {
    global $DB;

    $teachers = [];

    $sql = "SELECT u.id AS userid, u.firstname, u.lastname, u.email, ac.id AS courseid" .
        " FROM {user} u" .
        " JOIN {role_assignments} ra ON u.id = ra.userid AND ra.roleid = 3" . // Teacher.
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50" . // Course context.
        " JOIN {apsolu_courses} ac ON ac.id = ctx.instanceid" .
        " ORDER BY u.lastname, u.firstname";
    $recordset = $DB->get_recordset_sql($sql);
    foreach ($recordset as $record) {
        if (isset($teachers[$record->courseid]) === false) {
            $teachers[$record->courseid] = [];
        }

        $user = new \stdClass();
        $user->id = $record->userid;
        $user->firstname = $record->firstname;
        $user->lastname = $record->lastname;
        $user->email = $record->email;
        $teachers[$record->courseid][$record->userid] = $user;
    }
    $recordset->close();

    return $teachers;
}

/**
 * Renvoie tous les groupements d'activités visibles (Sports de raquettes, sports aquatiques, etc).
 *
 * @return array Un tableau de groupements d'activités.
 */
function enrol_select_get_visible_activities_domains() {
    global $DB;

    $sql = "SELECT *" .
        " FROM {course_categories} cc" .
        " JOIN {apsolu_courses_groupings} acg ON cc.id = acg.id" .
        " WHERE cc.visible = 1" .
        " ORDER BY cc.name";
    return $DB->get_records_sql($sql);
}

/**
 * Renvoie toutes les activités visibles (Tennis, Natation, etc).
 *
 * @return array Un tableau d'activités.
 */
function enrol_select_get_visible_sports() {
    global $DB;

    $sql = "SELECT *" .
        " FROM {course_categories} cc" .
        " JOIN {apsolu_courses_categories} acc ON cc.id = acc.id" .
        " WHERE cc.visible = 1" .
        " ORDER BY cc.name";
    return $DB->get_records_sql($sql);
}

/**
 * Renvoie tous les rôles basés sur le type STUDENT (sauf le rôle student de base).
 *
 * @return array Un tableau de rôles basés sur le type STUDENT.
 */
function enrol_select_get_custom_student_roles() {
    global $DB;

    $sql = "SELECT r.id, r.name, r.shortname, r.description, r.sortorder, r.archetype, ar.color, ar.fontawesomeid" .
        " FROM {role} r" .
        " LEFT JOIN {apsolu_roles} ar ON r.id = ar.id" .
        " WHERE r.archetype = 'student'" .
        " ORDER BY sortorder";
    $roles = role_fix_names($DB->get_records_sql($sql));
    unset($roles[5]);

    return $roles;
}

/**
 * Renvoie toutes les activités dans lesquelles un utilisateur est inscrit.
 *
 * @param int|null $userid Identifiant d'un utilisateur. Si NULL, on prend l'id de l'utilisateur courant.
 *
 * @return array Un tableau contenant la liste des inscriptions.
 */
function enrol_select_get_user_activity_enrolments($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $time = time();

    $sql = "SELECT DISTINCT c.*, cc.name AS sport, FORMAT(acol.userprice, 2) AS price, '1' AS paymentcenterid," .
        " e.id AS enrolid, ue.status, ra.roleid" .
        " FROM {course} c" .
        " JOIN {course_categories} cc ON cc.id = c.category" .
        " JOIN {apsolu_courses} ac ON c.id=ac.id" .
        // Check cohorts.
        " JOIN {enrol} e ON c.id = e.courseid" .
        " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid" .
        " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid" .
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = cm.userid" .
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id" .
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id" .
        " JOIN {apsolu_colleges} acol ON acol.roleid = ra.roleid" .
        " JOIN {apsolu_colleges_members} acm ON acol.id = acm.collegeid AND acm.cohortid = cm.cohortid" .
        " WHERE e.enrol = 'select'" .
        " AND e.status = 0" . // Active.
        " AND cm.userid = :userid" .
        " AND c.visible = 1" .
        " AND (ue.timestart = 0 OR ue.timestart <= :timestart)" .
        " AND (ue.timeend = 0 OR ue.timeend >= :timeend)" .
        " ORDER BY c.fullname";
    return $DB->get_records_sql($sql, ['userid' => $userid, 'timestart' => $time, 'timeend' => $time]);
}

/**
 * Renvoie toutes les activités dans lesquelles un utilisateur est inscrit (sans vérifier les cohortes).
 *
 * @param int|null $userid Identifiant d'un utilisateur. Si NULL, on prend l'id de l'utilisateur courant.
 *
 * @return array Un tableau contenant la liste des inscriptions.
 */
function enrol_select_get_real_user_activity_enrolments($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $time = time();

    $sql = "SELECT DISTINCT c.*, cc.name AS sport, e.id AS enrolid, ue.status, ra.roleid, '1' AS paymentcenterid" .
        " FROM {course} c" .
        " JOIN {course_categories} cc ON cc.id = c.category" .
        " JOIN {apsolu_courses} ac ON c.id = ac.id" .
        " JOIN {enrol} e ON c.id = e.courseid" .
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid" .
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.itemid = e.id" .
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id" .
        " WHERE e.enrol = 'select'" .
        " AND e.status = 0" . // Active.
        " AND c.visible = 1" .
        " AND ue.userid = :userid" .
        " AND e.enrolstartdate <= :timestart" . // Date de début des inscriptions.
        " AND e.customint8 >= :timeend" . // Date de fin des cours.
        " ORDER BY c.fullname";
    return $DB->get_records_sql($sql, ['userid' => $userid, 'timestart' => $time, 'timeend' => $time]);
}

/**
 * Renvoie toutes les activités dans lesquelles un utilisateur est inscrit (sans vérifier les cohortes).
 *
 * @param int|null $userid     Identifiant d'un utilisateur. Si NULL, on prend l'id de l'utilisateur courant.
 * @param bool     $onlyactive Témoin permettant de retourner uniquement les inscriptions actives.
 *
 * @return array Un tableau contenant la liste des inscriptions.
 */
function enrol_select_get_recordset_user_activity_enrolments($userid = null, $onlyactive = true) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if ($onlyactive === true) {
        $time = time();
    }

    $params = ['userid' => $userid];
    $sql = "SELECT DISTINCT c.*, cc.name AS sport, e.id AS enrolid, e.name AS enrolname," .
        " ue.status, ra.roleid, '1' AS paymentcenterid" .
        " FROM {course} c" .
        " JOIN {course_categories} cc ON cc.id = c.category" .
        " JOIN {apsolu_courses} ac ON c.id = ac.id" .
        " JOIN {enrol} e ON c.id = e.courseid" .
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid" .
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.itemid = e.id" .
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id" .
        " WHERE e.enrol = 'select'" .
        " AND e.status = 0" . // Active.
        " AND c.visible = 1" .
        " AND ue.userid = :userid";

    if ($onlyactive === true) {
        $sql .= " AND (ue.timestart = 0 OR ue.timestart <= :timestart)" .
            " AND (ue.timeend = 0 OR ue.timeend >= :timeend)";
        $params['timestart'] = $time;
        $params['timeend'] = $time;
    }

    $sql .= " ORDER BY c.fullname";

    return $DB->get_recordset_sql($sql, $params);
}

/**
 * Renvoie toutes les activités complémentaires dans lesquelles un utilisateur est inscrit et validé.
 *
 * @param int|null $userid Identifiant d'un utilisateur. Si NULL, on prend l'id de l'utilisateur courant.
 *
 * @return array Un tableau contenant la liste des inscriptions.
 */
function enrol_select_get_user_complement_enrolments($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT DISTINCT c.*, FORMAT(ac.price, 2) AS price, ac.federation, '1' AS paymentcenterid, e.id AS enrolid, ue.status" .
        " FROM {course} c" .
        " JOIN {apsolu_complements} ac ON c.id=ac.id" .
        // Check cohorts.
        " JOIN {enrol} e ON c.id = e.courseid" .
        " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid" .
        " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid" .
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = cm.userid" .
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.userid = cm.userid AND ra.itemid = e.id" .
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid=c.id" .
        " WHERE e.enrol = 'select'" .
        " AND e.status = 0" . // Active.
        " AND cm.userid=?" .
        " AND c.visible=1" .
        " ORDER BY c.fullname";

    return $DB->get_records_sql($sql, [$userid]);
}

/**
 * Renvoie tous les collèges auxquels appartient l'utilisateur (nombre de voeux possibles, roles, prix, etc).
 *
 * @param int|null $userid Identifiant d'un utilisateur. Si NULL, on prend l'id de l'utilisateur courant.
 * @param bool     $count  Ajoute le nombre de voeux fait par l'utilisateur pour chaque collège.
 *
 * @return array Un tableau contenant la liste des collèges d'un utilisateur.
 */
function enrol_select_get_user_colleges($userid = null, $count = false) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT DISTINCT ac.*" .
        " FROM {apsolu_colleges} ac" .
        // Check cohorts.
        " JOIN {apsolu_colleges_members} acm ON ac.id = acm.collegeid" .
        " JOIN {cohort} ct ON ct.id = acm.cohortid" .
        " JOIN {cohort_members} cm ON ct.id = cm.cohortid" .
        " WHERE cm.userid = ?";
    $colleges = $DB->get_records_sql($sql, [$userid]);

    if ($count === true) {
        $countuserroles = enrol_select_get_count_user_role_assignments();
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
 * Renvoie le nombre de voeux autorisés pour un utilisateur pour chaque rôle.
 *
 * @param int|null $userid Si null, on prend l'identifiant de l'utilisateur courant.
 * @param bool     $count  Ajoute le nombre de voeux fait par l'utilisateur pour chaque rôle.
 *
 * @return array
 */
function enrol_select_get_sum_user_choices($userid = null, $count = false) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $sql = "SELECT ac.roleid, SUM(ac.maxwish) AS maxwish, SUM(ac.minregister) AS minregister, SUM(ac.maxregister) AS maxregister" .
        " FROM {apsolu_colleges} ac" .
        " WHERE ac.id IN (" .
            // Récupère la liste des populations auxquelles appartient l'étudiant.
            // Note : nous faisons une sous-requête afin d'éviter de compter un maximum de voeux erroné,
            // notamment lorsqu'un étudiant apparait dans plusieurs cohortes liées à une population.
            " SELECT acm.collegeid FROM {apsolu_colleges_members} acm" .
            " JOIN {cohort} ct ON ct.id = acm.cohortid" .
            " JOIN {cohort_members} cm ON ct.id = cm.cohortid" .
            " WHERE cm.userid = ?" .
        " )" .
        " GROUP BY ac.roleid";
    $roles = $DB->get_records_sql($sql, [$userid]);

    if ($count === true) {
        $countuserroles = enrol_select_get_count_user_role_assignments($userid);
        foreach ($roles as $role) {
            $role->count = 0;
            if (isset($countuserroles[$role->roleid]) === true) {
                $role->count = $countuserroles[$role->roleid]->count;
            }
        }
    }

    return $roles;
}

/**
 * Renvoie le total d'inscription par rôle d'un utilisateur.
 *
 * @param int|null $userid Identifiant d'un utilisateur. Si NULL, on prend l'id de l'utilisateur courant.
 *
 * @return array Un tableau contenant la liste des rôles assignés à un utilisateur.
 */
function enrol_select_get_count_user_role_assignments($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $time = time();

    $sql = "SELECT ra.roleid, COUNT(c.id) AS count" .
        " FROM {role_assignments} ra" .
        " JOIN {context} ctx ON ctx.id = ra.contextid" .
        " JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = 50" .
        " JOIN {apsolu_courses} ac ON ac.id = c.id" .
        " JOIN {enrol} e ON c.id = e.courseid AND ra.itemid = e.id" .
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = ra.userid" .
        " WHERE e.enrol = 'select'" .
        " AND e.status = 0" . // Active.
        " AND c.visible = 1" .
        " AND ue.userid = :userid" .
        " AND e.enrolstartdate <= :timestart" . // Date de début des inscriptions.
        " AND e.customint8 >= :timeend" . // Date de fin des cours.
        " GROUP BY ra.roleid";
    return $DB->get_records_sql($sql, ['userid' => $userid, 'timestart' => $time, 'timeend' => $time]);
}

/**
 * Renvoie tous les rôles auxquels un utilisateur peut prétendre.
 *
 * @param int|null $userid   Identifiant d'un utilisateur. Si NULL, on prend l'id de l'utilisateur courant.
 * @param int|null $courseid Identifiant d'un cours. Si NULL, on prend tous les rôles possibles.
 *
 * @return array Un tableau contenant la liste des rôles auxquels un utilisateur peut prétendre.
 */
function enrol_select_get_potential_user_roles($userid = null, $courseid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if ($courseid === null) {
        $sql = "SELECT DISTINCT r.*
                  FROM {role} r
                  JOIN {apsolu_colleges} ac ON r.id = ac.roleid
                  JOIN {apsolu_colleges_members} acm ON ac.id = acm.collegeid
                  JOIN {cohort_members} cm ON cm.cohortid = acm.cohortid
                 WHERE cm.userid = :userid
              ORDER BY r.sortorder";
        $params = ['userid' => $userid];
    } else {
        $time = time();

        $sql = "SELECT r.*
                  FROM {role} r
                  JOIN {role_assignments} ra ON r.id = ra.roleid
                  JOIN {context} ctx ON ctx.id = ra.contextid
                  JOIN {course} c ON c.id = ctx.instanceid
                  JOIN {enrol} e ON c.id = e.courseid AND ra.itemid = e.id
                  JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = ra.userid
                 WHERE e.enrol = 'select'
                   AND e.status = 0
                   AND ue.userid = :userid
                   AND (ue.timestart = 0 OR ue.timestart <= :timestart)
                   AND (ue.timeend = 0 OR ue.timeend >= :timeend)
                   AND c.id = :courseid
                   AND ctx.contextlevel = 50
              ORDER BY r.sortorder";
        $params = ['userid' => $userid, 'timestart' => $time, 'timeend' => $time, 'courseid' => $courseid];
    }

    $roles = role_fix_names($DB->get_records_sql($sql, $params));

    return $roles;
}

/**
 * Retourne la liste des cours dans lesquels l'utilisateur peut s'inscrire ; même si toutes les places sont prises.
 *
 * @param int|null   $time    Timestamp unix permettant aux gestionnaires de simuler une date d'inscription.
 * @param array|null $cohorts Tableau de cohortes permettant aux gestionnaires de simuler une appartenance à des cohortes.
 *
 * @return void
 */
function enrol_select_get_potential_user_activities($time = null, $cohorts = null) {
    global $DB, $USER;

    $groupings = enrol_select_get_visible_activities_domains();
    $categories = enrol_select_get_visible_sports();
    $skills = $DB->get_records('apsolu_skills');
    $locations = $DB->get_records('apsolu_locations');
    $areas = $DB->get_records('apsolu_areas');
    $cities = $DB->get_records('apsolu_cities');
    $useractivityenrolments = enrol_select_get_user_activity_enrolments();
    $roles = enrol_select_get_custom_student_roles();

    if ($cohorts === null) {
        // Pour un étudiant.
        $availableuserroles = enrol_select_get_potential_user_roles();
        $usercolleges = enrol_select_get_sum_user_choices($userid = null, $count = true);

        $unavailableuserroles = [];
        foreach ($usercolleges as $college) {
            if ($college->maxwish > 0 && $college->count >= $college->maxwish) {
                $unavailableuserroles[$college->roleid] = $college->roleid;
            }
        }
    } else {
        // Lorsqu'on utilise les filtres pour gestionnaires, on prend tous les rôles.
        $sql = "SELECT DISTINCT r.*" .
            " FROM {role} r" .
            " JOIN {apsolu_colleges} ac ON r.id = ac.roleid" .
            " JOIN {apsolu_colleges_members} acm ON ac.id = acm.collegeid" .
            " WHERE acm.cohortid IN (" . substr(str_repeat('?,', count($cohorts)), 0, -1) . ")";
        $availableuserroles = role_fix_names($DB->get_records_sql($sql, $cohorts));

        // Collèges.
        $unavailableuserroles = $roles;
        foreach ($availableuserroles as $role) {
            unset($unavailableuserroles[$role->id]);
        }
    }

    $currentactivity = null;

    $now = $time;
    if ($now === null) {
        $now = time();
    }

    // Récupère toutes les activités.
    $sql = "SELECT DISTINCT c.*, ac.*, cc.id AS sportid, cc.description, grp.id AS groupingid" .
        " FROM {course} c" .
        " JOIN {apsolu_courses} ac ON c.id=ac.id" .
        " JOIN {course_categories} cc ON cc.id=c.category" . // Sport category.
        " JOIN {course_categories} grp ON grp.id=cc.parent" . // Parent category.
        // Check cohorts.
        " AND c.visible=1" .
        " AND cc.visible=1" .
        " ORDER BY cc.name, ac.numweekday, ac.starttime, ac.endtime";
    $courses = $DB->get_records_sql($sql);

    // Récupère toutes les méthodes d'inscription valides concernant l'utilisateur courant.
    $sql = "SELECT DISTINCT e.*" .
        " FROM {enrol} e" .
        " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid" .
        " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid" .
        " WHERE e.enrol = 'select'" .
        " AND e.status = 0" .
        " AND (e.enrolstartdate = 0 OR e.enrolstartdate < :enrolstartdate)" .
        " AND (e.enrolenddate = 0 OR e.enrolenddate > :enrolenddate)";
    $params = ['enrolstartdate' => $now, 'enrolenddate' => $now];

    if ($cohorts === null) {
        $sql .= " AND cm.userid = :userid";
        $params['userid'] = $USER->id;
    } else {
        $insql = [];
        foreach ($cohorts as $index => $cohortid) {
            $insql[] = ":cohort" . $index;
            $params['cohort' . $index] = $cohortid;
        }

        $sql .= ' AND ewc.cohortid IN (' . implode(',', $insql) . ')';
        $sql = str_replace('JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid', '', $sql);
    }

    $enrols = $DB->get_records_sql($sql, $params);
    foreach ($enrols as $enrol) {
        if (!isset($courses[$enrol->courseid])) {
            $params = (object) ['courseid' => $enrol->courseid, 'enrolid' => $enrol->id];
            if (defined('BEHAT_SITE_RUNNING') === false) {
                debugging(get_string('debug_enrol_invalid_enrolment', 'enrol_select', $params), $level = DEBUG_DEVELOPER);
            }
            continue;
        }

        if (!isset($courses[$enrol->courseid]->enrols)) {
            $courses[$enrol->courseid]->enrols = [];
        }

        $courses[$enrol->courseid]->enrols[] = $enrol;
    }

    // Calcule le nombre d'inscrits sur les listes d'inscription.
    $availability = [];

    $sql = "SELECT ue.enrolid, ue.status, COUNT(userid) AS count
             FROM {enrol} e
             JOIN {user_enrolments} ue ON e.id = ue.enrolid
            WHERE e.enrol = 'select'
           GROUP BY ue.enrolid, ue.status";
    $recordset = $DB->get_recordset_sql($sql);
    foreach ($recordset as $record) {
        if (isset($availability[$record->enrolid]) === false) {
            $availability[$record->enrolid] = new stdClass();
            $availability[$record->enrolid]->main = 0;
            $availability[$record->enrolid]->wait = 0;
        }

        switch ($record->status) {
            case enrol_select_plugin::ACCEPTED:
            case enrol_select_plugin::MAIN:
                $availability[$record->enrolid]->main += $record->count;
                break;
            case enrol_select_plugin::WAIT:
                $availability[$record->enrolid]->wait += $record->count;
        }
    }
    $recordset->close();

    // Récupère tous les rôles acceptés par cours.
    $selectroles = [];

    $recordset = $DB->get_recordset('enrol_select_roles');
    foreach ($recordset as $record) {
        if (isset($selectroles[$record->enrolid]) === false) {
            $selectroles[$record->enrolid] = [];
        }

        $selectroles[$record->enrolid][] = $record->roleid;
    }
    $recordset->close();

    // Parcourt chaque créneau horaire.
    foreach ($courses as $courseid => $course) {
        if (!isset($categories[$course->category])) {
            $params = (object) ['courseid' => $course->id, 'categoryid' => $course->category];
            if (defined('BEHAT_SITE_RUNNING') === false) {
                debugging(get_string('debug_enrol_invalid_category', 'enrol_select', $params), $level = DEBUG_DEVELOPER);
            }
            unset($courses[$courseid]);
            continue;
        }

        // L'utilisateur ne semble pas avoir le droit de s'inscrire à ce cours.
        if (!isset($courses[$courseid]->enrols)) {
            $params = (object) ['courseid' => $course->id, 'userid' => $USER->id];
            if (defined('BEHAT_SITE_RUNNING') === false) {
                debugging(get_string('debug_enrol_no_enrolments', 'enrol_select', $params), $level = DEBUG_DEVELOPER);
            }
            unset($courses[$courseid]);
            continue;
        }

        // Il y a trop de méthodes !
        if (isset($courses[$courseid]->enrols[1])) {
            $params = (object) ['courseid' => $courseid, 'userid' => $USER->id];
            if (defined('BEHAT_SITE_RUNNING') === false) {
                debugging(get_string('debug_enrol_too_many_enrolments', 'enrol_select', $params), $level = DEBUG_DEVELOPER);
            }
            unset($courses[$courseid]);
            continue;
        }

        $enrol = $courses[$courseid]->enrols[0];
        $course->enrolid = $enrol->id;
        $course->enrolname = $enrol->name;

        $time = time();

        if ($enrol->customint3 == 1) {
            // Les quotas sont activés.
            // TODO: refactoriser cette partie avec le script ajax/reload_column_left_places.php.
            // Récupère le nombre d'inscrits sur la liste des acceptés et sur la liste principale.
            $course->count_main_list = $availability[$enrol->id]->main ?? 0;

            // Récupère le quota de la liste principale.
            $course->max_main_list = $enrol->customint1;

            // Récupère le nombre d'inscrits sur la liste complémentaire.
            $course->count_wait_list = $availability[$enrol->id]->wait ?? 0;

            // Récupère le quota de la liste complémentaire.
            $course->max_wait_list = $enrol->customint2;

            if ($course->max_main_list > $course->count_main_list && $course->count_wait_list === 0) {
                // Si la liste principale n'est pas complète et que la liste d'attente est vide.
                $count = $course->max_main_list - $course->count_main_list;
                $course->left_places_str = $count . ' ' . get_string_on_list_x(
                    enrol_select_plugin::MAIN,
                    $count > 1 ? 'places_remaining_on_listname_X' : 'place_remaining_on_listname_X'
                );
                $course->left_places_style = 'success';
                $course->full_registration = false;
            } else if ($course->max_wait_list > $course->count_wait_list) {
                // Si la liste complémentaire n'est pas complète.
                // TODO: faire une option afin de laisser le choix entre afficher le nombre
                // de places restantes sur liste complémentaire
                // ou afficher un message générique indiquant qu'il reste des places sur liste complémentaire.
                $course->left_places_str = get_string_on_list_x(enrol_select_plugin::WAIT, 'there_are_still_places_on_listname_X');
                $course->left_places_style = 'warning';
                $course->full_registration = false;
            } else {
                // Si il ne reste plus de place.
                $course->left_places_str = get_string('no_places_available', 'enrol_select');
                $course->left_places_style = 'danger';
                $course->full_registration = true;
            }
        } else {
            // Les quotas sont désactivés.
            $course->left_places_str = get_string('no_seat_restrictions', 'enrol_select');
            $course->left_places_style = 'success';
            $course->full_registration = false;
        }

        // TODO: est-ce que l'utilisateur peut accéder à tous les types ?

        // Récupère tous les rôles acceptés par ce cours.
        $course->role_options = [];
        if (isset($selectroles[$enrol->id]) === true) {
            foreach ($selectroles[$enrol->id] as $roleid) {
                if (isset($roles[$roleid])) {
                    $course->role_options[$roleid] = $roles[$roleid];
                }
            }
        }

        if ($course->role_options === []) {
            if (defined('BEHAT_SITE_RUNNING') === false) {
                debugging('Course #' . $course->id . ': no role for enrol #' . $enrol->id, $level = DEBUG_DEVELOPER);
            }
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
                        // Le cours ne propose pas ce rôle. L'utilisateur ne peut pas choisir ce rôle.
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

        // Note : ne pas utiliser uasort() pour préserver les index, car mustache ne sait pas parcourir les tableaux associatifs.
        usort($course->role_options, function ($a, $b) {
            return ($a->sortorder < $b->sortorder) ? -1 : 1;
        });

        $course->grouping = $groupings[$categories[$course->category]->parent]->name;
        $course->sport = $categories[$course->category]->name;
        $course->weekday_locale = get_string(strtolower($course->weekday), 'local_apsolu');
        $course->skill = $skills[$course->skillid]->name;
        $location = $locations[$course->locationid];
        $course->location = $location->name;
        $area = $areas[$location->areaid];
        $course->areaid = $areas[$location->areaid]->id;
        $course->area = $areas[$location->areaid]->name;
        $course->cityid = $cities[$area->cityid]->id;
        $course->city = $cities[$area->cityid]->name;
        if (isset($locations[$course->locationid]->longitude, $locations[$course->locationid]->latitude)) {
            $course->longitude = $locations[$course->locationid]->longitude;
            $course->latitude = $locations[$course->locationid]->latitude;
        }
    }

    return $courses;
}

/**
 * Une fonction à documenter (TODO).
 *
 * @return array
 */
function enrol_select_get_potential_user_complements() {
    global $CFG, $DB, $USER;

    if (isset($CFG->is_siuaps_rennes) === false) {
        return [];
    }

    $usercomplementenrolments = enrol_select_get_user_complement_enrolments();

    $now = time();

    $sql = "SELECT DISTINCT c.*, ac.*, format(ac.price, 2, 'fr_FR') AS price" .
        " FROM {course} c" .
        " JOIN {apsolu_complements} ac ON c.id = ac.id" .
        // Check cohorts.
        " JOIN {enrol} e ON c.id = e.courseid" .
        " JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid" .
        " JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid" .
        " WHERE e.enrol = 'select'" .
        " AND e.status = 0" . // Active.
        " AND (e.enrolstartdate = 0 OR e.enrolstartdate < ?)" .
        " AND (e.enrolenddate = 0 OR e.enrolenddate > ?)" .
        " AND cm.userid=?" .
        " AND c.visible=1" .
        " ORDER BY c.fullname";
    $courses = $DB->get_records_sql($sql, [$now, $now, $USER->id]);

    foreach ($courses as $index => $course) {
        $enrol = $DB->get_record('enrol', ['enrol' => 'select', 'status' => 0, 'courseid' => $course->id]);
        if ($enrol === false || (isset($CFG->is_siuaps_rennes) === true && $course->id === '249')) {
            unset($courses[$index]);
            continue;
        }

        $course->enrolid = $enrol->id;
        $course->enrolname = $enrol->name;
        $course->enroled = isset($usercomplementenrolments[$course->id]);
    }

    return $courses;
}

/**
 * Retourne les activités pour lesquelles l'utilisateur peut potentiellement se réinscrire.
 *
 * @param int|null $userid Identifiant d'un utilisateur. Si NULL, on prend l'id de l'utilisateur courant.
 *
 * @return array Un tableau contenant la liste des activités pour lesquelles l'utilisateur peut potentiellement se réinscrire.
 */
function enrol_select_get_user_reenrolments($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $time = time();

    $sql = "SELECT DISTINCT c.*, cc.name AS sport, e.id AS enrolid, ue.status, ra.roleid, '1' AS paymentcenterid" .
        " FROM {course} c" .
        " JOIN {course_categories} cc ON cc.id = c.category" .
        " JOIN {apsolu_courses} ac ON c.id = ac.id" .
        " JOIN {enrol} e ON c.id = e.courseid" .
        " JOIN {user_enrolments} ue ON e.id = ue.enrolid" .
        " JOIN {role_assignments} ra ON ra.userid = ue.userid AND ra.itemid = e.id" .
        " JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = c.id" .
        " WHERE e.enrol = 'select'" .
        " AND e.status = 0" . // Active.
        " AND c.visible = 1" .
        " AND ue.userid = :userid" .
        " AND e.customint6 != 0" . // Enrol id, vers lequel on réinscrit l'utilisateur.
        " AND (e.customint4 = 0 OR e.customint4 <= :timestart)" . // Date de début des réinscriptions.
        " AND (e.customint5 = 0 OR e.customint5 >= :timeend)" . // Date de fin des réinscriptions.
        " ORDER BY c.fullname";
    return $DB->get_records_sql($sql, ['userid' => $userid, 'timestart' => $time, 'timeend' => $time]);
}

/**
 * Une fonction à documenter (TODO).
 *
 * @param array $courses Tableau contenant une liste de cours.
 *
 * @return void
 */
function enrol_select_generate_filters($courses = []) {
    $filters = [];

    $elements = [
        'city' => [],
        'category' => [],
        'sport' => [],
        'skill' => [],
        'area' => [],
        'weekday' => [],
        'starttime' => [],
        'endtime' => [],
        'role' => [],
    ];

    foreach ($courses as $course) {
        // Set elements.
        $elements['category'][$course->groupingid] = $course->grouping;
        $elements['sport'][$course->category] = $course->sport;
        $elements['skill'][$course->skillid] = $course->skill;
        $elements['area'][$course->areaid] = $course->area;
        $elements['weekday'][$course->numweekday] = get_string($course->weekday, 'local_apsolu');
        $starttime = substr($course->starttime, 0, 2) . 'h';
        $elements['starttime'][$starttime] = $starttime;
        $endtime = substr($course->endtime, 0, 2) . 'h';
        $elements['endtime'][$endtime] = $endtime;
        foreach ($course->role_options as $role) {
            $elements['role'][$role->id] = $role->localname;
        }
        $elements['city'][$course->cityid] = $course->city;
    }

    if (count($elements['city']) < 2) {
        unset($elements['city']);
    }

    foreach ($elements as $type => $element) {
        if ($type === 'weekday') {
            ksort($element);
        } else {
            asort($element);
        }
        $attributes = [
            'data-column-name' => $type,
            'data-placeholder' => get_string($type, 'local_apsolu'),
            'data-allow-clear' => 'true',
            'style' => 'margin: 0 1em',
            'multiple' => 'true',
            'class' => 'filters',
        ];
        $filters[$type] = \html_writer::select($element, 'filters[' . $type . ']', $selected = '', $nothing = '', $attributes);
    }

    return $filters;
}

/**
 * Retourne la valeur de la chaine de caractères correspondant à un des formats possibles des statuts d'inscription.
 * Les statuts d'inscription sont définis dans les constantes de la classe. Les valeurs des chaînes de caractère sont définies
 *  par défaut dans le fichier de langues et peuvent être surchargées grâce à la configuration du plugin.
 *
 * @param int|string  $status Code numérique d'une des constantes de classe (ACCEPTED, MAIN, WAIT et DELETED).
 * @param string $listformat Valeur pour préciser la partie générique (format) du nom de la chaîne de caractère attendue.
 * @param bool $default valeur à retourner : valeur par défaut ou valeur custom (si définie dans la configuration du plugin) ?
 * @param bool $strformat appliquer une chaîne de formatage si disponible ?
 *
 * @return string|false la valeur par défaut ou la valeur custom, renvoie false si le $status n'est pas correct.
 */
function get_enrolment_fieldvalue(int|string $status, string $listformat, bool $default = true, bool $strformat = true) {
    // Nom de la chaîne pour ce statut d'inscription.
    $strname = get_enrolment_strname((int) $status, $listformat);
    if ($strname !== false) {
        // Valeur de la chaîne : par défaut ou définie dans la configuration du plugin.
        $strvalue = get_enrolment_strvalue($strname, $default);
        // On applique un formatage à la chaîne (si définit dans le fichier de langue) ?
        if ($strformat) {
            $strvalue = formatstr($strvalue, $listformat);
        }

        return $strvalue;
    }

    return false;
}

/**
 * Retourne la clé permettant de charger la chaine de caractères correspondant au statut d'inscription avec le format donné.
 *
 * @param integer $status le code du statut dans les constantes de la classe.
 * @param string $listformat Valeur pour préciser le format attendu.
 * @return string|false renvoie le nom complet de la chaîne, false si $status ou $listformat n'est pas trouvé.
 */
function get_enrolment_strname(int $status, string $listformat): string|false {
    // Le statut correspond bien à une des constantes de classes ?
    $state = enrol_select_plugin::get_state_from_code($status);
    if ($state != false) {
        // Correspondance entre les différents formats et le nom du paramètre dans la configuration du plugin.
        $listformatf = enrol_select_plugin::get_settingname($listformat);
        if (empty($listformatf) != true) {
            return sprintf($listformatf, $state); // Remplace la partie dynamique de la clé par le statut.
        }
    }

    return false; // La fonction renvoie false si le code n'est pas trouvé ou si $listformat ne correspond à aucune entrée.
}

/**
 * Retourne la chaîne de caractère définie pour un format et un statut d'inscription, soit dans le fichier de langues (valeur
 *  par défaut) soit dans la configuration du plugin si la valeur a été modifiée.
 *
 * @param string $strname le nom de la chaîne à retourner.
 * @param bool $default permet de forcer l'utilisation de la valeur par défaut.
 * @return string la valeur par défaut si $default est true ou si la variable n'est pas redéfinie dans la configuration
 *                  la valeur "custom" (définie dans la configuration) sinon.
 */
function get_enrolment_strvalue(string $strname, bool $default = true): string {
    $defaultstr = get_string($strname, 'enrol_select');
    if ($default === true || get_config('enrol_select', $strname) == false) {
        return $defaultstr;
    }

    return get_config('enrol_select', $strname);
}

/**
 * Retourne l'étiquette du champ, sous titrée par la mention de la valeur par défaut
 * (formulaire de gestion des statuts d'inscription).
 *
 * @param string $label la chaîne de caractère à afficher dans le label.
 * @param string $defaultvalue la valeur par défaut.
 * @return string $label la balise html de l'étiquette du champ.
 */
function get_custom_label(string $labelname, string $defaultvalue) {
    $label = html_writer::tag('span', get_string($labelname, 'enrol_select'), ['class' => 'in-label label']);
    $default = html_writer::tag('span', $defaultvalue);
    $defaultlabel = html_writer::tag(
        'span',
        get_string('default_value', 'enrol_select') . $default,
        ['class' => 'in-label default form-shortname d-block small text-muted']
    );
    return $label . $defaultlabel;
}

/**
 * Retourne la chaine de caractère correspondant à un des formats possibles pour un statut d'inscription,
 *  formatée selon le pattern définit dans le fichier de langues (si existant).
 *
 * @param string $str la chaîne brute à formater.
 * @param string $listformat le format utilisé : permet de trouver la chaîne permettant de formater (ex. listname_strformat).
 * @return string la chaîne formatée selon le pattern défini dans le fichier de langues, si présent.
 */
function formatstr(string $str, string $listformat): string {
    // On teste si la chaîne correspondant au format (ex. ['listname_strformat'] pour le format 'listname')
    // est présente dans le fichier de langues.
    $identifier = sprintf('%s_strformat', $listformat);
    if (get_string($identifier, 'enrol_select') !== "[[$identifier]]") {
        return get_string($identifier, 'enrol_select', $str);
    }
    return $str;
}

/**
 * Retourne le statut d'inscription dans sa version brute à partir de sa version formatée
 *  (formulaire de gestion des statuts d'inscription).
 *
 * @param string $str la chaine formatée décrivant le statut d'inscription.
 * @param string $listformat le format auquel correspond la valeur.
 * @return string|false la chaîne brute, ou false si le pattern n'a pas été trouvé.
 */
function unformatstr(string $str, string $listformat): string|false {
    // On teste si la chaîne correspondant au format (ex. ['listname_strformat'] pour le format 'listname')
    // est présente dans le fichier de langues.
    $identifier = sprintf('%s_strformat', $listformat);
    if (get_string($identifier, 'enrol_select') == "[[$identifier]]") {
        return $str; // Pas de chaîne de formatage définie.
    }

    $strformat = get_string($identifier, 'enrol_select', '(.+)');
    preg_match('/^' . $strformat . '/i', $str, $matches);

    if (empty($matches[0] == true)) {
        return false;
    }

    return $matches[1];
}

/**
 * Retourne la chaîne de caractères du fichier de langues en complétant avec le(s)
 * statut(s) d'inscription formatés(s) selon la variante souhaitée (status, abbr, short...).
 *
 * @param array|int $status code(s) correspondant à un statut d'inscription selon les constantes de la classe.
 * @param string $getstring la clé de la chaîne dans le fichier de langues.
 *                  Doit posséder un élément dynamique {$a} si un seul code, ou {$a->accepted} si plusieurs codes.
 * @param string|null $listformat précise le format du libellé attendu.
 *                          Si null, le format est recherché dans la clé de la chaîne de caractères.
 * @param bool $strformat appliquer une chaîne de formatage si disponible ?
 * @return string
 */
function get_string_on_list_x(array|int $status, string $getstring, ?string $listformat = '', $strformat = false): string {
    $strlist = "[[$getstring]]";

    // Liste des formats possibles (variantes de formulation pour le nom de la liste).
    $listformats = enrol_select_plugin::get_listformats();

    // Si le format n'est pas précisé, on recherche celui-ci dans la clé de la chaîne de caractères.
    if (empty($listformat) == true) {
        // La clé doit respecter le pattern suivant : {strmessage}_on_{listformat}_X.
        $reg = '/.*_on_(.*)_X/i'; // Ex. welcome_on_listname_X : la chaîne de caractères sera chargée avec le format listname.
        preg_match($reg, $getstring, $matches);

        if (empty($matches[0] != true)) { // L'un des formats a été identifié dans le pattern ?
            $listformat = $matches[1];
        }
    } else if (in_array($listformat, $listformats) == false) {
        return $strlist; // L'argument $listformat soumis ne correspond pas à un des formats pris en charge.
    }

    if (is_array($status)) {
        $str = get_string($getstring, 'enrol_select');
        $search = [];
        $replace = [];
        foreach ($status as $code) {
            $state = enrol_select_plugin::get_state_from_code($code);
            $target = '{$a->' . $state . '}';
            if ($state != false && str_contains($str, $target)) {
                $search[]  = $target;
                $replace[] = get_enrolment_fieldvalue($code, $listformat, false, $strformat);
            }
        }

        if (count($search) == count($status)) {
            $strlist = str_replace($search, $replace, $str);
        }
    } else {
        // On récupère le libellé de la liste au format souhaité, avec la valeur appropriée
        // (valeur par défaut ou valeur custom si définie dans la configuratino du plugin).
        $replace = get_enrolment_fieldvalue($status, $listformat, false, $strformat);

        // La variable $replace est à false si le code du statut n'est pas dans les constantes de la classe.
        if (empty($replace) != true) {
            // On remplace l'élément dynamique (paramètre {$a} dans la chaîne) par le nom de la liste.
            $strlist = get_string($getstring, 'enrol_select', $replace);
        }
    }

    return $strlist;
}

/**
 * Retourne la chaîne de caractères correspondant à l'action de déplacer des étudiants depuis une liste vers une autre liste.
 *
 * @param integer $stateto le code de la liste sur laquelle sont actuellement les étudiants.
 * @param integer $statefrom le code de la liste sur laquelle les étudiants vont être déplacés.
 * @param boolean $isnext true si la liste d'arrivée correspond au prochain semestre par rapport à la liste de départ.
 * @return string la chaîne de caractères correspondant à l'action de déplacer des étudiants depuis une liste vers une autre liste.
 */
function get_goto_list_customstr(int $stateto, int $statefrom, bool $isnext = false): string {

    $lists = new stdClass();

    // Nom de la liste d'arrivée selon la configuration du plugin.
    $lists->to = $isnext ?
        get_string_on_list_x($stateto, 'next_on_listname_X') : // On précise s'il s'agit de celle du prochain semestre.
        get_enrolment_fieldvalue($stateto, 'listname', false, false);

    // Nom de la liste de départ selon la configuration du plugin.
    $lists->from = get_enrolment_fieldvalue($statefrom, 'listname', false, false);

    return get_string('goto', 'enrol_select', $lists);
}

/**
 * Retourne le message par défaut proposé lors du déplacement des étudiants depuis une liste vers une autre liste.
 *
 * @param integer $stateto le code de la liste sur laquelle sont actuellement les étudiants.
 * @param integer $statefrom le code de la liste sur laquelle les étudiants vont être déplacés.
 * @param boolean $isnext true si la liste d'arrivée correspond au prochain semestre par rapport à la liste de départ.
 * @return string le message par défaut.
 */
function get_moved_to_message(int $stateto, int $statefrom, bool $isnext = false): string {
    // Si la liste de départ est DELETED, cela n'est pas explicité : on évoque seulement la liste d'arrivée.
    if ($statefrom == enrol_select_plugin::DELETED) {
        // Nom de la liste d'arrivée selon la configuration du plugin.
        $tolist = $isnext ?
            get_string_on_list_x($stateto, 'next_on_listname_X') : // On précise s'il s'agit de celle du prochain semestre.
            get_enrolment_fieldvalue($stateto, 'listname', false, false);
        // Corps du message.
        $movedtostr = get_string('message_moved_from_deleted', 'enrol_select', $tolist);
    } else if ($stateto == enrol_select_plugin::ACCEPTED) {
        // Si la liste d'arrivée est ACCEPTED, on précise seulement que la (p)ré-inscription a été confirmée.
        $movedtostr = $isnext ?
            get_string('message_moved_to_next_accepted', 'enrol_select') : // Réinscription.
            get_string('message_moved_to_accepted', 'enrol_select'); // Préinscription.
    } else {
        $lists = new stdClass();
        // Nom de la liste d'arrivée selon la configuration du plugin.
        $lists->to = $isnext ?
            get_string_on_list_x($stateto, 'next_on_listname_X') : // On précise s'il s'agit de celle du prochain semestre.
            get_enrolment_fieldvalue($stateto, 'listname', false, false);
        // Nom de la liste de départ selon la configuration du plugin.
        $lists->from = $isnext ?
            get_string_on_list_x($statefrom, 'previous_on_listname_X') : // On précise lorsqu'il s'agit de celle semestre précédent.
            get_enrolment_fieldvalue($statefrom, 'listname', false, false);
        // Corps du message.
        $movedtostr = get_string('message_moved_on_list', 'enrol_select', $lists);
    }

    // Inclut les formules de politesse dans le message.
    return get_string('message_greetings', 'enrol_select', $movedtostr);
}

/**
 * Retourne la valeur définie pour le nom de la liste associée au statut d'inscription 'ACCEPTED'.
 *
 * @return string nom de la liste (format liste {nom de la liste}) pour le statut accepted.
 */
function get_accepted_listname(): string {
    return get_enrolment_fieldvalue(enrol_select_plugin::ACCEPTED, 'listname', false, true);
}

/**
 * Retourne la valeur définie pour le nom de la liste associée au statut d'inscription 'MAIN'.
 *
 * @return string nom de la liste (format liste {nom de la liste}) pour le statut main.
 */
function get_main_listname(): string {
    return get_enrolment_fieldvalue(enrol_select_plugin::MAIN, 'listname', false, true);
}

/**
 * Retourne la valeur définie pour le nom de la liste associée au statut d'inscription 'WAIT'.
 *
 * @return string nom de la liste (format liste {nom de la liste}) pour le statut wait.
 */
function get_wait_listname(): string {
    return get_enrolment_fieldvalue(enrol_select_plugin::WAIT, 'listname', false, true);
}

/**
 * Retourne la valeur définie pour le nom de la liste associée au statut d'inscription 'DELETED'.
 *
 * @return string nom de la liste (format liste {nom de la liste}) pour le statut deleted.
 */
function get_deleted_listname(): string {
    return get_enrolment_fieldvalue(enrol_select_plugin::DELETED, 'listname', false, true);
}

/**
 * Retourne la description du statut de la liste d'inscription
 * Nom de la liste correspondant au statut, accès aux contenus, visibilité des sessions.
 *
 * @param int|string $status Code numérique d'une des constantes de classe (ACCEPTED, MAIN, WAIT et DELETED).
 * @return string description.
 */
function get_status_description(int|string $status): string {
    // Description du statut pour cette liste.
    $statusstr = mb_ucfirst(get_enrolment_fieldvalue($status, 'description', false));
    if (empty($statusstr) == false) {
        // Description des droits associés en termes d'accès au contenu du cours et de visibilité des sessions (tableau de bord).

        // Statut "wait" et "main" : pas d'accès aux forums et documents du cours, seule la 1ère session est visible.
        $accessgranted = false;
        $displaysession = 'first';
        // Statut "accepted" : accès aux forums et documents du cours, toutes les sessions sont visibles.
        if ($status == enrol_select_plugin::ACCEPTED) {
            $accessgranted = true;
            $displaysession = 'all';
        } else if ($status == enrol_select_plugin::DELETED) {
            // Statut "deleted" : pas d'accès au forum et documents du cours, aucune session visible (le cours n'est pas référencé).
            $displaysession = 'none';
        }

        // Charge les chaînes en fonction des droits correspondant au statut.
        $access = $accessgranted ?
            get_string('description_accessgranted_true', 'enrol_select') :
            get_string('description_accessgranted_false', 'enrol_select');
        $display = get_string(sprintf('description_displaysession_%s', $displaysession), 'enrol_select');
        return sprintf('%s. %s. %s', $statusstr, $access, $display);
    }

    return "";
}
