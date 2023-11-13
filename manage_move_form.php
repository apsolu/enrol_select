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
 * Définition du formulaire pour gérer les déplacements d'inscription.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Définition du formulaire pour gérer les déplacements d'inscription.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_manage_move_form extends moodleform {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        list($instance, $users, $from, $to, $previousenrolid) = $this->_customdata;

        $strto = ($previousenrolid !== false) ? 'next_' . enrol_select_plugin::$states[$to] : enrol_select_plugin::$states[$to];
        $strfrom = enrol_select_plugin::$states[$from];

        $lists = new stdClass();
        $lists->to = get_string('list_'.$strto, 'enrol_select');
        $lists->from = get_string('list_'.$strfrom, 'enrol_select');
        $label = get_string('goto', 'enrol_select', $lists);

        $userslist = '<ul class="list list-unstyled">';
        foreach ($users as $user) {
            if (!empty($user->numberid)) {
                $numberid = ' ('.$user->numberid.')';
            } else {
                $numberid = '';
            }

            $userslist .= '<li>'.
                $user->firstname.' '.$user->lastname.$numberid.
                '</li>';

            $mform->addElement('hidden', 'users['.$user->id.']', $user->id);
            $mform->setType('users['.$user->id.']', PARAM_INT);
        }
        $userslist .= '</ul>';
        $mform->addElement('static', 'users', $label, $userslist);
        $mform->addElement('hidden', 'previousenrolid', $previousenrolid);
        $mform->setType('previousenrolid', PARAM_INT);

        $mform->addElement('selectyesno', 'notify', 'Envoyer une notification aux étudiants');
        $mform->setType('notify', PARAM_INT);

        $attributes = ['rows' => '15', 'cols' => '50'];
        $mform->addElement('textarea', 'message', get_string('send_message', 'enrol_select'), $attributes);
        $mform->setType('message', PARAM_TEXT);
        $mform->setDefault('message', get_string('message_'.$strfrom.'_to_'.$strto, 'enrol_select'));
        $mform->disabledIf('message', 'notify', 'eq', 0);

        $mform->addElement('hidden', 'actions', $to);
        $mform->setType('actions', PARAM_INT);

        // Submit buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save', 'admin'));

        $attributes = new stdClass();
        $attributes->href = $CFG->wwwroot.'/enrol/select/manage.php?enrolid='.$instance->id;
        $attributes->class = 'btn btn-default btn-secondary';
        $buttonarray[] = &$mform->createElement('static', '', '', get_string('cancel_link', 'local_apsolu', $attributes));

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
}
