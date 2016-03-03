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
 * post installation hook for adding data.
 *
 * @package    mod_attendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Post installation procedure
 */
function xmldb_enrol_select_install() {
    global $DB;

    $result = 1;
    /*
    // types
    $type = new stdClass();
    $type->name = '';
    $type->description = '';
    $type->sortorder = 0;
    foreach(array('Évalué (cursus)', 'Évalué (bonification)', 'Non évalué (libre)') as $sort => $name){
    $type->name = $name;
    $type->sortorder = ($sort+1);

    $result &= ($DB->insert_record('enrol_select_types', $type) !== false);
    }

    // groupings
    $groupings = array();
    $groupings[] = (object) ['name' => 'Évaluation en cursus (homme)', 'typeid' => '1'];
    $groupings[] = (object) ['name' => 'Évaluation en cursus (femme)', 'typeid' => '1'];
    $groupings[] = (object) ['name' => 'Évaluation en cursus (mixte)', 'typeid' => '1'];
    $groupings[] = (object) ['name' => 'Évaluation en bonification (homme)', 'typeid' => '2'];
    $groupings[] = (object) ['name' => 'Évaluation en bonification (femme)', 'typeid' => '2'];
    $groupings[] = (object) ['name' => 'Évaluation en bonification (mixte)', 'typeid' => '2'];
    $groupings[] = (object) ['name' => 'Cours libre (homme)', 'typeid' => '3'];
    $groupings[] = (object) ['name' => 'Cours libre (femme)', 'typeid' => '3'];
    $groupings[] = (object) ['name' => 'Cours libre (mixte)', 'typeid' => '3'];
    foreach($groupings as $grouping){
    $result &= ($DB->insert_record('enrol_select_groupings', $grouping) !== false);
    }

    // grp cohorts
    $grp_cohorts = array();
    $grp_cohorts[] = array(1,1); // cursus
    $grp_cohorts[] = array(1,2);
    $grp_cohorts[] = array(2,3);
    $grp_cohorts[] = array(2,4);
    $grp_cohorts[] = array(3,1);
    $grp_cohorts[] = array(3,2);
    $grp_cohorts[] = array(3,3);
    $grp_cohorts[] = array(3,4);
    $grp_cohorts[] = array(4,5); // bonif
    $grp_cohorts[] = array(4,6);
    $grp_cohorts[] = array(5,7);
    $grp_cohorts[] = array(5,8);
    $grp_cohorts[] = array(6,5);
    $grp_cohorts[] = array(6,6);
    $grp_cohorts[] = array(6,7);
    $grp_cohorts[] = array(6,8);
    $grp_cohorts[] = array(7,9); // libre
    $grp_cohorts[] = array(7,10);
    $grp_cohorts[] = array(7,11);
    $grp_cohorts[] = array(7,12);
    $grp_cohorts[] = array(8,13);
    $grp_cohorts[] = array(8,14);
    $grp_cohorts[] = array(8,15);
    $grp_cohorts[] = array(8,16);
    $grp_cohorts[] = array(9,9);
    $grp_cohorts[] = array(9,10);
    $grp_cohorts[] = array(9,11);
    $grp_cohorts[] = array(9,12);
    $grp_cohorts[] = array(9,13);
    $grp_cohorts[] = array(9,14);
    $grp_cohorts[] = array(9,15);
    $grp_cohorts[] = array(9,16);
    foreach($grp_cohorts as $grouping){
    $result &= ($DB->execute('INSERT INTO {enrol_select_grp_cohorts}(groupingid, cohortid) VALUES(?, ?)', $grouping) !== false);
    }
    */
    return ($result === 1);
}
