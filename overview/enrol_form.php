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

defined('MOODLE_INTERNAL') || die;

use html_writer;

require_once($CFG->libdir . '/formslib.php');

/**
 * Définition du formulaire pour s'inscire à un cours.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2
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
        [$instance, $roles, $federationrequirement] = $this->_customdata;

        $title = html_writer::tag('div', html_writer::tag('h5', 'Inscription'), ['class' => 'enrol-popup-header']);
        $mform->addElement('html', $title);

        // Course field.
        $attr = ['name' => 'fakefullname', 'class' => 'apsolu-custom-field' ];
        $fullname = html_writer::tag('div', $instance->fullname, $attr);
        $fullnamearr[] = &$mform->createElement('html', $fullname);
        $mform->addGroup($fullnamearr, 'fullnamearr', get_string('course'), [' '], false, ['class' => 'form-text mb-4']);

        $mform->addElement('hidden', 'fullname', null);
        $mform->setType('fullname', PARAM_TEXT);

        // Location field.
        if (isset($instance->location)) {
            $attr = ['name' => 'fakelocation'];
            if (isset($instance->site)) {
                $location = html_writer::tag(
                    'div',
                    html_writer::tag('strong', $instance->site) . ' - ' . $instance->location,
                    $attr
                );
                $locationlabel = get_string('site_and_location', 'local_apsolu');
            } else {
                $location = html_writer::tag('div', $instance->location, $attr);
                $locationlabel = get_string('location', 'local_apsolu');
            }

            $locationarr[] = &$mform->createElement('html', $location);

            $mform->addGroup($locationarr, 'locationarr', $locationlabel, [' '], false, ['class' => 'form-text mb-4']);
        }

        // Roles field.
        if (empty($instance->role) || isset($instance->edit)) {
            // Inscription ou modification d'inscription.
            $attributes = count($roles) === 1 ? ['disabled' => 1] : [];
            $mform->addElement('select', 'role', get_string('role', 'local_apsolu'), $roles, $attributes);
            $mform->setType('role', PARAM_INT);

            // Federations fields.
            if ($federationrequirement !== APSOLU_FEDERATION_REQUIREMENT_FALSE) {
                $isrequired = $federationrequirement === APSOLU_FEDERATION_REQUIREMENT_TRUE;
                $attributes = $isrequired ? ['disabled' => 1] : [];
                $federationvalue = $isrequired ? 1 : $instance->federation;
                $mform->addElement('selectyesno', 'federation', get_string(
                    $isrequired ? 'federation_required' : 'federation_optional',
                    'enrol_select'
                ), $attributes);
                $mform->addHelpButton('federation', $isrequired ? 'federation_required' : 'federation_optional', 'enrol_select');
                $mform->setDefault('federation', $federationvalue);
                $mform->setType('federation', PARAM_INT);
            }

            // Acceptation des recommandations médicales.
            if (
                empty($instance->showpolicy) === false &&
                (empty($CFG->sitepolicy) === false || is_file($CFG->dirroot . '/policy.html') === true)
            ) {
                $url = $CFG->sitepolicy;
                if (empty($url) === true) {
                    $url = $CFG->wwwroot . '/policy.html';
                }
                $policy[] = &$mform->createElement('checkbox', 'policy', get_string('policyagree', 'enrol_select', $url));
                $mform->setDefault('policy', 0);
                $mform->setType('policy', PARAM_INT);
                $mform->addGroup($policy, 'policies', '', [' '], false);
                $mform->addRule('policies', get_string('required'), 'required', null, 'client');
            }
        } else {
            // Utilisateur déjà inscrit : on propose la désinscription ou la modification de l'inscription.
            $attr = ['class' => 'col-md-9 d-flex flex-wrap pb-0 pe-md-0 felement', 'name' => 'fakerole' ];
            $role = html_writer::tag('div', $roles[$instance->role], $attr);
            $rolearr[] = &$mform->createElement('html', $role);
            $mform->addGroup($rolearr, 'rolearr', get_string('role', 'local_apsolu'), [' '], false, ['class' => 'form-text mb-4']);

            $mform->addElement('hidden', 'role', null);
            $mform->setType('role', PARAM_TEXT);
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
        $attributes->href = $CFG->wwwroot . '/enrol/select/overview.php';
        $attributes->class = 'btn btn-default btn-secondary apsolu-cancel-a';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        // Hidden fields.
        $mform->addElement('hidden', 'enrolid', $instance->enrolid);
        $mform->setType('enrolid', PARAM_INT);

        // Set default values.
        $this->set_data($instance);
    }
}
