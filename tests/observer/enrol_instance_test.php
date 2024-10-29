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
 * Classe de tests pour enrol_select\observer\enrol_instance
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class enrol_instance_test extends advanced_testcase {
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
     * @covers \enrol_select\observer\enrol_instance::deleted()
     *
     * @return void
     */
    public function test_deleted(): void {
        global $DB;

        $countrecords1 = $DB->count_records('enrol_select_cards');
        $countrecords2 = $DB->count_records('enrol_select_cohorts');
        $countrecords3 = $DB->count_records('enrol_select_roles');

        // Crée un cours.
        $course = $this->getDataGenerator()->create_course();

        // Ajoute une instance 'enrol_self' au cours.
        $selfplugin = enrol_get_plugin('self');
        $selfinstanceid = $selfplugin->add_instance($course, $selfplugin->get_instance_defaults());
        $selfinstance = $DB->get_record('enrol', ['id' => $selfinstanceid], '*', MUST_EXIST);

        // Ajoute une instance 'enrol_select' au cours.
        $selectplugin = enrol_get_plugin('select');
        $selectinstanceid = $selectplugin->add_instance($course, $selectplugin->get_instance_defaults());
        $selectinstance = $DB->get_record('enrol', ['id' => $selectinstanceid], '*', MUST_EXIST);

        // Crée des enregistrements dans les tables enrol_select.
        $sql = "INSERT INTO {enrol_select_cards} (enrolid, cardid) VALUES(:enrolid, :cardid)";
        $DB->execute($sql, ['enrolid' => $selectinstanceid, 'cardid' => 1]);

        $sql = "INSERT INTO {enrol_select_cohorts} (enrolid, cohortid) VALUES(:enrolid, :cohortid)";
        $DB->execute($sql, ['enrolid' => $selectinstanceid, 'cohortid' => 1]);

        $sql = "INSERT INTO {enrol_select_roles} (enrolid, roleid) VALUES(:enrolid, :roleid)";
        $DB->execute($sql, ['enrolid' => $selectinstanceid, 'roleid' => 1]);

        $this->assertSame($countrecords1 + 1, $DB->count_records('enrol_select_cards'));
        $this->assertSame($countrecords2 + 1, $DB->count_records('enrol_select_cohorts'));
        $this->assertSame($countrecords3 + 1, $DB->count_records('enrol_select_roles'));

        // Teste les appels à la classe enrol_select\observer\enrol_instance.
        $selfplugin->delete_instance($selfinstance);
        $this->assertSame($countrecords1 + 1, $DB->count_records('enrol_select_cards'));
        $this->assertSame($countrecords2 + 1, $DB->count_records('enrol_select_cohorts'));
        $this->assertSame($countrecords3 + 1, $DB->count_records('enrol_select_roles'));

        $selectplugin->delete_instance($selectinstance);
        $this->assertSame($countrecords1, $DB->count_records('enrol_select_cards'));
        $this->assertSame($countrecords2, $DB->count_records('enrol_select_cohorts'));
        $this->assertSame($countrecords3, $DB->count_records('enrol_select_roles'));
    }
}
