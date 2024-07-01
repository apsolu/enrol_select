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
 * Teste la classe enrol_select\observer\cohort
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_select\observer;

use advanced_testcase;
use stdclass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * Classe de tests pour enrol_select\observer\cohort
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cohort_test extends advanced_testcase {
    /**
     * Initialise un environnement de test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest();
    }

    /**
     * Teste la méthode deleted().
     *
     * @covers \enrol_select\observer\cohort::deleted()
     *
     * @return void
     */
    public function test_deleted(): void {
        global $DB;

        $countrecords1 = $DB->count_records('apsolu_colleges_members');
        $countrecords2 = $DB->count_records('enrol_select_cohorts');
        $countrecords3 = $DB->count_records('enrol_select_cohorts_roles');

        // Crée une cohorte.
        $cohort = new stdClass();
        $cohort->name = 'test';
        $cohort->contextid = 1;
        $cohort->id = cohort_add_cohort($cohort);

        // Crée un enregistrement carte/cohorte.
        $sql = "INSERT INTO {apsolu_colleges_members} (collegeid, cohortid) VALUES(:collegeid, :cohortid)";
        $DB->execute($sql, ['collegeid' => 1, 'cohortid' => $cohort->id]);

        $sql = "INSERT INTO {enrol_select_cohorts} (enrolid, cohortid) VALUES(:enrolid, :cohortid)";
        $DB->execute($sql, ['enrolid' => 1, 'cohortid' => $cohort->id]);

        $sql = "INSERT INTO {enrol_select_cohorts_roles} (roleid, cohortid) VALUES(:roleid, :cohortid)";
        $DB->execute($sql, ['roleid' => 1, 'cohortid' => $cohort->id]);

        $this->assertSame($countrecords1 + 1, $DB->count_records('apsolu_colleges_members'));
        $this->assertSame($countrecords2 + 1, $DB->count_records('enrol_select_cohorts'));
        $this->assertSame($countrecords3 + 1, $DB->count_records('enrol_select_cohorts_roles'));

        // Teste le bon appel à la classe enrol_select\observer\cohort.
        cohort_delete_cohort($cohort);
        $this->assertSame($countrecords1, $DB->count_records('apsolu_colleges_members'));
        $this->assertSame($countrecords2, $DB->count_records('enrol_select_cohorts'));
        $this->assertSame($countrecords3, $DB->count_records('enrol_select_cohorts_roles'));
    }
}
