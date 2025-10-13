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
 * Classe pour le formulaire permettant de configurer les populations.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Classe pour le formulaire permettant de configurer les populations.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class apsolu_colleges_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        [$data, $roles, $cohorts] = $this->_customdata;

        $nameattribs = ['size' => '20', 'maxlength' => '255'];
        $mform->addElement('text', 'name', 'Libellé de la population', $nameattribs);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Roles.
        $options = [];
        foreach ($roles as $role) {
            $options[$role->id] = $role->localname;
        }
        $mform->addElement('select', 'roleid', 'Sélectionner un rôle', $options);

        // Cohorts.
        $options = [];
        foreach ($cohorts as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }
        $select = $mform->addElement('select', 'cohorts', 'Sélectionner les cohortes', $options, ['size' => 10]);
        $select->setMultiple(true);

        // Limite de voeux.
        $mform->addElement('text', 'maxwish', get_string('maximum_wishes', 'enrol_select'));
        $mform->setType('maxwish', PARAM_INT);
        $mform->addRule('maxwish', get_string('required'), 'required', null, 'client');

        // Minimum d'inscriptions.
        $mform->addElement('text', 'minregister', get_string('minimum_enrolments', 'enrol_select'));
        $mform->setType('minregister', PARAM_INT);
        $mform->addRule('minregister', get_string('required'), 'required', null, 'client');

        // Maximum d'inscriptions.
        $mform->addElement('text', 'maxregister', get_string('maximum_enrolments', 'enrol_select'));
        $mform->setType('maxregister', PARAM_INT);
        $mform->addRule('maxregister', get_string('required'), 'required', null, 'client');

        // Submit buttons.
        $a = new stdClass();
        $a->href = $CFG->wwwroot . '/enrol/select/administration.php?tab=colleges';
        $a->class = 'btn btn-default btn-secondary';

        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $a));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        $mform->addElement('hidden', 'id', $data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'tab', 'colleges');
        $mform->setType('tab', PARAM_ALPHAEXT);

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $this->set_data($data);
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
        global $DB;

        $errors = parent::validation($data, $files);

        // Vérifie que le nombre de voeux n'est pas inférieur au nombre maximum d'inscription.
        if ($data['maxwish'] < $data['maxregister']) {
            $errors['maxwish'] = get_string('maximum_wishes_must_be_greater_than_or_equal_to_maximum_enrolments', 'enrol_select');
        }

        // Vérifie que le nombre maximum de voeux n'est pas inférieur au nombre minimum de voeux.
        if ($data['maxregister'] < $data['minregister']) {
            $label = get_string('maximum_enrolments_must_be_greater_than_or_equal_to_minimum_enrolments', 'enrol_select');
            $errors['maxregister'] = $label;
        }

        // Vérifie qu'une cohorte n'est pas utilisée dans un autre collège et avec un autre role.
        $conflicts = [];

        $roles = role_fix_names($DB->get_records('role'));

        $sql = "SELECT ac.id, ac.name, ac.roleid, acm.cohortid, c.name AS cohort" .
            " FROM {apsolu_colleges} ac" .
            " JOIN {apsolu_colleges_members} acm ON ac.id = acm.collegeid" .
            " JOIN {cohort} c ON c.id = acm.cohortid" .
            " JOIN {role} r ON r.id = ac.roleid" .
            " WHERE ac.id != :collegeid" .
            " AND ac.roleid = :roleid";
        $recordset = $DB->get_recordset_sql($sql, ['collegeid' => $data['id'], 'roleid' => $data['roleid']]);
        foreach ($recordset as $college) {
            if (in_array($college->cohortid, $data['cohorts'], $strict = true) === false) {
                continue;
            }

            $options = ['cohort' => $college->cohort, 'college' => $college->name, 'role' => $roles[$college->roleid]->name];
            $conflicts[] = get_string('cohort_X_is_already_used_with_role_Y_by_college_Z', 'enrol_select', $options);
        }
        $recordset->close();

        if (isset($conflicts[0]) === true) {
            $errors['cohorts'] = implode('<br />', $conflicts);
        }

        return $errors;
    }
}
