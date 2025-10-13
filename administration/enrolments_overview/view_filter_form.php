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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de filtrer la vue d'ensemble des inscriptions.
 *
 * @package    enrol_select
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class apsolu_enrolments_overview_filter_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        [$colleges, $calendars] = $this->_customdata;

        // Champ "population".
        $mform->addElement('select', 'college', get_string('college', 'enrol_select'), $colleges, ['multiple' => false]);
        $mform->setType('college', PARAM_INT);
        $mform->addRule('college', get_string('required'), 'required', null, 'client');

        // Champ "calendrier".
        $mform->addElement('autocomplete', 'calendar', get_string('calendar', 'local_apsolu'), $calendars, ['multiple' => false]);
        $mform->setType('calendar', PARAM_INT);

        // Champ "erreurs".
        $mform->addElement('checkbox', 'errors', 'Afficher seulement les erreurs');
        $mform->setType('errors', PARAM_INT);
        $mform->setDefault('errors', 1);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'display', get_string('view'));
        $buttonarray[] = &$mform->createElement('submit', 'exportcsv', get_string('export_to_csv_format', 'local_apsolu'));
        $buttonarray[] = &$mform->createElement('submit', 'exportexcel', get_string('export_to_excel_format', 'local_apsolu'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        $mform->addElement('hidden', 'tab', 'enrolments_overview');
        $mform->setType('tab', PARAM_ALPHAEXT);
    }
}
