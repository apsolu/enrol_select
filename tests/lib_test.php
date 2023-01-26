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

namespace enrol\select;

use advanced_testcase;
use context_course;
use enrol_select_plugin;
use local_apsolu\core\course;
use stdClass;

global $CFG;

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');

/**
 * Classe de tests pour enrol_select_plugin
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends advanced_testcase {
    protected function setUp() : void {
        parent::setUp();

        $this->resetAfterTest();
    }

    public function test_enrol_user() {
        global $CFG, $DB, $USER;

        $backupuserid = $USER->id;

        $course = new course();
        $data = advanced_testcase::getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);

        $plugin = enrol_get_plugin('select');
        $instanceid = $plugin->add_instance($course, $plugin->get_instance_defaults());

        $instance = $DB->get_record('enrol', array('id' => $instanceid));

        $roleid = '5';
        $timestart = 0;
        $timeend = 0;
        $status = enrol_select_plugin::ACCEPTED;

        // Teste une première inscription.
        $user = advanced_testcase::getDataGenerator()->create_user();

        $plugin->enrol_user($instance, $user->id, $roleid, $timestart, $timeend, $status);
        $userenrolment = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $user->id));
        $this->assertSame($instance->customint7, $userenrolment->timestart);
        $this->assertSame($instance->customint8, $userenrolment->timeend);

        // Teste un changement de status et de rôle.
        $status = enrol_select_plugin::MAIN;

        $plugin->enrol_user($instance, $user->id, $roleid, $timestart, $timeend, $status);
        $userenrolment = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $user->id));
        $this->assertSame($status, $userenrolment->status);

        // Teste un changement de rôle.
        $roleid = '3';

        $plugin->enrol_user($instance, $user->id, $roleid, $timestart, $timeend, $status);
        $context = context_course::instance($instance->courseid);
        $params = array(
            'component' => 'enrol_select',
            'userid' => $user->id,
            'contextid' => $context->id,
            'itemid' => $instance->id
        );
        $roleassignment = $DB->get_record('role_assignments', $params);
        $this->assertSame($roleid, $roleassignment->roleid);

        // Teste une première inscription avec des dates de début et de fin personnalisées.
        $user = advanced_testcase::getDataGenerator()->create_user();

        $timestart = '10';
        $timeend = '20';
        $plugin->enrol_user($instance, $user->id, $roleid, $timestart, $timeend, $status);

        $userenrolment = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $user->id));
        $this->assertSame($timestart, $userenrolment->timestart);
        $this->assertSame($timeend, $userenrolment->timeend);

        // Désactive la messagerie pour avoir un message de debug.
        $CFG->messaging = 0;

        // Teste l'envoi des notifications pour la liste des acceptés.
        $instance->customtext1 = 'accepted';
        $instance->customtext2 = '';
        $instance->customtext3 = '';
        $DB->update_record('enrol', $instance);

        $user = advanced_testcase::getDataGenerator()->create_user();
        $USER->id = $user->id;
        $status = enrol_select_plugin::ACCEPTED;
        $this->assertDebuggingCalled($plugin->enrol_user($instance, $user->id, $roleid, $timestart, $timeend, $status));

        // Teste l'envoi des notifications pour la liste principale.
        $instance->customtext1 = '';
        $instance->customtext2 = 'main';
        $instance->customtext3 = '';
        $DB->update_record('enrol', $instance);

        $user = advanced_testcase::getDataGenerator()->create_user();
        $USER->id = $user->id;
        $status = enrol_select_plugin::MAIN;
        $this->assertDebuggingCalled($plugin->enrol_user($instance, $user->id, $roleid, $timestart, $timeend, $status));

        // Teste l'envoi des notifications pour la liste secondaire.
        $instance->customtext1 = '';
        $instance->customtext2 = '';
        $instance->customtext3 = 'wait';
        $DB->update_record('enrol', $instance);

        $user = advanced_testcase::getDataGenerator()->create_user();
        $USER->id = $user->id;
        $status = enrol_select_plugin::WAIT;
        $this->assertDebuggingCalled($plugin->enrol_user($instance, $user->id, $roleid, $timestart, $timeend, $status));

        // Teste l'absence de notification.
        $instance->customtext1 = '';
        $instance->customtext2 = '';
        $instance->customtext3 = '';
        $DB->update_record('enrol', $instance);

        $user = advanced_testcase::getDataGenerator()->create_user();
        $USER->id = $user->id;
        $status = enrol_select_plugin::WAIT;
        $this->assertDebuggingNotCalled($plugin->enrol_user($instance, $user->id, $roleid, $timestart, $timeend, $status));

        // Restaure l'identifiant de l'utilisateur courant.
        $USER->id = $backupuserid;
    }

    public function test_get_available_status() {
        global $DB;

        $generator = $this->getDataGenerator();

        // Désactive les notifications.
        set_config('enrol_select_select_notification_disable', 1, 'message');

        // Génère une instance enrol_select.
        $numberofusers = array(enrol_select_plugin::ACCEPTED => 5, enrol_select_plugin::MAIN => 0, enrol_select_plugin::WAIT => 0);
        list($plugin, $instance, $users) = $generator->get_plugin_generator('enrol_select')->create_enrol_instance($numberofusers);

        // Teste que l'utilisateur se voit proposer une place sur liste principale quand les quotas sont absents.
        $user = $generator->create_user();
        $this->assertSame('0', $instance->customint3);
        $this->assertSame(enrol_select_plugin::MAIN, $plugin->get_available_status($instance, $user));

        // On active les quotas et définit un maximum d'inscrits sur liste principale.
        $instance->customint3 = 1; // Active les quotas.
        $instance->customint1 = $numberofusers[enrol_select_plugin::ACCEPTED] + $numberofusers[enrol_select_plugin::MAIN]; // Max. places LP.
        $instance->customint2 = 0; // Max. places LC.

        // On ajoute une place sur la liste principale.
        $instance->customint1++;
        $DB->update_record('enrol', $instance);

        // Teste que l'utilisateur se voit proposer une place sur liste principale.
        $this->assertSame(enrol_select_plugin::MAIN, $plugin->get_available_status($instance, $user));

        // On inscrit l'utilisateur.
        $plugin->enrol_user($instance, $user->id, $roleid = 5, $timestart = 0, $timeend = 0, enrol_select_plugin::MAIN);

        // Teste que l'utilisateur se voit proposer la même place sur liste principale.
        $this->assertSame(enrol_select_plugin::MAIN, $plugin->get_available_status($instance, $user));

        // Teste qu'un autre utilisateur se voit refuser une place sur liste complémentaire.
        $user = $generator->create_user();
        $this->assertFalse($plugin->get_available_status($instance, $user));

        // On définit un quota sur la liste complémentaire.
        $instance->customint2 = 2;
        $DB->update_record('enrol', $instance);

        // Teste que l'utilisateur se voit proposer une place sur liste complémentaire.
        $this->assertSame(enrol_select_plugin::WAIT, $plugin->get_available_status($instance, $user));

        // On inscrit l'utilisateur.
        $plugin->enrol_user($instance, $user->id, $roleid = 5, $timestart = 0, $timeend = 0, enrol_select_plugin::WAIT);

        // Teste que l'utilisateur se voit proposer la même place sur liste complémentaire.
        $this->assertSame(enrol_select_plugin::WAIT, $plugin->get_available_status($instance, $user));

        // Teste qu'un autre utilisateur se voit proposer une place sur liste complémentaire.
        // Note : sert à tester la première sortie "return self::WAIT;" de la méthode get_available_status()).
        $user = $generator->create_user();
        $this->assertSame(enrol_select_plugin::WAIT, $plugin->get_available_status($instance, $user));

        // On inscrit l'utilisateur.
        $plugin->enrol_user($instance, $user->id, $roleid = 5, $timestart = 0, $timeend = 0, enrol_select_plugin::WAIT);

        // Teste que l'utilisateur se voit proposer la même place sur liste complémentaire.
        $this->assertSame(enrol_select_plugin::WAIT, $plugin->get_available_status($instance, $user));

        // Teste qu'un autre utilisateur se voit proposer aucune place.
        $user = $generator->create_user();
        $this->assertFalse($plugin->get_available_status($instance, $user));

        // Teste lorsque que le quota sur liste principale est à 0.
        $numberofusers = array(enrol_select_plugin::ACCEPTED => 0, enrol_select_plugin::MAIN => 0, enrol_select_plugin::WAIT => 0);
        list($plugin, $instance, $users) = $generator->get_plugin_generator('enrol_select')->create_enrol_instance($numberofusers);

        // On définit un maximum d'inscrits.
        $instance->customint3 = 1; // Active les quotas.
        $instance->customint1 = 0; // Max. places LP.
        $instance->customint2 = 1; // Max. places LC.

        // Teste qu'on arrive directement sur liste complémentaire.
        $user = $generator->create_user();
        $this->assertSame(enrol_select_plugin::WAIT, $plugin->get_available_status($instance, $user));

        // On inscrit l'utilisateur.
        $plugin->enrol_user($instance, $user->id, $roleid = 5, $timestart = 0, $timeend = 0, enrol_select_plugin::WAIT);

        // Teste que l'utilisateur se voit proposer la même place sur liste complémentaire.
        $this->assertSame(enrol_select_plugin::WAIT, $plugin->get_available_status($instance, $user));

        // Teste l'absence de place pour un autre utilisateur.
        $user = $generator->create_user();
        $this->assertFalse($plugin->get_available_status($instance, $user));

        // Teste lorsque que le quota sur liste secondaire est à 0.
        $numberofusers = array(enrol_select_plugin::ACCEPTED => 0, enrol_select_plugin::MAIN => 0, enrol_select_plugin::WAIT => 0);
        list($plugin, $instance, $users) = $generator->get_plugin_generator('enrol_select')->create_enrol_instance($numberofusers);

        // On définit un maximum d'inscrits.
        $instance->customint3 = 1; // Active les quotas.
        $instance->customint1 = 1; // Max. places LP.
        $instance->customint2 = 0; // Max. places LC.

        // Teste qu'on arrive directement sur liste complémentaire.
        $user = $generator->create_user();
        $mainuser = clone($user);
        $this->assertSame(enrol_select_plugin::MAIN, $plugin->get_available_status($instance, $user));

        // On inscrit l'utilisateur.
        $plugin->enrol_user($instance, $user->id, $roleid = 5, $timestart = 0, $timeend = 0, enrol_select_plugin::MAIN);

        // Teste que l'utilisateur se voit proposer la même place sur liste principale.
        $this->assertSame(enrol_select_plugin::MAIN, $plugin->get_available_status($instance, $user));

        // Teste l'absence de place pour un autre utilisateur.
        $user = $generator->create_user();
        $this->assertFalse($plugin->get_available_status($instance, $user));

        // Ajoute une place sur LC.
        $instance->customint2 = 1;

        // Teste une inscription sur LC.
        $this->assertSame(enrol_select_plugin::WAIT, $plugin->get_available_status($instance, $user));
        $plugin->enrol_user($instance, $user->id, $roleid = 5, $timestart = 0, $timeend = 0, enrol_select_plugin::WAIT);

        // Teste que l'utilisateur sur liste principale se voit toujours attribuer sa place sur LP.
        $this->assertSame(enrol_select_plugin::MAIN, $plugin->get_available_status($instance, $mainuser));

        // On bascule sur une inscription sur liste acceptée par défaut.
        $numberofusers = array(enrol_select_plugin::ACCEPTED => 0, enrol_select_plugin::MAIN => 0, enrol_select_plugin::WAIT => 0);
        list($plugin, $instance, $users) = $generator->get_plugin_generator('enrol_select')->create_enrol_instance($numberofusers);
        $instance->customchar3 = enrol_select_plugin::ACCEPTED;
        $instance->customint3 = 0;

        // Teste qu'on arrive directement sur liste des acceptés lorsque les quotas sont désactivés.
        $user = $generator->create_user();
        $this->assertSame(enrol_select_plugin::ACCEPTED, $plugin->get_available_status($instance, $user));

        // Teste qu'on arrive directement sur liste des acceptés lorsque les quotas sont activés.
        $instance->customint1 = 10; // Active les quotas.
        $instance->customint2 = 10; // Max. places LP.
        $instance->customint3 = 1; // Max. places LC.
        $user = $generator->create_user();
        $this->assertSame(enrol_select_plugin::ACCEPTED, $plugin->get_available_status($instance, $user));
    }

    public function test_get_default_enrolment_list() {
        $instance = new stdClass();

        // Teste la valeur enrol_select_plugin::ACCEPTED.
        $instance->customchar3 = enrol_select_plugin::ACCEPTED;
        $this->assertSame(enrol_select_plugin::ACCEPTED, enrol_select_plugin::get_default_enrolment_list($instance));

        // Teste la valeur enrol_select_plugin::MAIN.
        $instance->customchar3 = enrol_select_plugin::MAIN;
        $this->assertSame(enrol_select_plugin::MAIN, enrol_select_plugin::get_default_enrolment_list($instance));

        // Teste que par défaut la valeur enrol_select_plugin::MAIN soit retournée.
        $instance->customchar3 = null;
        $this->assertSame(enrol_select_plugin::MAIN, enrol_select_plugin::get_default_enrolment_list($instance));
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

        // On active les quotas et définit le nombre de places sur liste principale.
        $instance->customint3 = 1;
        $instance->customint1 = $numberofusers[enrol_select_plugin::ACCEPTED] + $numberofusers[enrol_select_plugin::MAIN];
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

        // Augmente le nombre de places sur liste principale.
        $instance->customint1++;
        $DB->update_record('enrol', $instance);

        // Teste que la remontée se fait par ordre chronologique.
        $conditions = array('enrolid' => $instance->id, 'status' => enrol_select_plugin::WAIT);
        $this->assertSame(1, $DB->count_records('user_enrolments', $conditions));

        $newuser = $generator->create_user();
        sleep(1);
        $plugin->enrol_user($instance, $newuser->id, $roleid = 5, $timestart = 0, $timeend = 0, enrol_select_plugin::WAIT);

        $this->assertSame(2, $DB->count_records('user_enrolments', $conditions));

        $plugin->refill_main_list($instance, $USER->id);

        $this->assertSame(1, $DB->count_records('user_enrolments', $conditions));

        $waitingenrolment = $DB->get_record('user_enrolments', $conditions);
        $this->assertSame($newuser->id, $waitingenrolment->userid);
    }

    public function test_unenrol_user() {
        global $DB, $USER;

        $adminid = $USER->id;
        $generator = $this->getDataGenerator();

        // Désactive les notifications.
        set_config('enrol_select_select_notification_disable', 1, 'message');

        // Génère une instance enrol_select.
        $numberofusers = array(enrol_select_plugin::ACCEPTED => 0, enrol_select_plugin::MAIN => 2, enrol_select_plugin::WAIT => 2);
        list($plugin, $instance, $users) = $generator->get_plugin_generator('enrol_select')->create_enrol_instance($numberofusers);

        // Active les quotas et la remontée automatique.
        $instance->customint3 = 1;
        $instance->customint1 = 2;
        $instance->customint2 = 2;
        $instance->customchar2 = '1';
        $DB->update_record('enrol', $instance);

        // Désinscrit un étudiant.
        $user = array_shift($users[enrol_select_plugin::MAIN]);
        $USER->id = $user->id;
        $plugin->unenrol_user($instance, $user->id);

        // Vérifie que l'utilisateur n'est plus inscrit.
        $conditions = array('enrolid' => $instance->id, 'userid' => $user->id);
        $this->assertFalse($DB->get_record('user_enrolments', $conditions));

        // Vérifie que le remplissage n'a pas eu lieu et qu'il y a bien 2 utilisateurs sur liste principale.
        $conditions = array('enrolid' => $instance->id, 'status' => enrol_select_plugin::MAIN);
        $this->assertSame(2, $DB->count_records('user_enrolments', $conditions));

        // Vérifie que le remplissage n'a pas eu lieu et qu'il y a bien 2 utilisateurs sur liste complémentaire.
        $conditions = array('enrolid' => $instance->id, 'status' => enrol_select_plugin::WAIT);
        $this->assertSame(1, $DB->count_records('user_enrolments', $conditions));

        // Désactive la remontée automatique.
        $instance->customchar2 = '0';
        $DB->update_record('enrol', $instance);

        // Désinscrit un utilisateur.
        $user = array_shift($users[enrol_select_plugin::MAIN]);
        $USER->id = $user->id;
        $plugin->unenrol_user($instance, $user->id);

        // Vérifie que l'utilisateur n'est plus inscrit.
        $conditions = array('enrolid' => $instance->id, 'userid' => $user->id);
        $this->assertFalse($DB->get_record('user_enrolments', $conditions));

        // Vérifie que le remplissage a eu lieu et qu'il y a maintenant 2 utilisateurs sur liste principale.
        $conditions = array('enrolid' => $instance->id, 'status' => enrol_select_plugin::MAIN);
        $this->assertSame(1, $DB->count_records('user_enrolments', $conditions));

        // Vérifie que le remplissage a eu lieu et qu'il reste 1 utilisateur sur liste complémentaire.
        $conditions = array('enrolid' => $instance->id, 'status' => enrol_select_plugin::WAIT);
        $this->assertSame(1, $DB->count_records('user_enrolments', $conditions));

        $USER->id = $adminid;
    }
}
