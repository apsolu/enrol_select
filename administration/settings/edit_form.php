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

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');

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

        list($defaults, $calendars, $cohorts, $roles, $cards) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('general'));

        // Calendrier utilisé.
        $options = array();
        foreach ($calendars as $calendar) {
            $options[$calendar->id] = $calendar->name;
        }
        $select = $mform->addElement('select', 'default_customchar1', get_string('calendars', 'local_apsolu'), $options);

        $mform->addElement('header', 'header', get_string('settings'));

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
        $mform->addElement('text', 'default_customint2', get_string('max_waiting_places', 'enrol_select'), array('optional' => true));
        $mform->setType('default_customint2', PARAM_INT);
        $mform->disabledIf('default_customint2', 'default_customint3', 'eq', 0);

        // Liste d'inscription par défaut.
        $options = array();
        $options[enrol_select_plugin::MAIN] = get_string('main_list', 'enrol_select');
        $options[enrol_select_plugin::ACCEPTED] = get_string('accepted_list', 'enrol_select');

        $mform->addElement('select', 'default_customchar3', get_string('default_enrolment_list', 'enrol_select'), $options);
        $mform->addHelpButton('default_customchar3', 'default_enrolment_list', 'enrol_select');
        $mform->setType('default_customchar3', PARAM_INT);

        // Cohortes.
        $mform->addElement('header', 'header', get_string('cohorts', 'enrol_select'));

        $options = array();
        foreach ($cohorts as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }

        if (count($options) === 0) {
            $mform->addElement('html', '<div class="alert alert-danger">'.get_string('no_available_cohorts', 'enrol_select').'</div>');
            $mform->addElement('hidden', 'default_cohorts', '');
            $mform->setType('default_cohorts', PARAM_ALPHANUM);
        } else {
            $attributes = array('size' => 10);
            $select = $mform->addElement('select', 'default_cohorts', get_string('selectcohorts', 'enrol_select'), $options, $attributes);
            $select->setMultiple(true);
        }

        // Rôles.
        $mform->addElement('header', 'header', get_string('roles'));

        $options = array();
        foreach ($roles as $role) {
            $options[$role->id] = $role->localname;
        }

        if (count($options) === 0) {
            $mform->addElement('html', '<div class="alert alert-danger">'.get_string('no_available_roles', 'enrol_select').'</div>');
            $mform->addElement('hidden', 'default_roles', '');
            $mform->setType('default_roles', PARAM_ALPHANUM);
        } else {
            $attributes = array('size' => 5);
            $select = $mform->addElement('select', 'default_roles', get_string('registertype', 'enrol_select'), $options, $attributes);
            $select->setMultiple(true);
        }

        // Paiements.
        $mform->addElement('header', 'header', get_string('payments'));

        $options = array();
        foreach ($cards as $card) {
            $options[$card->id] = $card->fullname;
        }

        if (count($options) === 0) {
            $mform->addElement('html', '<div class="alert alert-info">'.get_string('no_available_prices', 'enrol_select').'</div>');
            $mform->addElement('hidden', 'default_cards', '');
            $mform->setType('default_cards', PARAM_ALPHANUM);
        } else {
            $attributes = array('size' => 10);
            $select = $mform->addElement('select', 'default_cards', 'Cartes requises', $options, $attributes);
            $select->setMultiple(true);
        }

        // Messages de bienvenue.
        $options = array('cols' => '60', 'rows' => '8');
        $mform->addElement('header', 'header', get_string('welcome_messages', 'enrol_select'));

        // Message pour les inscrits sur la liste des acceptés.
        $mform->addElement('selectyesno', 'default_customtext1switch', get_string('send_welcome_message_to_users_on_accepted_list', 'enrol_select'));
        $mform->addHelpButton('default_customtext1switch', 'custom_welcome_message', 'enrol_select');

        $mform->addElement('editor', 'default_customtext1', get_string('custom_welcome_message', 'enrol_select'), $options);
        $mform->setType('default_customtext1', PARAM_RAW);
        $mform->disabledIf('default_customtext1', 'default_customtext1switch', 'eq', 0);

        // Message pour les inscrits sur la liste principale.
        $mform->addElement('selectyesno', 'default_customtext2switch', get_string('send_welcome_message_to_users_on_main_list', 'enrol_select'));
        $mform->addHelpButton('default_customtext2switch', 'custom_welcome_message', 'enrol_select');

        $mform->addElement('editor', 'default_customtext2', get_string('custom_welcome_message', 'enrol_select'), $options);
        $mform->setType('default_customtext2', PARAM_RAW);
        $mform->disabledIf('default_customtext2', 'default_customtext2switch', 'eq', 0);

        // Message pour les inscrits sur la liste complémentaire.
        $mform->addElement('selectyesno', 'default_customtext3switch', get_string('send_welcome_message_to_users_on_wait_list', 'enrol_select'));
        $mform->addHelpButton('default_customtext3switch', 'custom_welcome_message', 'enrol_select');

        $mform->addElement('editor', 'default_customtext3', get_string('custom_welcome_message', 'enrol_select'), $options);
        $mform->setType('default_customtext3', PARAM_RAW);
        $mform->disabledIf('default_customtext3', 'default_customtext3switch', 'eq', 0);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'tab', 'settings');
        $mform->setType('tab', PARAM_ALPHANUM);

        // Set default values.
        $this->set_data($defaults);
    }
}