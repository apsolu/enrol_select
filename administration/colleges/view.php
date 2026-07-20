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
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_select\administration\college;

require_once($CFG->dirroot . '/enrol/select/lib.php');

echo $OUTPUT->heading('Liste des populations');

$colleges = college::get_records($conditions = null, $sort = 'name');
$cohorts = $DB->get_records('cohort', $conditions = null, $sort = 'name');

$roles = enrol_select_get_custom_student_roles();

$sql = "SELECT c.*
          FROM {cohort} c
         WHERE c.id NOT IN (SELECT cohortid FROM {apsolu_colleges_members})
      ORDER BY c.name";
$unusedcohorts = [];
foreach ($DB->get_records_sql($sql) as $cohort) {
    $unusedcohorts[] = ['name' => $cohort->name];
}

// Tâches planifiées (adhoc) pour la gestion des quotas individuels par population (nombre de voeux, d'inscriptions max./min).
// Le résultat est groupé par date d'exécution, la plus proche en première position.
$tasks = college::get_college_wish_rules();

// Date du prochain changement pour chaque population (apparait dans le tableau).
$collegevalidities = [];

$collegerules = [];
$countrules = 0;
foreach ($tasks as $date => $rules) {
    foreach ($colleges as $id => $college) {
        if (in_array($id, array_keys($rules)) && !in_array($id, array_keys($collegevalidities))) {
            $collegevalidities[$id] = $date;
        }
    }

    $daterule = new stdClass();
    $daterule->date = userdate($date, get_string('strftimedatetime', 'local_apsolu'));
    $collegelist = []; // Liste des populations qui ont une règle programmée ce jour là.

    foreach ($rules as $id => $rule) {
        if (isset($colleges[$id])) { // On vérifie que la population décrite dans la règle existe.
            // Description de la règle.
            $rule->population = $colleges[$id]->name;
            $rule->plural1 = $rule->maxwish > 1 ? 'x' : '';
            $rule->plural2 = $rule->maxregister > 1 ? 's' : '';
            $rule->changes = get_string('planned_college_rule', 'enrol_select', $rule);
            $collegelist[] = $rule;
        }
    }

    $daterule->collegelist = $collegelist;
    $daterule->isfirst = $countrules == 0;

    $collegerules[] = $daterule;

    $countrules++;
}

foreach ($colleges as $college) {
    // Members (cohorts).
    $members = [];

    foreach ($college->get_members() as $member) {
        if (isset($cohorts[$member->cohortid]) === false) {
            continue;
        }

        $members[] = '<li>' . $cohorts[$member->cohortid]->name . '</li>';
    }

    if ($members !== []) {
        sort($members);
        $college->members = '<ul>' . implode('', $members) . '</ul>';
    } else {
        $college->members = '';
    }

    $college->rolename = $roles[$college->roleid]->name;

    // Dans le tableau des populations on affiche uniquement la date du prochain changement, s'il y en a minimum un.
    $college->hasrule = 0;
    $college->daterule = get_string('no_planned_changes', 'enrol_select');
    if (isset($collegevalidities[$college->id])) {
        $college->hasrule = 1;
        // Message affiché au survol de la date dans le tableau.
        $college->daterule = get_string(
            'has_rule_info',
            'enrol_select',
            userdate($collegevalidities[$college->id], get_string('strftimedatetime', 'local_apsolu'))
        );
        $college->dateruledate = userdate($collegevalidities[$college->id], get_string('strftimeabbrday', 'local_apsolu'));
    }
}

$data = new stdClass();
$data->wwwroot = $CFG->wwwroot;
$data->colleges = array_values($colleges);
$data->count_colleges = count($data->colleges);

$data->count_unused_cohorts = count($unusedcohorts);
$data->unused_cohorts = $unusedcohorts;

$data->count_rules = $countrules;
$data->college_rules = $collegerules;
$data->next_rules = $nextrules;

$data->rulecolor = get_config('theme_apsolu', 'custom_brandcolor_links');

$context = context_system::instance();

$options = [];
$options['contextid'] = $context->id;

$PAGE->requires->js_call_amd('enrol_select/administration_colleges', 'initialise', [$options]);

echo $OUTPUT->render_from_template('enrol_select/administration_colleges', $data);
