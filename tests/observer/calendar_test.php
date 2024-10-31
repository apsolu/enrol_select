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

use context_system;
use stdClass;

/**
 * Classe de tests pour enrol_select\observer\calendar
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_select\observer\calendar
 */
final class calendar_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->setAdminUser();

        $this->resetAfterTest();
    }

    /**
     * Teste deleted().
     *
     * @covers ::deleted()
     */
    public function test_deleted(): void {
        global $DB;

        $calendarid = 1;

        $DB->delete_records('enrol', ['customchar1' => $calendarid]);
        $countrecords = 0;

        // Insère un enregistrement.
        list($plugin, $instance, $users) = $this->getDataGenerator()->get_plugin_generator('enrol_select')->create_enrol_instance();

        $instance->customchar1 = $calendarid;
        $DB->update_record('enrol', $instance);

        $this->assertSame($countrecords + 1, $DB->count_records('enrol', ['customchar1' => $calendarid]));

        // Teste le bon appel à la classe enrol_select\observer\calendar.
        $event = \local_apsolu\event\calendar_deleted::create([
            'objectid' => $calendarid,
            'context' => context_system::instance(),
        ]);
        $event->trigger();

        $this->assertSame($countrecords, $DB->count_records('enrol', ['customchar1' => $calendarid]));
    }
}
