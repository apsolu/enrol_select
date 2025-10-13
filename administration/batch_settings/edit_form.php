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
require_once($CFG->dirroot . '/enrol/select/lib.php');

/**
 * Classe pour le formulaire permettant de configurer le paramétrage par lots des méthodes d'inscription par voeux.
 *
 * @package    enrol_select
 * @copyright  2025 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_batch_settings_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        [$calendars] = $this->_customdata;

        // Critères de sélection.
        $mform->addElement('header', 'header1', get_string('selection_criteria', 'enrol_select'));

        // Calendriers.
        $options = [];
        foreach ($calendars as $calendar) {
            $options[$calendar->id] = $calendar->name;
        }
        $select = $mform->addElement('select', 'calendar', get_string('calendars', 'local_apsolu'), $options);
        $mform->setType('calendar', PARAM_INT);
        $mform->addRule('calendar', get_string('required'), 'required', null, 'client');

        // Messages de bienvenue.
        $attributes = ['cols' => '60', 'rows' => '16'];
        $options = ['autosave' => false];
        $mform->addElement('header', 'header2', get_string('welcome_messages', 'enrol_select'));

        // Message pour les inscrits sur la liste des acceptés.
        $label = get_string('edit_field_X', 'enrol_select', get_string('custom_welcome_message_on_accepted_list', 'enrol_select'));
        $mform->addElement('checkbox', 'batch_customtext1switch', $label);

        $label = get_string('custom_welcome_message_on_accepted_list', 'enrol_select');
        $mform->addElement('editor', 'batch_customtext1', $label, $attributes, $options);
        $mform->setType('batch_customtext1', PARAM_RAW);
        $mform->addHelpButton('batch_customtext1', 'custom_welcome_message', 'enrol_select');
        $mform->disabledIf('batch_customtext1', 'batch_customtext1switch');

        // Message pour les inscrits sur la liste principale.
        $label = get_string('edit_field_X', 'enrol_select', get_string('custom_welcome_message_on_main_list', 'enrol_select'));
        $mform->addElement('checkbox', 'batch_customtext2switch', $label);

        $label = get_string('custom_welcome_message_on_main_list', 'enrol_select');
        $mform->addElement('editor', 'batch_customtext2', $label, $attributes, $options);
        $mform->setType('batch_customtext2', PARAM_RAW);
        $mform->addHelpButton('batch_customtext2', 'custom_welcome_message', 'enrol_select');
        $mform->disabledIf('batch_customtext2', 'batch_customtext2switch');

        // Message pour les inscrits sur la liste complémentaire.
        $label = get_string('edit_field_X', 'enrol_select', get_string('custom_welcome_message_on_wait_list', 'enrol_select'));
        $mform->addElement('checkbox', 'batch_customtext3switch', $label);

        $label = get_string('custom_welcome_message_on_wait_list', 'enrol_select');
        $mform->addElement('editor', 'batch_customtext3', $label, $attributes, $options);
        $mform->setType('batch_customtext3', PARAM_RAW);
        $mform->addHelpButton('batch_customtext3', 'custom_welcome_message', 'enrol_select');
        $mform->disabledIf('batch_customtext3', 'batch_customtext3switch');

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('submit', 'previewbutton', get_string('preview'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'batch_settings');
        $mform->setType('tab', PARAM_ALPHAEXT);
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     *
     * @return array The errors that were found.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Contrôle que les zones de texte ne sont pas vides.
        for ($i = 1; $i < 4; $i++) {
            $textswitch = sprintf('batch_customtext%sswitch', $i);
            $text = sprintf('batch_customtext%s', $i);

            if (isset($data[$textswitch]) === false) {
                // Personnalisation non activée.
                continue;
            }

            if (empty($data[$text]['text']) === true) {
                // Le texte de base est vide. L'utilisateur veut effacer le message.
                continue;
            }

            if (empty(trim(strip_tags($data[$text]['text']))) === false) {
                // Le texte n'est pas vide, même sans les tags HTML.
                continue;
            }

            $errors[$textswitch] = get_string('the_field_welcome_message_seems_to_be_empty', 'enrol_select');
        }

        return $errors;
    }
}
