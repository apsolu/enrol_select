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
 * Page de configuration des collèges.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/enrol/select/locallib.php');

$id = required_param('id', PARAM_INT);

require(__DIR__.'/edit_form.php');

$instance = $DB->get_record('apsolu_colleges', array('id' => $id));
$cohorts = $DB->get_records('cohort');

$roles = apsolu\get_custom_student_roles();

if (!$instance) {
    $instance = new stdClass();
    $instance->id = 0;
    $instance->name = '';
    $instance->roleid = '';
    $instance->cohorts = array();
} else {
    $instance->cohorts = array_keys($DB->get_records('apsolu_colleges_members', array('collegeid' => $id), '', 'cohortid'));
}

$mform = new apsolu_colleges_form(null, array($instance, $roles, $cohorts));

if ($data = $mform->get_data()) {
    if ($data->id == 0) {
        $data->id = $DB->insert_record('apsolu_colleges', $data);
    } else {
        $DB->update_record('apsolu_colleges', $data);
    }

    $DB->delete_records('apsolu_colleges_members', array('collegeid' => $data->id));

    if (isset($data->cohorts)) {
        foreach ($data->cohorts as $cohortid) {
            $sql = "INSERT INTO {apsolu_colleges_members}(collegeid, cohortid) VALUES(?, ?)";
            $DB->execute($sql, array($data->id, $cohortid));
        }
    }

    require(__DIR__.'/view.php');
} else {
    $mform->display();
}
