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
 * Page affichant la liste des cours disponibles pour le renouvèlement par voeux.
 *
 * @package    enrol_select
 * @copyright  2019 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/enrol/select/lib.php');

echo $OUTPUT->heading('Réinscriptions en masse');

$sql = "SELECT e.id, c.fullname AS coursename, e.name AS enrolname, e2.name AS renewalname
          FROM mdl_course c
          JOIN mdl_enrol e ON e.courseid = c.id
          JOIN mdl_enrol e2 ON e.customint6 = e2.id
         WHERE e.enrol = 'select'
      ORDER BY coursename";
$recordset = $DB->get_recordset_sql($sql);

$options = [];
foreach (enrol_select_plugin::$states as $code => $state) {
    $options[$code] = get_string($state . '_list', 'enrol_select');
}

if ($recordset->valid()) {
    $table = new html_table();
    $table->attributes = ['class' => 'table table-striped'];
    $table->head = [
        '',
        'Nom de l\'activité',
        'Méthode d\'inscription actuelle',
        'Méthode de réinscription',
        'Liste de réinscription',
        ];

    foreach ($recordset as $renewal) {
        // Actions.
        $uid = uniqid();
        $submitlink = $CFG->wwwroot . '/enrol/select/administration.php?tab=renewals&action=submit';
        $actions = '<ul><li style="display:inline;">' .
            '<a href="' . $submitlink . '">Valider les réinscriptions en masse</a></li></ul>';
        $selectoptions = html_writer::select($options, $uid . '_action', '0', ['' => 'choosedots']);

        $table->data[] = [
            '<input type="hidden" name="uids[]" value="' . $uid . '" />' .
            '<input type="checkbox" name="' . $uid . '_enrol" value="' . $renewal->id . '" />',
            $renewal->coursename,
            $renewal->enrolname,
            $renewal->renewalname,
            $selectoptions,
        ];
    }

    $information = get_string('only_students_on_the_accepted_list_will_be_transferred_to_the_list_of_your_choice', 'enrol_select');
    echo '<form method="post" action="' . $submitlink . '">';
    echo html_writer::div($information, 'alert alert-info');
    echo html_writer::table($table);
    echo '<input class="btn btn-primary" type="submit" value="Valider">';
    echo '</form>';
} else {
    echo html_writer::div(get_string('no_select_enrolment_method_uses_reenrolment_setting', 'enrol_select'), 'alert alert-warning');
}

$recordset->close();
