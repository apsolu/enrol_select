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
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   enrol_select
 * @copyright 2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_select\observer;

use core\event\cohort_deleted;

defined('MOODLE_INTERNAL') || die();

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   enrol_select
 * @copyright 2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort {
    /**
     * Écoute l'évènement cohort_deleted.
     *
     * @param cohort_deleted $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function deleted(cohort_deleted $event) {
        global $DB;

        // Supprime les références de la cohorte supprimée dans la table apsolu_colleges_members.
        $DB->delete_records('apsolu_colleges_members', array('cohortid' => $event->objectid));

        // Supprime les références de la cohorte supprimée dans la table enrol_select_cohorts.
        $DB->delete_records('enrol_select_cohorts', array('cohortid' => $event->objectid));

        // Supprime les références de la cohorte supprimée dans la table enrol_select_cohorts_roles.
        $DB->delete_records('enrol_select_cohorts_roles', array('cohortid' => $event->objectid));
    }
}