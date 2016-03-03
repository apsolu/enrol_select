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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Form class to create or to edit an area.
 */
class enrol_select_form extends moodleform {
    protected function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        list($instance, $roles) = $this->_customdata;

        // Course field.
        $mform->addElement('text', 'fullname', get_string('course'), array('readonly' => 1, 'size' => '48'));
        $mform->setType('fullname', PARAM_TEXT);

        // Roles field.
        if (empty($instance->role) || isset($instance->edit)) {
            if (count($roles) === 1) {
                $attributes = array('disabled' => 1, 'size' => '48');
                $mform->addElement('text', 'fakerole', get_string('role', 'local_apsolu_courses'), $attributes);
                $mform->setType('fakerole', PARAM_TEXT);
                $mform->setDefault('fakerole', current($roles));

                unset($instance->role);
                $mform->addElement('hidden', 'role', key($roles));
            } else {
                $mform->addElement('select', 'role', get_string('role', 'local_apsolu_courses'), $roles);
                $mform->addRule('role', get_string('required'), 'required', null, 'client');
            }
            $mform->setType('role', PARAM_INT);
        } else {
            $mform->addElement('text', 'role', get_string('role', 'local_apsolu_courses'), array('readonly' => 1, 'size' => '48'));
            $mform->setType('role', PARAM_TEXT);
            $instance->role = $roles[$instance->role];
        }

        // Submit buttons.
        if (empty($instance->role)) {
            $attributes = array('class' => 'btn btn-primary');
            $buttonarray[] = &$mform->createElement('submit', 'enrolbutton', get_string('enrol', 'enrol_select'), $attributes);
        } else {
            if (isset($instance->edit)) {
                $attributes = array('class' => 'btn btn-primary');
                $buttonarray[] = &$mform->createElement('submit', 'enrolbutton', get_string('save', 'admin'), $attributes);
            } else {
                if (count($roles) > 1) {
                    $label = get_string('edit_enrol', 'enrol_select');
                    $attributes = array('class' => 'btn btn-primary');
                    $buttonarray[] = &$mform->createElement('submit', 'editenrol', $label, $attributes);
                }

                $label = get_string('unenrol', 'enrol_select');
                $attributes = array('class' => 'btn btn-danger');
                $buttonarray[] = &$mform->createElement('submit', 'unenrolbutton', $label, $attributes);
            }
        }

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/enrol/select/overview.php';
        $attributes->class = 'btn btn-default apsolu-cancel-a';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu_courses', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        // Static text about roles.
        $mform->addElement('html', '<div class="alert alert-info"><p>Seule la première inscription par type de voeux est payante. Les inscriptions suivantes sont gratuites.</p>'.
            '<p>Example: 1 inscription en libre est également à 30€. 2 inscriptions en libre sont égales aussi à 30€ (et non 60€).</p></div>');

        // Hidden fields.
        $mform->addElement('hidden', 'enrolid', $instance->enrolid);
        $mform->setType('enrolid', PARAM_INT);

        // Set default values.
        $this->set_data($instance);
    }
}
