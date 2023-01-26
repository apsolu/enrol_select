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
 * enrol_select data generator
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\course;

/**
 * Data generator class
 *
 * @package    enrol_select
 * @category   test
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_generator extends testing_module_generator {
    /**
     * Fonction pour générer :
     * - un cours APSOLU
     * - une méthode d'inscription par voeux
     * - un certain nombre d'utilisateurs
     *
     * @param array $users Un tableau contenant le nombre d'utilisateurs à créer par status.
     *      Exemple : array(enrol_select_plugin::ACCEPTED => 4, enrol_select_plugin::MAIN => 2, enrol_select_plugin::WAIT => 8);
     *
     * @return array
     */
    public function create_enrol_instance(array $users = array()) {
        global $DB;

        // Créer un cours APSOLU.
        $course = new course();
        $data = advanced_testcase::getDataGenerator()->get_plugin_generator('local_apsolu')->get_course_data();
        $course->save($data);

        // Ajoute une instance 'enrol_select' au cours.
        $plugin = enrol_get_plugin('select');
        $instanceid = $plugin->add_instance($course, $plugin->get_instance_defaults());

        $instance = $DB->get_record('enrol', array('id' => $instanceid));

        $enroledusers = array();
        foreach ($users as $status => $numberofusers) {
            $users[$status] = array();
            for ($i = 0; $i < $numberofusers; $i++) {
                $user = advanced_testcase::getDataGenerator()->create_user();

                $plugin->enrol_user($instance, $user->id, $roleid = 5, $timestart = 0, $timeend = 0, $status);
                $users[$status][$user->id] = $user;
            }
        }

        return array($plugin, $instance, $users);
    }
}
