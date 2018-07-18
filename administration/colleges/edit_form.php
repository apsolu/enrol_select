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

class apsolu_colleges_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        list($data, $roles, $cohorts) = $this->_customdata;

        $nameattribs = array('size' => '20', 'maxlength' => '255');
        $mform->addElement('text', 'name', 'Libellé de la population', $nameattribs);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Roles.
        $options = array();
        foreach ($roles as $role) {
            $options[$role->id] = $role->localname;
        }
        $mform->addElement('select', 'roleid', 'Sélectionner un rôle', $options);

        // Cohorts.
        $options = array();
        foreach ($cohorts as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }
        $select = $mform->addElement('select', 'cohorts', 'Sélectionner les cohortes', $options, array('size' => 10));
        $select->setMultiple(true);

        // Limit.
        $mform->addElement('text', 'maxwish', 'Nombre maximum de voeux');
        $mform->setType('maxwish', PARAM_INT);
        $mform->addRule('maxwish', get_string('required'), 'required', null, 'client');

        // Limit.
        $mform->addElement('text', 'minregister', 'Nombre d\'inscription minimum');
        $mform->setType('minregister', PARAM_INT);
        $mform->addRule('minregister', get_string('required'), 'required', null, 'client');

        // Limit.
        $mform->addElement('text', 'maxregister', 'Nombre d\'inscription maximum');
        $mform->setType('maxregister', PARAM_INT);
        $mform->addRule('maxregister', get_string('required'), 'required', null, 'client');

        // Limit.
        $mform->addElement('text', 'userprice', 'Tarif étudiant');
        $mform->setType('userprice', PARAM_FLOAT);
        $mform->addRule('userprice', get_string('required'), 'required', null, 'client');

        // Limit.
        $mform->addElement('text', 'institutionprice', 'Tarif établissement');
        $mform->setType('institutionprice', PARAM_FLOAT);
        $mform->addRule('institutionprice', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $a = new stdClass();
        $a->href = $CFG->wwwroot.'/enrol/select/administration.php?tab=colleges';
        $a->class = 'btn btn-default';

        $attributes = array('class' => 'btn btn-primary');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'), $attributes);
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $a));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        $mform->addElement('hidden', 'id', $data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_TEXT);

        $this->set_data($data);
    }
}
