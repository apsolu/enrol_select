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
 * Select enrolment tests.
 *
 * @package    enrol_select
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Select enrolment tests.
 *
 * @package    enrol_select
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_lib_testcase extends advanced_testcase {
    public function test_get_enrolment_list_name() {
        global $CFG;

        require_once($CFG->dirroot.'/enrol/select/lib.php');

        $this->assertEquals(get_string('accepted_list', 'enrol_select'), enrol_select_plugin::get_enrolment_list_name(enrol_select_plugin::ACCEPTED));
        $this->assertEquals(get_string('main_list', 'enrol_select'), enrol_select_plugin::get_enrolment_list_name(enrol_select_plugin::MAIN));
        $this->assertEquals(get_string('wait_list', 'enrol_select'), enrol_select_plugin::get_enrolment_list_name(enrol_select_plugin::WAIT));
        $this->assertEquals(get_string('deleted_list', 'enrol_select'), enrol_select_plugin::get_enrolment_list_name(enrol_select_plugin::DELETED));
    }
}
