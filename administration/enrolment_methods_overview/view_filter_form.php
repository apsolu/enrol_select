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
 * Classe pour le formulaire permettant de filtrer la vue d'ensemble des inscriptions.
 *
 * @package    enrol_select
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Classe pour le formulaire permettant de filtrer la vue d'ensemble des inscriptions.
 *
 * @package    enrol_select
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class apsolu_overview_filter_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        list($calendars, $teachers) = $this->_customdata;

        // Champ "créneaux horaires".
        $mform->addElement('text', 'course', get_string('course', 'local_apsolu'));
        $mform->setType('course', PARAM_TEXT);

        // Champ "calendriers".
        $options = [0 => get_string('choosedots')];
        foreach ($calendars as $calendar) {
            $options[$calendar->id] = $calendar->name;
        }
        $mform->addElement('select', 'calendarid', get_string('calendar', 'local_apsolu'), $options);
        $mform->setType('calendarid', PARAM_INT);

        // Champ "date d'ouverture des inscriptions".
        $label = get_string('enrolstartdate', 'local_apsolu');
        $mform->addElement('date_selector', 'enrolstartdate', $label, ['optional'  => true]);
        $mform->setType('enrolstartdate', PARAM_INT);

        // Champ "date de fermeture des inscriptions".
        $label = get_string('enrolenddate', 'local_apsolu');
        $mform->addElement('date_selector', 'enrolenddate', $label, ['optional'  => true]);
        $mform->setType('enrolenddate', PARAM_INT);

        // Champ "enseignants".
        $mform->addElement('select', 'teacherid', get_string('teacher', 'local_apsolu'), $teachers);
        $mform->setType('teacherid', PARAM_INT);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('filter', 'local_apsolu'));
        $buttonarray[] = &$mform->createElement('submit', 'exportcsv', get_string('export_to_csv_format', 'local_apsolu'));
        $buttonarray[] = &$mform->createElement('submit', 'exportexcel', get_string('export_to_excel_format', 'local_apsolu'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        $mform->addElement('hidden', 'tab', 'enrolment_methods_overview');
        $mform->setType('tab', PARAM_ALPHAEXT);
    }
}
