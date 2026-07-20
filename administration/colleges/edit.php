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
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/enrol/select/locallib.php');

$id = required_param('id', PARAM_INT);

require(__DIR__ . '/edit_form.php');

$instance = $DB->get_record('apsolu_colleges', ['id' => $id]);
$cohorts = $DB->get_records('cohort', $conditions = null, $sort = 'name');

$roles = enrol_select_get_custom_student_roles();

if (!$instance) {
    $instance = new stdClass();
    $instance->id = 0;
    $instance->name = '';
    $instance->roleid = '';
    $instance->cohorts = [];
    $notificationstr = 'population_created';
} else {
    $instance->cohorts = array_keys($DB->get_records('apsolu_colleges_members', ['collegeid' => $id], '', 'cohortid'));
     $notificationstr = 'population_updated';
}

$mform = new apsolu_colleges_form($PAGE->url->out(false), [$instance, $roles, $cohorts]);

if ($data = $mform->get_data()) {
    if ($data->id == 0) {
        $data->id = $DB->insert_record('apsolu_colleges', $data);
    } else {
        $DB->update_record('apsolu_colleges', $data);
    }

    // Met à jour la liste des cohortes.
    $newcohorts = $data->cohorts;

    // Population qui possède déjà des cohortes : supprimer les cohortes désélectionnées et insérer les nouvelles cohortes choisies.
    if (empty($instance->cohorts) == false) {
        $removecohorts = array_diff($instance->cohorts, $newcohorts);
        $DB->delete_records_list('apsolu_colleges_members', 'cohortid', $removecohorts);

        $newcohorts = array_diff($newcohorts, $instance->cohorts);
    }

    foreach ($newcohorts as $cohortid) {
        $sql = "INSERT INTO {apsolu_colleges_members}(collegeid, cohortid) VALUES(?, ?)";
        $DB->execute($sql, [$data->id, $cohortid]);
    }

    // Rediriger permet de clore la requête POST et éviter des resoumissions notamment
    // avec le formulaire d'ajout d'une règle programmée ou en cas d'actualisation après la soumission.
    redirect(
        $PAGE->url,
        get_string($notificationstr, 'enrol_select', $data->name),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} else {
    $mform->display();
}
