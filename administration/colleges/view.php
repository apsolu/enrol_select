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

use UniversiteRennes2\Apsolu as apsolu;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/enrol/select/locallib.php');

echo $OUTPUT->heading('Liste des populations');

$url = $CFG->wwwroot.'/enrol/select/administration.php?tab=colleges&action=edit&id=0';
echo '<p class="text-right"><a href="'.$url.'" class="btn btn-primary">Créer une nouvelle population</a></p>';

$colleges = $DB->get_records('apsolu_colleges');
$cohorts = $DB->get_records('cohort');

$roles = apsolu\get_custom_student_roles();

$sql = "SELECT c.*".
    " FROM {cohort} c".
    " WHERE c.id NOT IN (SELECT cohortid FROM {apsolu_colleges_members})".
    " ORDER BY c.name";
$unusedcohorts = array();
foreach ($DB->get_records_sql($sql) as $cohort) {
    $unusedcohorts[] = $cohort->name;
}

if ($unusedcohorts !== array()) {
    $unusedcohorts = '<li>'.implode('</li><li>', $unusedcohorts).'</li>';
    echo get_string('college_unused_cohorts', 'enrol_select', $unusedcohorts);
}

// TODO: utiliser un template mustache.
$editimage = $OUTPUT->image_url('t/edit');
$deleteimage = $OUTPUT->image_url('t/delete');

if ($colleges) {
    $table = new html_table();
    $table->attributes = array('class' => 'table table-striped');
    $table->head = array(
        'nom de la population',
        'roles',
        get_string('maximum_wishes', 'enrol_select'),
        get_string('minimum_enrolments', 'enrol_select'),
        get_string('maximum_enrolments', 'enrol_select'),
        'cohortes',
        'actions'
        );

    foreach ($colleges as $college) {
        // Members.
        $members = '';
        foreach ($DB->get_records('apsolu_colleges_members', array('collegeid' => $college->id), '', 'cohortid') as $member) {
            if (isset($cohorts[$member->cohortid]) === false) {
                // TODO: faire en sorte de retirer les cohortes qui n'existe plus.
                // Voir si il y a un event lors de la suppression des cohortes.
                continue;
            }

            $members .= '<li>'.$cohorts[$member->cohortid]->name.'</li>';
        }

        if ($members !== '') {
            $members = '<ul>'.$members.'</ul>';
        }

        // Actions.
        $editlink = $CFG->wwwroot.'/enrol/select/administration.php?tab=colleges&action=edit&id='.$college->id;
        $deletelink = $CFG->wwwroot.'/enrol/select/administration.php?tab=colleges&action=delete&id='.$college->id;
        $actions = '<ul><li style="display:inline;"><a href="'.$editlink.'"><img src="'.$editimage.'" /></a></li></ul>';

        $table->data[] = array(
            $college->name,
            $roles[$college->roleid]->name,
            $college->maxwish,
            $college->minregister,
            $college->maxregister,
            $members,
            $actions);
    }

    echo html_writer::table($table);
}
