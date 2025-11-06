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

namespace enrol_select\observer;

use core\event\enrol_instance_deleted;

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   enrol_select
 * @copyright 2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_instance {
    /**
     * Écoute l'évènement enrol_instance_deleted.
     *
     * @param enrol_instance_deleted $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function deleted(enrol_instance_deleted $event) {
        global $DB;

        if (isset($event->other['enrol']) === false || $event->other['enrol'] !== 'select') {
            // Ignore le traitement si la méthode d'inscription supprimée n'est pas de type 'enrol_select'.
            return;
        }

        // Supprime les références de la méthode d'inscription supprimée dans la table enrol_select_cards.
        $DB->delete_records('enrol_select_cards', ['enrolid' => $event->objectid]);

        // Supprime les références de la méthode d'inscription supprimée dans la table enrol_select_cohorts.
        $DB->delete_records('enrol_select_cohorts', ['enrolid' => $event->objectid]);

        // Supprime les références de la méthode d'inscription supprimée dans la table enrol_select_roles.
        $DB->delete_records('enrol_select_roles', ['enrolid' => $event->objectid]);
    }
}
