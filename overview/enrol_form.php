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
 * Définition du formulaire pour s'inscire à un cours.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Définition du formulaire pour s'inscire à un cours.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    protected function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        list($instance, $roles, $federations, $federationrequirement) = $this->_customdata;

        // Course field.
        $mform->addElement('text', 'fullname', get_string('course'), array('readonly' => 1, 'size' => '48'));
        $mform->setType('fullname', PARAM_TEXT);

        // Roles field.
        if (empty($instance->role) || isset($instance->edit)) {
            // Inscription ou modification d'inscription.
            if (count($roles) === 1) {
                $attributes = array('disabled' => 1, 'size' => '48');
                $mform->addElement('text', 'fakerole', get_string('role', 'local_apsolu'), $attributes);
                $mform->setType('fakerole', PARAM_TEXT);
                $mform->setDefault('fakerole', current($roles));

                unset($instance->role);
                $mform->addElement('hidden', 'role', key($roles));
            } else {
                $mform->addElement('select', 'role', get_string('role', 'local_apsolu'), $roles);
                $mform->addRule('role', get_string('required'), 'required', null, 'client');
            }
            $mform->setType('role', PARAM_INT);

            // Federations fields.
            if ($federations !== array()) {
                $mform->addElement('select', 'federation', get_string('main_sport', 'enrol_select'), $federations);
                $mform->addRule('federation', get_string('required'), 'required', null, 'client');
                $mform->setType('federation', PARAM_INT);
            }

            if ($federationrequirement === APSOLU_FEDERATION_REQUIREMENT_TRUE) {
                $attributes = array('disabled' => 1, 'size' => '48');
                $mform->addElement('text', 'fakefederation', get_string('federation_required', 'enrol_select'), $attributes);
                $mform->addHelpButton('fakefederation', 'federation_required', 'enrol_select');
                $mform->setType('fakefederation', PARAM_TEXT);
                $mform->setDefault('fakefederation', get_string('yes'));

                $mform->addElement('hidden', 'federation', '1');
                $mform->setType('federation', PARAM_INT);
            } else if ($federationrequirement === APSOLU_FEDERATION_REQUIREMENT_OPTIONAL) {
                $mform->addElement('selectyesno', 'federation', get_string('federation_optional', 'enrol_select'));
                $mform->addHelpButton('federation', 'federation_optional', 'enrol_select');
                $mform->setType('federation', PARAM_INT);
            }

            // Acceptation des recommandations médicales.
            if (empty($instance->showpolicy) === false && empty($CFG->sitepolicy) === false) {
                $mform->addElement('checkbox', 'policy', get_string('policyagree', 'enrol_select', $CFG->sitepolicy));
                $mform->addRule('policy', get_string('required'), 'required', null, 'client');
            }
        } else {
            // Désinscription.
            $mform->addElement('text', 'role', get_string('role', 'local_apsolu'), array('readonly' => 1, 'size' => '48'));
            $mform->setType('role', PARAM_TEXT);
            $instance->role = $roles[$instance->role];
        }

        // Submit buttons.
        if (empty($instance->role)) {
            $buttonarray[] = &$mform->createElement('submit', 'enrolbutton', get_string('enrol', 'enrol_select'));
        } else {
            if (isset($instance->edit)) {
                $buttonarray[] = &$mform->createElement('submit', 'enrolbutton', get_string('save', 'admin'));
            } else {
                if (count($roles) > 1) {
                    $label = get_string('edit_enrol', 'enrol_select');
                    $buttonarray[] = &$mform->createElement('submit', 'editenrol', $label);
                }

                $label = get_string('unenrol', 'enrol_select');
                $buttonarray[] = &$mform->createElement('submit', 'unenrolbutton', $label);
            }
        }

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/enrol/select/overview.php';
        $attributes->class = 'btn btn-default btn-secondary apsolu-cancel-a';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Hidden fields.
        $mform->addElement('hidden', 'enrolid', $instance->enrolid);
        $mform->setType('enrolid', PARAM_INT);

        // Set default values.
        $this->set_data($instance);
    }
}
