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
 * Page d'affichage des collèges.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/enrol/select/locallib.php');

echo $OUTPUT->heading('Liste des populations');

$colleges = $DB->get_records('apsolu_colleges', $conditions = null, $sort = 'name');
$cohorts = $DB->get_records('cohort', $conditions = null, $sort = 'name');

$roles = enrol_select_get_custom_student_roles();

$sql = "SELECT c.*".
    " FROM {cohort} c".
    " WHERE c.id NOT IN (SELECT cohortid FROM {apsolu_colleges_members})".
    " ORDER BY c.name";
$unusedcohorts = [];
foreach ($DB->get_records_sql($sql) as $cohort) {
    $unusedcohorts[] = $cohort->name;
}

foreach ($colleges as $college) {
    // Members.
    $members = [];
    foreach ($DB->get_records('apsolu_colleges_members', ['collegeid' => $college->id], '', 'cohortid') as $member) {
        if (isset($cohorts[$member->cohortid]) === false) {
            // TODO: faire en sorte de retirer les cohortes qui n'existe plus.
            // Voir si il y a un event lors de la suppression des cohortes.
            continue;
        }

        $members[] = '<li>'.$cohorts[$member->cohortid]->name.'</li>';
    }

    if ($members !== []) {
        sort($members);
        $college->members = '<ul>'.implode('', $members).'</ul>';
    } else {
        $college->members = '';
    }

    $college->rolename = $roles[$college->roleid]->name;
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->colleges = array_values($colleges);
$data->count_colleges = count($data->colleges);

$data->unusedcohorts = '';
if ($unusedcohorts !== []) {
    $unusedcohorts = '<li>'.implode('</li><li>', $unusedcohorts).'</li>';
    $data->unusedcohorts = get_string('college_unused_cohorts', 'enrol_select', $unusedcohorts);
}

echo $OUTPUT->render_from_template('enrol_select/administration_colleges', $data);
