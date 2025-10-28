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

if (isset($_POST['enrols']) === true) {
    // Traite les réponses envoyées via le formulaire.
    require($CFG->dirroot . '/enrol/select/administration/renewals/submit.php');
}

$sql = "SELECT e.id, c.fullname AS coursename, e.name AS enrolname, e2.id AS renewalid, e2.name AS renewalname
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
    $mastercheckbox = new \core\output\checkbox_toggleall('enrolments-togglegroup', $ismaster = true, [
        'id' => 'select-all-enrolments',
        'name' => 'select-all-enrolments',
        'label' => get_string('selectall'),
        'labelclasses' => 'visually-hidden',
        'classes' => 'm-1',
        'checked' => false,
    ]);

    $table = new html_table();
    $table->attributes = ['class' => 'table table-striped'];
    $table->head = [
        $OUTPUT->render($mastercheckbox),
        'Nom de l\'activité',
        'Méthode d\'inscription actuelle',
        'Méthode de réinscription',
        ];

    foreach ($recordset as $renewal) {
        // Actions.
        $submitlink = new moodle_url('/enrol/select/administration.php', ['tab' => 'renewals', 'action' => 'select']);

        $label = sprintf('%s : copier %s vers %s', $renewal->coursename, $renewal->enrolname, $renewal->renewalname);
        $checkbox = new \core\output\checkbox_toggleall('enrolments-togglegroup', $ismaster = false, [
            'classes' => 'usercheckbox m-1',
            'id' => 'enrols-' . $renewal->id,
            'name' => 'enrols[]',
            'value' => $renewal->id,
            'checked' => false,
            'label' => get_string('selectitem', 'moodle', $label),
            'labelclasses' => 'accesshide',
        ]);

        $enrolurl = new moodle_url('/enrol/select/manage.php', ['enrolid' => $renewal->id]);
        $renewalurl = new moodle_url('/enrol/select/manage.php', ['enrolid' => $renewal->renewalid]);
        $table->data[] = [
            $OUTPUT->render($checkbox),
            $renewal->coursename,
            html_writer::link($enrolurl, $renewal->enrolname),
            html_writer::link($renewalurl, $renewal->renewalname),
        ];
    }

    echo '<form method="post" action="' . $submitlink . '">';
    echo html_writer::start_div('alert alert-info');
    foreach ($options as $code => $label) {
        if (isset($_POST['targetlist'][$code]) === true) {
            $selected = $_POST['targetlist'][$code];
        } else {
            $selected = -1;
            if ($code == enrol_select_plugin::ACCEPTED) {
                $selected = $code;
            }
        }

        $selectname = sprintf('targetlist[%s]', $code);
        $selectoptions = html_writer::select($options, $selectname, $selected, ['-1' => get_string('none')]);
        $selectcontainer = html_writer::div(
            $selectoptions,
            'col-md-8 d-flex flex-wrap align-items-start felement',
            ['data-fieldtype' => 'select']
        );
        $string = get_string('copy_users_on_X_to', 'enrol_select', $label);
        $labeltag = html_writer::label($string, $selectname, $colonize = true, ['class' => 'd-inline word-break']);
        $labelcontainer = html_writer::div($labeltag, 'col-md-4 col-form-label d-flex pb-0 pe-md-0');
        echo html_writer::div($labelcontainer . $selectcontainer, 'mb-3 row fitem border');
    }
    echo html_writer::end_div();
    echo html_writer::table($table);
    echo '<input class="btn btn-primary" type="submit" value="Valider">';
    echo '</form>';
} else {
    echo html_writer::div(get_string('no_select_enrolment_method_uses_reenrolment_setting', 'enrol_select'), 'alert alert-warning');
}

$recordset->close();
