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
 * Teste la classe enrol_select_plugin
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Classe de tests pour enrol_select_plugin
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_plugin_testcase extends advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_refill_main_list() {
        global $DB, $USER;

        $generator = $this->getDataGenerator();

        // Désactive les notifications.
        set_config('enrol_select_select_notification_disable', 1, 'message');

        // Génère une instance enrol_select.
        $numberofusers = array(enrol_select_plugin::ACCEPTED => 5, enrol_select_plugin::MAIN => 5, enrol_select_plugin::WAIT => 0);
        list($plugin, $instance, $users) = $generator->get_plugin_generator('enrol_select')->create_enrol_instance($numberofusers);

        // Teste que la liste principale n'est pas réalimentée lorsque les inscriptions sont closes.
        $instance->enrolstartdate = strtotime('-2 week');
        $instance->enrolenddate = strtotime('-1 week');
        $DB->update_record('enrol', $instance);

        $plugin->refill_main_list($instance, $USER->id);
        foreach ($numberofusers as $status => $count) {
            $conditions = array('enrolid' => $instance->id, 'status' => $status);
            $this->assertSame($count, $DB->count_records('user_enrolments', $conditions));
        }

        // Ouvre les inscriptions.
        $instance->enrolenddate = strtotime('1 week');
        $DB->update_record('enrol', $instance);

        // Teste que la liste principale n'est pas réalimentée lorsqu'un enseignant fait une modification.
        $plugin->refill_main_list($instance, -1);
        foreach ($numberofusers as $status => $count) {
            $conditions = array('enrolid' => $instance->id, 'status' => $status);
            $this->assertSame($count, $DB->count_records('user_enrolments', $conditions));
        }

        // Teste que la liste principale n'est pas réalimentée lorsque les quotas ne sont pas activés.
        $plugin->refill_main_list($instance, $USER->id);
        foreach ($numberofusers as $status => $count) {
            $conditions = array('enrolid' => $instance->id, 'status' => $status);
            $this->assertSame($count, $DB->count_records('user_enrolments', $conditions));
        }

        // Active les quotas.
        $instance->customint3 = 1;
        $instance->customint1 = $numberofusers[enrol_select_plugin::ACCEPTED] + $numberofusers[enrol_select_plugin::MAIN]; // Places sur la liste principale.
        $DB->update_record('enrol', $instance);

        // Teste que la liste principale n'est pas réalimentée lorsque les inscriptions sont déjà complètes.
        $plugin->refill_main_list($instance, $USER->id);
        foreach ($numberofusers as $status => $count) {
            $conditions = array('enrolid' => $instance->id, 'status' => $status);
            $this->assertSame($count, $DB->count_records('user_enrolments', $conditions));
        }

        // Augmente le nombre de places sur liste principale, active la liste complémentaire.
        $instance->customint1 += 4;
        $instance->customint2 = 5;
        $DB->update_record('enrol', $instance);

        // Teste que la liste principale n'est pas réalimentée lorsque la liste d'attente est vide.
        $plugin->refill_main_list($instance, $USER->id);
        foreach ($numberofusers as $status => $count) {
            $conditions = array('enrolid' => $instance->id, 'status' => $status);
            $this->assertSame($count, $DB->count_records('user_enrolments', $conditions));
        }

        // Inscrit des utilisateurs sur liste complémentaire.
        for ($i = 0; $i < $instance->customint2; $i++) {
            $user = $generator->create_user();
            $plugin->enrol_user($instance, $user->id, $roleid = 5, $timestart = 0, $timeend = 0, enrol_select_plugin::WAIT);
        }

        // Teste que les utilisateurs passent de la liste complémentaire à la principale lorsqu'il y a des places disponibles.
        $numberofusers = array(enrol_select_plugin::ACCEPTED => 5, enrol_select_plugin::MAIN => 9, enrol_select_plugin::WAIT => 1);
        $plugin->refill_main_list($instance, $USER->id);
        foreach ($numberofusers as $status => $count) {
            $conditions = array('enrolid' => $instance->id, 'status' => $status);
            $this->assertSame($count, $DB->count_records('user_enrolments', $conditions));
        }
    }
}