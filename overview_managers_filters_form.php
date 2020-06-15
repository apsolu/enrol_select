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
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/cohort/lib.php');

class overview_managers_filters_form extends moodleform {
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $datetimeoptions = array('optional' => true);

        // Date du jour.
        $mform->addElement('date_time_selector', 'now', get_string('date'));
        $mform->addRule('now', get_string('required'), 'required', null, 'client');

        // Cohortes.
        $cohorts = array();
        foreach ($DB->get_records('cohort', $params = array(), $sort = 'name') as $cohort) {
            $cohorts[$cohort->id] = $cohort->name;
        }
        $attributes = array('size' => 10);
        $select = $mform->addElement('select', 'cohorts', get_string('selectcohorts', 'enrol_select'), $cohorts, $attributes);
        $select->setMultiple(true);
        $mform->addRule('cohorts', get_string('required'), 'required', null, 'client');

        // Validation.
        $mform->addElement('submit', 'submitbutton', get_string('show'));
    }
}
