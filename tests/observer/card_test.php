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
 * Classe de tests pour enrol_select\observer\card
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_select\observer\card
 */
final class card_test extends \advanced_testcase {
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

        $cardid = 1;

        $countrecords = $DB->count_records('enrol_select_cards');

        // Insère un enregistrement.
        $sql = "INSERT INTO {enrol_select_cards} (enrolid, cardid) VALUES(1, :cardid)";
        $DB->execute($sql, ['cardid' => $cardid]);

        $this->assertSame($countrecords + 1, $DB->count_records('enrol_select_cards'));

        // Teste le bon appel à la classe enrol_select\observer\card.
        $event = \local_apsolu\event\card_deleted::create([
            'objectid' => $cardid,
            'context' => context_system::instance(),
        ]);
        $event->trigger();

        $this->assertSame($countrecords, $DB->count_records('enrol_select_cards'));
    }
}
