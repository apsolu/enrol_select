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
 * Définition du formulaire pour gérer les changements de cours.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Définition du formulaire pour gérer les changements de cours.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_manage_change_course_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        [$instance, $users, $from, $to, $courses] = $this->_customdata;

        $label = get_string('users');

        $userslist = '<ul class="list list-unstyled">';
        foreach ($users as $user) {
            if (!empty($user->numberid)) {
                $numberid = ' (' . $user->numberid . ')';
            } else {
                $numberid = '';
            }

            $userslist .= '<li>' .
                $user->firstname . ' ' . $user->lastname . $numberid .
                '</li>';

            $mform->addElement('hidden', 'users[' . $user->id . ']', $user->id);
            $mform->setType('users[' . $user->id . ']', PARAM_INT);
        }
        $userslist .= '</ul>';
        $mform->addElement('static', 'users', $label, $userslist);

        $mform->addElement('select', 'courseid', get_string('courses'), $courses);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'actions', $to);
        $mform->setType('actions', PARAM_INT);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot . '/enrol/select/manage.php?enrolid=' . $instance->id;
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
}
