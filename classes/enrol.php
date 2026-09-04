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

namespace enrol_select;

use moodle_recordset;

/**
 * Classe statique exposant des fonctions utiles pour les méthodes d'inscription.
 *
 * @package    enrol_select
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol {
    /**
     * Retourne la liste des méthodes d'inscription ouvertes à l'inscription pour un utilisateur donné.
     *
     * @param ?int $time Timestamp unix permettant aux gestionnaires de simuler une date d'inscription.
     * @param ?array $cohorts Tableau de cohortes permettant aux gestionnaires de simuler une appartenance à des cohortes.
     *
     * @return moodle_recordset
     */
    public static function get_available_enrol_methods(?int $time, ?array $cohorts): moodle_recordset {
        global $DB, $USER;

        $joins = ['JOIN {enrol_select_cohorts} ewc ON e.id = ewc.enrolid'];
        $wheres = [];
        $params = [];

        if ($time === null && $cohorts === null) {
            // Traite le cas par défaut, lorsqu'un utilisateur visite la page des inscriptions.
            $time = time() - date('s'); // Calcule l'heure sans les secondes (HH:MM:00) pour bénéficier du cache SQL.

            $joins[] = 'JOIN {cohort_members} cm ON cm.cohortid = ewc.cohortid';
            $wheres[] = 'AND cm.userid = :userid';
            $params['userid'] = $USER->id;
        } else if ($time === null || $cohorts === null) {
            throw new coding_exception('$time and $cohorts parameters must be both NULL or with a value.');
        } else {
            // Traite le cas où un gestionnaire teste l'affichage en entrant une date et des cohortes.
            [$insql, $params] = $DB->get_in_or_equal(array_values($cohorts), SQL_PARAMS_NAMED, 'cohortid_');

            $wheres[] = 'AND ewc.cohortid ' . $insql;
        }

        // Récupère toutes les méthodes d'inscription valides et ouvertes aux inscriptions.
        $sql = "SELECT DISTINCT e.*
                  FROM {enrol} e " . implode(' ', $joins) . "
                 WHERE e.enrol = 'select'
                   AND e.status = 0
                   AND (e.enrolstartdate = 0 OR e.enrolstartdate <= :enrolstartdate)
                   AND (e.enrolenddate = 0 OR e.enrolenddate >= :enrolenddate) " . implode(' ', $wheres);
        $params['enrolstartdate'] = $time;
        $params['enrolenddate'] = $time;

        return $DB->get_recordset_sql($sql, $params);
    }
}
