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
 * Classe pour le formulaire permettant de configurer le paramétrage par défaut des méthodes d'inscription par voeux.
 *
 * @package    enrol_select
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/enrol/select/lib.php');

/**
 * Classe pour le formulaire permettant de configurer le paramétrage par défaut des méthodes d'inscription par voeux.
 *
 * @package    enrol_select
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_default_settings_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        [$defaults, $calendars, $cohorts, $roles, $cards] = $this->_customdata;

        $mform->addElement('header', 'header1', get_string('general'));

        // Calendrier utilisé.
        $options = [];
        foreach ($calendars as $calendar) {
            $options[$calendar->id] = $calendar->name;
        }
        $select = $mform->addElement('select', 'default_customchar1', get_string('calendars', 'local_apsolu'), $options);

        $mform->addElement('header', 'header2', get_string('settings'));
        $mform->setExpanded('header2');

        // Activer les quotas.
        $mform->addElement('selectyesno', 'default_customint3', get_string('enablequotas', 'enrol_select'));
        $mform->setType('default_customint3', PARAM_INT);

        // Activer la remontée de liste automatique.
        $mform->addElement('selectyesno', 'default_customchar2', get_string('enable_automatic_list_filling', 'enrol_select'));
        $mform->addHelpButton('default_customchar2', 'enable_automatic_list_filling', 'enrol_select');
        $mform->setType('default_customchar2', PARAM_INT);
        $mform->disabledIf('default_customchar2', 'default_customint3', 'eq', 0);

        // Nombre de places sur la liste principale.
        $mform->addElement('text', 'default_customint1', get_string('max_places', 'enrol_select'));
        $mform->setType('default_customint1', PARAM_INT);
        $mform->disabledIf('default_customint1', 'default_customint3', 'eq', 0);

        // Nombre de places sur la liste complémentaire.
        $label = get_string('max_waiting_places', 'enrol_select');
        $mform->addElement('text', 'default_customint2', $label, ['optional' => true]);
        $mform->setType('default_customint2', PARAM_INT);
        $mform->disabledIf('default_customint2', 'default_customint3', 'eq', 0);

        // Liste d'inscription par défaut.
        $options = [];
        $options[enrol_select_plugin::MAIN] = get_string('main_list', 'enrol_select');
        $options[enrol_select_plugin::ACCEPTED] = get_string('accepted_list', 'enrol_select');

        $mform->addElement('select', 'default_customchar3', get_string('default_enrolment_list', 'enrol_select'), $options);
        $mform->addHelpButton('default_customchar3', 'default_enrolment_list', 'enrol_select');
        $mform->setType('default_customchar3', PARAM_INT);

        // Cohortes.
        $mform->addElement('header', 'header3', get_string('cohorts', 'enrol_select'));
        $mform->setExpanded('header3');

        $options = [];
        foreach ($cohorts as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }

        if (count($options) === 0) {
            $label = get_string('no_available_cohorts', 'enrol_select');
            $mform->addElement('html', sprintf('<div class="alert alert-danger">%s</div>', $label));
            $mform->addElement('hidden', 'default_cohorts', '');
            $mform->setType('default_cohorts', PARAM_ALPHANUM);
        } else {
            $attributes = ['size' => 10];
            $label = get_string('selectcohorts', 'enrol_select');
            $select = $mform->addElement('select', 'default_cohorts', $label, $options, $attributes);
            $select->setMultiple(true);
        }

        // Rôles.
        $mform->addElement('header', 'header4', get_string('roles'));
        $mform->setExpanded('header4');

        $options = [];
        foreach ($roles as $role) {
            $options[$role->id] = $role->localname;
        }

        if (count($options) === 0) {
            $label = get_string('no_available_roles', 'enrol_select');
            $mform->addElement('html', sprintf('<div class="alert alert-danger">%s</div>'));
            $mform->addElement('hidden', 'default_roles', '');
            $mform->setType('default_roles', PARAM_ALPHANUM);
        } else {
            $attributes = ['size' => 5];
            $label = get_string('registertype', 'enrol_select');
            $select = $mform->addElement('select', 'default_roles', $label, $options, $attributes);
            $select->setMultiple(true);
        }

        // Paiements.
        $mform->addElement('header', 'header5', get_string('payments'));
        $mform->setExpanded('header5');

        $options = [];
        foreach ($cards as $card) {
            $options[$card->id] = $card->fullname;
        }

        if (count($options) === 0) {
            $mform->addElement('html', html_writer::div(get_string('no_available_prices', 'enrol_select'), 'alert alert-info'));
            $mform->addElement('hidden', 'default_cards', '');
            $mform->setType('default_cards', PARAM_ALPHANUM);
        } else {
            $attributes = ['size' => 10];
            $select = $mform->addElement('select', 'default_cards', 'Cartes requises', $options, $attributes);
            $select->setMultiple(true);

            $options = ['defaultunit' => MINSECS, 'units' => [MINSECS, HOURSECS]];
            $mform->addElement('duration', 'default_customdec1', get_string('payment_deadline', 'enrol_select'), $options);
            $mform->addHelpButton('default_customdec1', 'payment_deadline', 'enrol_select');
            $mform->setType('default_customdec1', PARAM_INT);
        }

        // Messages de bienvenue.
        $options = ['cols' => '60', 'rows' => '8'];
        $mform->addElement('header', 'header6', get_string('welcome_messages', 'enrol_select'));
        $mform->setExpanded('header6');

        // Message pour les inscrits sur la liste des acceptés.
        $label = get_string('send_welcome_message_to_users_on_accepted_list', 'enrol_select');
        $mform->addElement('selectyesno', 'default_customtext1switch', $label);
        $mform->addHelpButton('default_customtext1switch', 'custom_welcome_message', 'enrol_select');

        $label = get_string('custom_welcome_message', 'enrol_select');
        $mform->addElement('editor', 'default_customtext1', $label, $options);
        $mform->setType('default_customtext1', PARAM_RAW);
        $mform->disabledIf('default_customtext1', 'default_customtext1switch', 'eq', 0);

        // Message pour les inscrits sur la liste principale.
        $label = get_string('send_welcome_message_to_users_on_main_list', 'enrol_select');
        $mform->addElement('selectyesno', 'default_customtext2switch', $label);
        $mform->addHelpButton('default_customtext2switch', 'custom_welcome_message', 'enrol_select');

        $mform->addElement('editor', 'default_customtext2', get_string('custom_welcome_message', 'enrol_select'), $options);
        $mform->setType('default_customtext2', PARAM_RAW);
        $mform->disabledIf('default_customtext2', 'default_customtext2switch', 'eq', 0);

        // Message pour les inscrits sur la liste complémentaire.
        $label = get_string('send_welcome_message_to_users_on_wait_list', 'enrol_select');
        $mform->addElement('selectyesno', 'default_customtext3switch', $label);
        $mform->addHelpButton('default_customtext3switch', 'custom_welcome_message', 'enrol_select');

        $mform->addElement('editor', 'default_customtext3', get_string('custom_welcome_message', 'enrol_select'), $options);
        $mform->setType('default_customtext3', PARAM_RAW);
        $mform->disabledIf('default_customtext3', 'default_customtext3switch', 'eq', 0);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'settings');
        $mform->setType('tab', PARAM_ALPHAEXT);

        // Set default values.
        $this->set_data($defaults);
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

        // Contrôle qu'un calendrier est activé lorsqu'au moins une carte de paiement est sélectionnée.
        if (isset($data['default_cards'][0]) === true && empty($data['default_customchar1']) === true) {
            $errors['default_customchar1'] = get_string('you_must_set_a_calendar_so_that_payments_can_apply', 'enrol_select');
        }

        // Contrôle que la liste d'inscription par défaut est "acceptée" lorsqu'un délai de paiement est activé.
        if (isset($data['default_customdec1']) === true && empty($data['default_customdec1']) === false) {
            $quotaenabled = (isset($data['default_customint3']) === true && empty($data['default_customint3']) === false);
            if (
                $quotaenabled === true &&
                (isset($data['default_customchar2']) === false || empty($data['default_customchar2']) === false)
            ) {
                $label = get_string('the_delay_cannot_be_combined_with_the_automatic_list_filling', 'enrol_select');
                $errors['default_customdec1'] = $label;
            }

            if (isset($data['default_customchar3']) === false || $data['default_customchar3'] !== enrol_select_plugin::ACCEPTED) {
                $errors['default_customdec1'] = get_string(
                    'the_delay_cannot_be_set_if_the_default_list_is_accepted',
                    'enrol_select'
                );
            }

            if ($data['default_customdec1'] < 1200) {
                $label = get_string('the_delay_cannot_be_set_to_a_value_of_less_than_20_minutes', 'enrol_select');
                $errors['default_customdec1'] = $label;
            }
        }

        // TODO: à cause d'un problème de stockage de données en base, on empêche de saisir un délai de paiement
        // supérieur à 99999 secondes.
        if ($data['default_customdec1'] > 99999) {
            $label = get_string('it_is_currently_not_possible_to_indicate_a_duration_greater_than_one_day', 'enrol_select');
            $errors['default_customdec1'] = $label;
        }

        // Contrôle que les zones de texte ne sont pas vides.
        for ($i = 1; $i < 4; $i++) {
            $textswitch = sprintf('default_customtext%sswitch', $i);
            $text = sprintf('default_customtext%s', $i);

            if (empty($data[$textswitch]) === true) {
                // Personnalisation non activée.
                continue;
            }

            if (empty(trim(strip_tags($data[$text]['text']))) === false) {
                // Le texte n'est pas vide.
                continue;
            }

            $errors[$textswitch] = get_string('the_field_welcome_message_seems_to_be_empty', 'enrol_select');
        }

        return $errors;
    }
}
