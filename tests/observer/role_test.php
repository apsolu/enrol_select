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

namespace enrol_select\observer;

use advanced_testcase;

/**
 * Classe de tests pour enrol_select\observer\role
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class role_test extends advanced_testcase {
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
     * @covers \enrol_select\observer\role::deleted()
     *
     * @return void
     */
    public function test_deleted(): void {
        global $DB;

        $countrecords1 = $DB->count_records('enrol_select_roles');
        $countrecords2 = $DB->count_records('enrol_select_cohorts_roles');

        // Crée un rôle.
        $roleid = create_role('role1', 'shortname', 'description', $archetype = '');

        // Crée un enregistrement inscription/role.
        $sql = "INSERT INTO {enrol_select_roles} (enrolid, roleid) VALUES(:enrolid, :roleid)";
        $DB->execute($sql, ['enrolid' => 1, 'roleid' => $roleid]);

        $sql = "INSERT INTO {enrol_select_cohorts_roles} (roleid, cohortid) VALUES(:roleid, :cohortid)";
        $DB->execute($sql, ['roleid' => $roleid, 'cohortid' => 1]);

        $this->assertSame($countrecords1 + 1, $DB->count_records('enrol_select_roles'));
        $this->assertSame($countrecords2 + 1, $DB->count_records('enrol_select_cohorts_roles'));

        // Teste le bon appel à la classe enrol_select\observer\role.
        delete_role($roleid);
        $this->assertSame($countrecords1, $DB->count_records('enrol_select_roles'));
        $this->assertSame($countrecords2, $DB->count_records('enrol_select_cohorts_roles'));
    }
}
