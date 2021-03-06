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
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/cohort/lib.php');

class enrol_select_edit_form extends moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;

        list($instance, $plugin, $context, $cohorts, $roles, $enrolmethods, $calendars, $cards) = $this->_customdata;

        $datetimeoptions = array('optional' => true);

        // GÉNÉRAL.
        $mform->addElement('header', 'header', get_string('general'));

        // Activer la méthode d'inscription.
        // Note: pas de selectyesno parce que la valeur de mdl_enrol.status est inversée par rapport à la logique.
        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('enableinstance', 'enrol_select'), $options);

        // Nom de la méthode.
        $nameattribs = array('size' => '20', 'maxlength' => '255');
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'), $nameattribs);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        // Calendrier utilisé.
        $options = array();
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
            $mform->addElement('html', '<div class="alert alert-info">'.get_string('no_available_enrol_methods_desc', 'enrol_select').'</div>');
        } else {
            // Date de début des réinscriptions.
            $mform->addElement('date_time_selector', 'customint4', get_string('reenrolstartdate', 'enrol_select'), $datetimeoptions);
            $mform->disabledIf('customint4', 'customchar1', 'ne', 0);

            // Date de fin des réinscriptions.
            $mform->addElement('date_time_selector', 'customint5', get_string('reenrolenddate', 'enrol_select'), $datetimeoptions);
            $mform->disabledIf('customint5', 'customchar1', 'ne', 0);

            // Méthode de réinscription.
            $select = $mform->addElement('select', 'customint6', get_string('reenrolinstance', 'enrol_select'), $enrolmethods);
            $mform->addHelpButton('customint6', 'reenrolinstance', 'enrol_select');
        }

        // QUOTA.
        $mform->addElement('header', 'header', get_string('quotas', 'enrol_select'));

        // Activer les quotas.
        $mform->addElement('selectyesno', 'customint3', get_string('enablequotas', 'enrol_select'));
        $mform->setType('customint3', PARAM_INT);

        // Nombre de places sur la liste principale.
        $mform->addElement('text', 'customint1', get_string('max_places', 'enrol_select'));
        $mform->setType('customint1', PARAM_INT);
        $mform->disabledIf('customint1', 'customint3', 'eq', 0);

        // Nombre de places sur la liste complémentaire.
        $mform->addElement('text', 'customint2', get_string('max_waiting_places', 'enrol_select'), array('optional' => true));
        $mform->setType('customint2', PARAM_INT);
        $mform->disabledIf('customint2', 'customint3', 'eq', 0);

        // Cohortes.
        $mform->addElement('header', 'header', get_string('cohorts', 'enrol_select'));

        $options = array();
        foreach ($cohorts as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }

        if (count($options) === 0) {
            $mform->addElement('html', '<div class="alert alert-danger">'.get_string('no_available_cohorts', 'enrol_select').'</div>');
        } else {
            $attributes = array('size' => 10);
            $select = $mform->addElement('select', 'cohorts', get_string('selectcohorts', 'enrol_select'), $options, $attributes);
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
        } else {
            $select = $mform->addElement('select', 'roles', get_string('registertype', 'enrol_select'), $options, $instance->roles);
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
        } else {
            $attributes = array('size' => 10);
            $select = $mform->addElement('select', 'cards', 'Cartes requises', $options, $attributes);
            $select->setMultiple(true);
        }

        // Validation.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    public function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        list($instance, $plugin, $context) = $this->_customdata;

        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            // Vérifie que la date de fin des inscriptions est bien supérieure à la date de début.
            if (!empty($data['enrolenddate']) && $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddateerror', 'enrol_select');
            }

            // Vérifie que la date de fin du cours est bien supérieure à la date de début.
            if (!empty($data['customint8']) && $data['customint8'] < $data['customint7']) {
                $errors['customint8'] = get_string('courseenddateerror', 'enrol_select');
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
        }

        return $errors;
    }
}
