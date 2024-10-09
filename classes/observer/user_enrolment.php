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

use context_course;
use core\event\user_enrolment_deleted;
use local_apsolu\core\federation\course;

/**
 * Classe permettant d'écouter les évènements diffusés par Moodle.
 *
 * @package   enrol_select
 * @copyright 2024 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_enrolment {
    /**
     * Écoute l'évènement user_enrolment_deleted.
     *
     * @param user_enrolment_deleted $event Évènement diffusé par Moodle.
     *
     * @return void
     */
    public static function deleted(user_enrolment_deleted $event) {
        global $DB;

        $federationcourse = new course();
        $federationcourseid = $federationcourse->get_courseid();
        if (empty($federationcourseid) === true) {
            // Le cours FFSU n'est pas configuré.
            return;
        }

        if ($event->courseid !== $federationcourseid) {
            return;
        }

        // Supprime les références à l'inscription FFSU.
        $DB->delete_records('apsolu_federation_adhesions', ['userid' => $event->relateduserid]);

        // Supprime les fichiers déposés.
        $context = context_course::instance($federationcourseid, MUST_EXIST);

        $where = "contextid = :contextid
                AND component = :component
                AND filearea = :filearea
                AND userid = :userid";
        $params['contextid'] = $context->id;
        $params['component'] = 'local_apsolu';
        $params['userid'] = $event->relateduserid;

        $fs = get_file_storage();
        foreach (['parentalauthorization', 'medicalcertificate'] as $filearea) {
            $params['filearea'] = $filearea;

            $filerecords = $DB->get_recordset_select('files', $where, $params);
            foreach ($filerecords as $filerecord) {
                $fs->get_file_instance($filerecord)->delete();
            }
            $filerecords->close();
        }
    }
}
