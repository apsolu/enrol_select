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

namespace enrol_select\event;

/**
 * Enregistre une trace lorsque des méthodes d'inscription sont mis à jour en lot.
 *
 * @package    enrol_select
 * @copyright  2025 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class batch_enrol_instance_updated extends \core\event\base {
    /**
     * Initialise l'évènement.
     *
     * @return void
     */
    protected function init() {
        // Values: c (create), r (read), u (update) or d (delete).
        $this->data['crud'] = 'u';

        // Values: LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER.
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Retourne le nom de l'évènement.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('batch_enrol_instance_updated', 'local_apsolu');
    }

    /**
     * Retourne les critères utilisés pour la mise à jour des méthodes d'inscription.
     *
     * @return string
     */
    public function get_critiria() {
        $criteria = [];
        foreach ($this->other['criteria'] as $field => $id) {
            $criteria[] = $field . ' with id ' . $id;
        }
        return implode(', ', $criteria);
    }

    /**
     * Retourne la description de l'évènement.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' updated all enrol instances with following criteria : " .
            $this->get_criteria() . ".";
    }

    /**
     * Retourne l'URL liée à l'action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/enrol/select/administration.php', ['tab' => 'batch_settings']);
    }

    /**
     * Valide les données.
     *
     * Throw \coding_exception notice in case of any problems.
     */
    protected function validate_data() {
        parent::validate_data();

        if (isset($this->other['criteria']) === false || is_array($this->other['criteria']) === false) {
            throw new \coding_exception('The array \'criteria\' value must be set in other.');
        }
    }
}
