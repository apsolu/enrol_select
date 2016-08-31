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

use UniversiteRennes2\Apsolu as apsolu;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');
require_once($CFG->libdir . '/csvlib.class.php');

$enrolid = required_param('enrolid', PARAM_INT);

$instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'select'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
$canenrol = has_capability('enrol/select:enrol', $context);
$canunenrol = has_capability('enrol/select:unenrol', $context);

// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.

if (!$canenrol and !$canunenrol) {
    // No need to invent new error strings here...
    require_capability('enrol/select:enrol', $context);
    require_capability('enrol/select:unenrol', $context);
}

if (!$enrolselect = enrol_get_plugin('select')) {
    throw new coding_exception('Can not instantiate enrol_select');
}

$roles = role_fix_names($DB->get_records('role'));

$sql = 'SELECT DISTINCT u.*, ra.roleid, ue.timecreated, ue.status'.
    ' FROM {user} u'.
    ' JOIN {user_enrolments} ue ON u.id = ue.userid'.
    ' JOIN {role_assignments} ra ON u.id = ra.userid'.
    ' JOIN {role} r ON r.id = ra.roleid AND r.archetype = "student"'.
    ' JOIN {context} ctx ON ctx.id = ra.contextid'.
    ' WHERE ue.enrolid = :enrolid'.
    ' AND ctx.instanceid = :courseid'.
    ' AND ctx.contextlevel = 50'.
    ' ORDER BY ue.status, u.lastname, u.firstname, u.institution, u.department';
$users = $DB->get_records_sql($sql, array('enrolid' => $enrolid, 'courseid' => $course->id));

// Génération du fichier csv.
$filename = str_replace(' ', '_', strtolower($course->fullname));

$headers = array(
    get_string('firstname'),
    get_string('lastname'),
    get_string('age', 'enrol_select'),
    get_string('birthday', 'enrol_select'),
    get_string('sex', 'enrol_select'),
    get_string('register_type', 'enrol_select'),
    get_string('institution'),
    get_string('department'),
    get_string('paid', 'enrol_select'),
    get_string('list', 'enrol_select'),
);

$csvexport = new \csv_export_writer();
$csvexport->set_filename($filename);
$csvexport->add_data($headers);

foreach ($users as $user) {
    $sex = '';
    $birthday = '';
    $optionpaid = '';
    $bonificationpaid = '';
    $librepaid = '';

    $fields = $DB->get_records('user_info_field');
    $userfields = $DB->get_records('user_info_data', array('userid' => $user->id), $sort = '', $columns = 'fieldid, data');
    foreach ($fields as $fieldid => $field) {
        switch($field->shortname) {
            case 'sex':
            case 'birthday':
            case 'option_paid':
            case 'bonification_paid':
            case 'libre_paid':
                if (isset($userfields[$fieldid])) {
                    ${$field->shortname} = $userfields[$fieldid]->data;
                }
                break;
        }
    }

    try {
        $birthdayday = substr($birthday, 0, 2);
        $birthdaymonth = substr($birthday, 3, 2);
        $birthdayyear = substr($birthday, 6, 4);
        $from = new DateTime($birthdayyear.'-'.$birthdaymonth.'-'.$birthdayday);
        $to   = new DateTime('today');
        $age = $from->diff($to)->y;
    } catch (Exception $exception) {
        $age = '';
    }

    $row = array();
    $row[] = $user->firstname;
    $row[] = $user->lastname;
    $row[] = $age;
    $row[] = $birthday;
    $row[] = $sex;
    $row[] = $roles[$user->roleid]->localname;
    $row[] = $user->institution;
    $row[] = $user->department;
    $paid = $roles[$user->roleid]->shortname.'paid';
    $row[] = (${$paid} === '1') ? get_string('yes') : get_string('no');
    $state = enrol_select_plugin::$states[$user->status];
    $row[] = get_string($state.'_list', 'enrol_select');

    $csvexport->add_data($row);
}

$csvexport->download_file();

exit(0);
