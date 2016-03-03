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

        list($instance, $plugin, $context, $cohorts, $roles) = $this->_customdata;

        // Activer la méthode d'inscription.
        // Note: pas de selectyesno parce que la valeur de mdl_enrol.status est inversée par rapport à la logique.
        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', 'Activer cette méthode d\'inscription', $options);

        // Nom de la méthode.
        $nameattribs = array('size' => '20', 'maxlength' => '255');
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'), $nameattribs);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        // Date d'inscription.
        $mform->addElement('header', 'header', 'Dates d\'inscription');
        $datetimeoptions = array('optional' => true);

        // Date de début des inscriptions.
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_select'), $datetimeoptions);

        // Date de fin des inscriptions.
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_select'), $datetimeoptions);

        // Quota.
        $mform->addElement('header', 'header', 'Quotas');

        // Activer les quotas.
        $mform->addElement('selectyesno', 'customint3', 'Activer les quotas');
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
        $mform->addElement('header', 'header', 'Cohortes');

        $options = array();
        foreach ($cohorts as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }
        $attributes = array('size' => 10);
        $select = $mform->addElement('select', 'cohorts', 'Sélectionner les populations (cohortes)', $options, $attributes);
        $select->setMultiple(true);

        // Rôles.
        $mform->addElement('header', 'header', 'Rôles');

        $options = array();
        foreach ($roles as $role) {
            $options[$role->id] = $role->localname;
        }
        $select = $mform->addElement('select', 'roles', 'Type d\'inscription', $options, $instance->roles);
        $select->setMultiple(true);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        if (enrol_accessing_via_instance($instance)) {
            $mform->addElement('static', 'apsoluwarn', get_string('instanceeditapsoluwarning', 'core_enrol'), get_string('instanceeditapsoluwarningtext', 'core_enrol'));
        }

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    public function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        list($instance, $plugin, $context) = $this->_customdata;

        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_select');
            }
        }

        return $errors;
    }
}
