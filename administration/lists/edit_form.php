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

use html_writer;

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant définir les libellés des listes d'inscriptions.
 *
 * @package    enrol_select
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_lists_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        [$formvalues, $defaults, $status] = $this->_customdata;

        // L'étiquette du champ est sous-titrée par l'affichage de la valeur par défaut.
        $label = get_custom_label('edit_status', $defaults['status']);
        $mform->addElement('text', 'status', $label);
        $mform->addHelpButton('status', 'edit_status', 'enrol_select', '', false, get_string('status_strexample', 'enrol_select'));

        $label = get_custom_label('edit_statusabbr', $defaults['statusabbr']);
        $mform->addElement('text', 'statusabbr', $label);

        $label = get_custom_label('edit_statusshort', $defaults['statusshort']);
        $mform->addElement('text', 'statusshort', $label);

        $label = get_custom_label('edit_listname', $defaults['listname']);
        $mform->addElement('text', 'listname', $label);
        $mform->addHelpButton('listname', 'edit_listname', 'enrol_select', '', false, self::get_validation_example(
            'listname',
            get_string('listname_strexample', 'enrol_select') // Précisions sur le format attendu.
        ));

        $label = get_custom_label('edit_description', $defaults['description']);
        $mform->addElement('text', 'description', $label);
        $mform->addHelpButton('description', 'edit_description', 'enrol_select', '', false, self::get_validation_example(
            'description',
            get_string('description_strexample', 'enrol_select')
        ));

        // Un bloc texte précise comment réinitialiser les valeurs sur les valeurs par défaut.
        $emptyfieldhelp = html_writer::tag(
            'div',
            get_string('default_reset_values', 'enrol_select'),
            ['class' => 'info small form-shortname text-muted']
        );
        $mform->addElement('html', $emptyfieldhelp);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Set default values.
        $this->set_data($formvalues);
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

        if (empty($data['listname']) == false) { // Pas de test de validation sur une chaîne vide.
            // Vérifier que le champs listname respecte le format souhaité.
            if (unformatstr($data['listname'], 'listname') == false) {
                $errors['listname'] = self::get_validation_example('listname', get_string('listname_strexample', 'enrol_select'));
            }
        }

        if (empty($data['description']) == false) { // Pas de test de validation sur une chaîne vide.
            // Vérifier que le champs description respecte le format souhaité.
            if (unformatstr($data['description'], 'description') == false) {
                $errors['description'] = self::get_validation_example(
                    'description',
                    get_string(
                        'description_strexample',
                        'enrol_select'
                    )
                );
            }
        }

        return $errors;
    }

    /**
     * Renvoie le message d'erreur explicitant la formulation à respecter pour un champ donné.
     *
     * @param string $strname le nom du champ.
     * @param string $example la chaîne qui remplace la valeur dans l'exemple.
     * @return string le message d'erreur
     */
    protected static function get_validation_example(string $strname, string $example): string {
        // Formulation à respecter.
        $formatexample = get_string($strname . '_strformat', 'enrol_select', '{' . $example . '}');
        // Message d'erreur + formulation.
        return $errors['description'] = get_string('formaterror', 'enrol_select', $formatexample);
    }
}
