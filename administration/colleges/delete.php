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
 * Page de gestion des suppressions de populations.
 *
 * @package    enrol_select
 * @copyright  2023 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM); // Confirmation hash.

$url = new moodle_url('/enrol/select/administration.php', ['tab' => 'colleges', 'action' => 'delete', 'id' => $id]);
$returnurl = new moodle_url('/enrol/select/administration.php', ['tab' => 'colleges']);

$instance = $DB->get_record('apsolu_colleges', ['id' => $id], '*', MUST_EXIST);
$deletehash = md5($calendar->id);

if ($confirm === $deletehash) {
    // We do - time to delete the course.
    require_sesskey();

    try {
        $transaction = $DB->start_delegated_transaction();

        // Supprime tous les associations collegeid/cohortid.
        $sql = "DELETE FROM {apsolu_colleges_members} WHERE collegeid = :collegeid";
        $DB->execute($sql, ['collegeid' => $instance->id]);

        // Supprime la population.
        $DB->delete_records('apsolu_colleges', ['id' => $instance->id]);

        $transaction->allow_commit();
    } catch (Exception $exception) {
        // On ne peut pas utiliser $transaction->rollback($exception);, car Moodle redirige vers la homepage.
        $message = get_string('an_error_occurred_while_deleting_record', 'local_apsolu');
        redirect($returnurl, $message, null, \core\output\notification::NOTIFY_ERROR);
    }

    $message = get_string('college_deleted', 'local_apsolu');
    redirect($returnurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

// Affichage du message de confirmation.
$data = new stdClass();
$data->message = get_string('do_you_want_to_delete_college', 'local_apsolu', $instance->name);
$message = $OUTPUT->render_from_template('local_apsolu/courses_form_delete_message', $data);

// Bouton de validation.
$urlarguments = ['tab' => 'colleges', 'action' => 'delete', 'id' => $id, 'confirm' => $deletehash];
$confirmurl = new moodle_url('/enrol/select/administration.php', $urlarguments);
$confirmbutton = new single_button($confirmurl, get_string('delete'), 'post');

// Bouton d'annulation.
echo $OUTPUT->confirm($message, $confirmbutton, $returnurl);
