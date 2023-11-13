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

namespace enrol_select\task;

use enrol_select_plugin;
use UniversiteRennes2\Apsolu\Payment;

/**
 * Classe représentant la tâche pour vérifier les paiements à l'inscription.
 *
 * @package    enrol_select
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_enrolment_payment extends \core\task\adhoc_task {
    /**
     * Retourne le nom de la tâche.
     *
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('check_enrolment_payment', 'enrol_select');
    }

    /**
     * Execute la tâche.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/enrol/select/lib.php');
        require_once($CFG->dirroot.'/local/apsolu/classes/apsolu/payment.php');

        $customdata = $this->get_custom_data();
        $course = $DB->get_record('course', ['id' => $customdata->courseid]);

        if ($course === false) {
            // Le cours ne semble plus exister.
            return;
        }

        // Récupère les cartes dues pour le cours donné.
        $cards = [];
        foreach (Payment::get_user_cards_status_per_course($customdata->courseid, $this->get_userid()) as $card) {
            if ($card->status !== Payment::DUE) {
                continue;
            }

            $cards[] = $card->name;
        }

        if (isset($cards[0]) === false) {
            // Aucune carte due.
            return;
        }

        $enrolselectplugin = new enrol_select_plugin();

        // Détermine si l'utilisateur est toujours inscrit.
        $conditions = [
            'enrolid' => $customdata->enrolid,
            'userid' => $this->get_userid(),
            'status' => $enrolselectplugin::ACCEPTED,
        ];
        if ($DB->count_records('user_enrolments', $conditions) === 0) {
            // L'utilisateur n'est plus inscrit à ce cours.
            return;
        }

        // Désinscrit l'utilisateur du cours.
        $conditions = ['id' => $customdata->enrolid, 'enrol' => 'select', 'courseid' => $customdata->courseid];
        $instance = $DB->get_record('enrol', $conditions);

        $enrolselectplugin->unenrol_user($instance, $this->get_userid());

        // Notifie l'utilisateur.
        $cardlist = implode('</li><li>', $cards);
        $functionalcontact = get_config('local_apsolu', 'functional_contact');
        $params = ['coursename' => $course->fullname, 'cards' => $cardlist, 'contact' => $functionalcontact];
        $message = get_string('unenrolment_message', 'enrol_select', $params);

        $eventdata = new \core\message\message();
        $eventdata->name = 'select_notification';
        $eventdata->component = 'enrol_select';
        $eventdata->userfrom = get_admin();
        $eventdata->userto = $this->get_userid();
        $params = format_string($course->fullname, $striplinks = true, $course->id);
        $eventdata->subject = get_string('unenrolment_from', 'enrol_select', $course->fullname);
        $eventdata->fullmessage = $message;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml = $message;
        $eventdata->smallmessage = '';
        $eventdata->notification = 1;
        $eventdata->courseid = $course->id;

        message_send($eventdata);
    }
}
