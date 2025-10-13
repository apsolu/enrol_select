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
 * Définition du formulaire pour configurer une instance enrol_select d'un cours.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Définition du formulaire pour configurer une instance enrol_select d'un cours.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_edit_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        [$instance, $plugin, $context, $cohorts, $roles, $enrolmethods, $calendars, $cards] = $this->_customdata;

        $datetimeoptions = ['optional' => true];

        // GÉNÉRAL.
        $mform->addElement('header', 'header', get_string('general'));

        // Activer la méthode d'inscription.
        // Note: pas de selectyesno parce que la valeur de mdl_enrol.status est inversée par rapport à la logique.
        $options = [
            ENROL_INSTANCE_ENABLED  => get_string('yes'),
            ENROL_INSTANCE_DISABLED => get_string('no'),
        ];
        $mform->addElement('select', 'status', get_string('enableinstance', 'enrol_select'), $options);

        // Nom de la méthode.
        $nameattribs = ['size' => '20', 'maxlength' => '255'];
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'), $nameattribs);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        // Calendrier utilisé.
        $options = [];
        foreach ($calendars as $calendar) {
            $options[$calendar->id] = $calendar->name;
        }
        $select = $mform->addElement('select', 'customchar1', get_string('calendars', 'local_apsolu'), $options);

        // DATE D'INSCRIPTION.
        $mform->addElement('header', 'header', get_string('enroldate', 'enrol_select'));

        // Date de début des inscriptions.
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_select'), $datetimeoptions);
        if (empty($instance->id) === true) {
            $mform->disabledIf('enrolstartdate', 'customchar1', 'ne', 0);
        }

        // Date de fin des inscriptions.
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_select'), $datetimeoptions);
        if (empty($instance->id) === true) {
            $mform->disabledIf('enrolenddate', 'customchar1', 'ne', 0);
        }

        // DATE DE FIN DES COURS.
        $mform->addElement('header', 'header', get_string('coursedate', 'enrol_select'));

        // Date de début du cours.
        $mform->addElement('date_time_selector', 'customint7', get_string('coursestartdate', 'enrol_select'), $datetimeoptions);
        $mform->disabledIf('customint7', 'customchar1', 'ne', 0);

        // Date de fin du cours.
        $mform->addElement('date_time_selector', 'customint8', get_string('courseenddate', 'enrol_select'), $datetimeoptions);
        $mform->disabledIf('customint8', 'customchar1', 'ne', 0);

        // DATE DE RÉINSCRIPTION.
        $mform->addElement('header', 'header', get_string('reenroldate', 'enrol_select'));

        if (count($enrolmethods) === 1) {
            $label = get_string('no_available_enrol_methods_desc', 'enrol_select');
            $mform->addElement('html', sprintf('<div class="alert alert-info">%s</div>', $label));
        } else {
            // Date de début des réinscriptions.
            $label = get_string('reenrolstartdate', 'enrol_select');
            $mform->addElement('date_time_selector', 'customint4', $label, $datetimeoptions);
            $mform->disabledIf('customint4', 'customchar1', 'ne', 0);

            // Date de fin des réinscriptions.
            $mform->addElement('date_time_selector', 'customint5', get_string('reenrolenddate', 'enrol_select'), $datetimeoptions);
            $mform->disabledIf('customint5', 'customchar1', 'ne', 0);

            // Méthode de réinscription.
            $select = $mform->addElement('select', 'customint6', get_string('reenrolinstance', 'enrol_select'), $enrolmethods);
            $mform->addHelpButton('customint6', 'reenrolinstance', 'enrol_select');
        }

        // PARAMÈTRES.
        $mform->addElement('header', 'header', get_string('settings'));

        // Activer les quotas.
        $mform->addElement('selectyesno', 'customint3', get_string('enablequotas', 'enrol_select'));
        $mform->setType('customint3', PARAM_INT);

        // Activer la remontée de liste automatique.
        $mform->addElement('selectyesno', 'customchar2', get_string('enable_automatic_list_filling', 'enrol_select'));
        $mform->addHelpButton('customchar2', 'enable_automatic_list_filling', 'enrol_select');
        $mform->setType('customchar2', PARAM_INT);
        $mform->disabledIf('customchar2', 'customint3', 'eq', 0);

        // Nombre de places sur la liste principale.
        $mform->addElement('text', 'customint1', get_string('max_places', 'enrol_select'));
        $mform->setType('customint1', PARAM_INT);
        $mform->disabledIf('customint1', 'customint3', 'eq', 0);

        // Nombre de places sur la liste complémentaire.
        $mform->addElement('text', 'customint2', get_string('max_waiting_places', 'enrol_select'), ['optional' => true]);
        $mform->setType('customint2', PARAM_INT);
        $mform->disabledIf('customint2', 'customint3', 'eq', 0);

        // Liste d'inscription par défaut.
        $options = [];
        $options[enrol_select_plugin::MAIN] = get_string('main_list', 'enrol_select');
        $options[enrol_select_plugin::ACCEPTED] = get_string('accepted_list', 'enrol_select');

        $mform->addElement('select', 'customchar3', get_string('default_enrolment_list', 'enrol_select'), $options);
        $mform->addHelpButton('customchar3', 'default_enrolment_list', 'enrol_select');
        $mform->setType('customchar3', PARAM_INT);

        // Cohortes.
        $mform->addElement('header', 'header', get_string('cohorts', 'enrol_select'));

        $options = [];
        foreach ($cohorts as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }

        if (count($options) === 0) {
            $label = get_string('no_available_cohorts', 'enrol_select');
            $mform->addElement('html', sprintf('<div class="alert alert-danger">%s</div>', $label));
        } else {
            $attributes = ['size' => 10];
            $select = $mform->addElement('select', 'cohorts', get_string('selectcohorts', 'enrol_select'), $options, $attributes);
            $select->setMultiple(true);
        }

        // Rôles.
        $mform->addElement('header', 'header', get_string('roles'));

        $options = [];
        foreach ($roles as $role) {
            $options[$role->id] = $role->localname;
        }

        if (count($options) === 0) {
            $label = get_string('no_available_roles', 'enrol_select');
            $mform->addElement('html', sprintf('<div class="alert alert-danger">%s</div>', $label));
        } else {
            $select = $mform->addElement('select', 'roles', get_string('registertype', 'enrol_select'), $options, $instance->roles);
            $select->setMultiple(true);
        }

        // Paiements.
        $mform->addElement('header', 'header', get_string('payments'));

        $options = [];
        foreach ($cards as $card) {
            $options[$card->id] = $card->fullname;
        }

        if (count($options) === 0) {
            $mform->addElement('html', html_writer::div(get_string('no_available_prices', 'enrol_select'), 'alert alert-info'));
        } else {
            $attributes = ['size' => 10];
            $select = $mform->addElement('select', 'cards', 'Cartes requises', $options, $attributes);
            $select->setMultiple(true);

            $options = ['defaultunit' => MINSECS, 'units' => [MINSECS, HOURSECS]];
            $mform->addElement('duration', 'customdec1', get_string('payment_deadline', 'enrol_select'), $options);
            $mform->addHelpButton('customdec1', 'payment_deadline', 'enrol_select');
            $mform->setType('customdec1', PARAM_INT);
        }

        // Messages de bienvenue.
        $options = ['cols' => '60', 'rows' => '16'];

        // Message pour les inscrits sur la liste des acceptés.
        $mform->addElement('header', 'header', get_string('welcome_message_on_accepted_list', 'enrol_select'));

        $label = get_string('send_welcome_message_to_users_on_accepted_list', 'enrol_select');
        $mform->addElement('selectyesno', 'customtext1switch', $label);
        $mform->addHelpButton('customtext1switch', 'custom_welcome_message', 'enrol_select');

        $mform->addElement('editor', 'customtext1', get_string('custom_welcome_message', 'enrol_select'), $options);
        $mform->setType('customtext1', PARAM_RAW);
        $mform->disabledIf('customtext1', 'customtext1switch', 'eq', 0);

        // Message pour les inscrits sur la liste principale.
        $mform->addElement('header', 'header', get_string('welcome_message_on_main_list', 'enrol_select'));

        $label = get_string('send_welcome_message_to_users_on_main_list', 'enrol_select');
        $mform->addElement('selectyesno', 'customtext2switch', $label);
        $mform->addHelpButton('customtext2switch', 'custom_welcome_message', 'enrol_select');

        $mform->addElement('editor', 'customtext2', get_string('custom_welcome_message', 'enrol_select'), $options);
        $mform->setType('customtext2', PARAM_RAW);
        $mform->disabledIf('customtext2', 'customtext2switch', 'eq', 0);

        // Message pour les inscrits sur la liste complémentaire.
        $mform->addElement('header', 'header', get_string('welcome_message_on_wait_list', 'enrol_select'));

        $label = get_string('send_welcome_message_to_users_on_wait_list', 'enrol_select');
        $mform->addElement('selectyesno', 'customtext3switch', $label);
        $mform->addHelpButton('customtext3switch', 'custom_welcome_message', 'enrol_select');

        $mform->addElement('editor', 'customtext3', get_string('custom_welcome_message', 'enrol_select'), $options);
        $mform->setType('customtext3', PARAM_RAW);
        $mform->disabledIf('customtext3', 'customtext3switch', 'eq', 0);

        // Validation.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
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

        if ($data['status'] != ENROL_INSTANCE_ENABLED) {
            return $errors;
        }

        // Vérifie que la date de fin des inscriptions est bien supérieure à la date de début.
        if (!empty($data['enrolenddate']) && $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('enrolenddateerror', 'enrol_select');
        }

        // Vérifie que la date de fin du cours est bien supérieure à la date de début.
        if (!empty($data['customint8']) && $data['customint8'] < $data['customint7']) {
            $errors['customint8'] = get_string('courseenddateerror', 'enrol_select');
        }

        // Contrôle que le début des inscriptions démarrent avant le début des cours.
        if (empty($data['enrolstartdate']) === false && empty($data['customint7']) === false) {
            if ($data['enrolstartdate'] > $data['customint7']) {
                $str = get_string('the_enrol_start_date_must_be_prior_to_the_course_start_date', 'local_apsolu');
                $errors['enrolstartdate'] = $str;

                $str = get_string('the_course_start_date_must_be_after_to_the_enrol_start_date', 'local_apsolu');
                $errors['customint7'] = $str;
            }
        }

        // Contrôle que la fin des inscriptions se terminent avant la fin des cours.
        if (empty($data['enrolenddate']) === false && empty($data['customint8']) === false) {
            if ($data['enrolenddate'] > $data['customint8']) {
                $str = get_string('the_enrol_end_date_must_be_prior_to_the_course_end_date', 'local_apsolu');
                $errors['enrolenddate'] = $str;

                $str = get_string('the_course_end_date_must_be_after_to_the_enrol_end_date', 'local_apsolu');
                $errors['customint8'] = $str;
            }
        }

        // Vérifie que la date de fin des réinscriptions est bien supérieure à la date de début.
        if (!empty($data['customint5']) && $data['customint5'] < $data['customint4']) {
            $errors['customint5'] = get_string('reenrolenddateerror', 'enrol_select');
        }

        // Vérifie que la date de début des réinscriptions est renseignée lorsque la date de fin l'est également.
        if (empty($data['customint4']) && !empty($data['customint5'])) {
            $errors['customint4'] = get_string('reenrolstartdatemissingerror', 'enrol_select');
        }

        // Vérifie que la date de fin des réinscriptions est renseignée lorsque la date de début l'est également.
        if (!empty($data['customint4']) && empty($data['customint5'])) {
            $errors['customint5'] = get_string('reenrolenddatemissingerror', 'enrol_select');
        }

        // Contrôle qu'un calendrier est activé lorsqu'au moins une carte de paiement est sélectionnée.
        if (isset($data['cards'][0]) === true && empty($data['customchar1']) === true) {
            $errors['customchar1'] = get_string('you_must_set_a_calendar_so_that_payments_can_apply', 'enrol_select');
        }

        // Contrôle que la liste d'inscription par défaut est "acceptée" lorsqu'un délai de paiement est activé.
        if (isset($data['customdec1']) === true && empty($data['customdec1']) === false) {
            $quotaenabled = (isset($data['customint3']) === true && empty($data['customint3']) === false);
            if ($quotaenabled === true && (isset($data['customchar2']) === false || empty($data['customchar2']) === false)) {
                $label = get_string('the_delay_cannot_be_combined_with_the_automatic_list_filling', 'enrol_select');
                $errors['customdec1'] = $label;
            }

            if (isset($data['customchar3']) === false || $data['customchar3'] !== enrol_select_plugin::ACCEPTED) {
                $errors['customdec1'] = get_string('the_delay_cannot_be_set_if_the_default_list_is_accepted', 'enrol_select');
            }

            if ($data['customdec1'] < 1200) {
                $label = get_string('the_delay_cannot_be_set_to_a_value_of_less_than_20_minutes', 'enrol_select');
                $errors['customdec1'] = $label;
            }

            // TODO: à cause d'un problème de stockage de données en base, on empêche de saisir un délai de paiement
            // supérieur à 99999 secondes.
            if ($data['customdec1'] > 99999) {
                $label = get_string('it_is_currently_not_possible_to_indicate_a_duration_greater_than_one_day', 'enrol_select');
                $errors['customdec1'] = $label;
            }
        }

        // Contrôle que les zones de texte ne sont pas vides.
        for ($i = 1; $i < 4; $i++) {
            $textswitch = sprintf('customtext%sswitch', $i);
            $text = sprintf('customtext%s', $i);

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
