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

namespace enrol_select\form;

use core_form\dynamic_form;
use context;
use moodle_url;
use moodle_exception;
use stdClass;
use html_writer;
use enrol_select\administration\college;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/enrol/select/lib.php');

/**
 * Classe pour le formulaire permettant de programmer les réglages de voeux pour les populations.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_college_form extends dynamic_form {
    /**
     * Définit les champs du formulaire.
     *
     * @return void
     */
    public function definition() {
        global $CFG;
        global $DB;

        $mform = $this->_form;

        // Contexte id : récupéré via la requête AJAX puis propagé par un champ caché.
        $contextid = $this->optional_param('contextid', 0, PARAM_INT);
        $mform->addElement('hidden', 'contextid', $contextid);
        $mform->setType('contextid', PARAM_INT);

        // Id de la tâche (mode édition uniquement) : récupéré via la requête AJAX puis propagé par un champ caché.
        $taskid = $this->optional_param('taskid', 0, PARAM_INT);

        // Calendrier.
        $datepicker = $mform->createElement(
            'date_time_selector',
            'nextdatetime',
            get_string('program_date', 'enrol_select'),
            ['optional' => false, 'startyear' => date("Y"), 'stopyear'  => date("Y") + 1]
        );

        $task = new stdClass();

        // Mode édition : l'id de la population est passé en paramètre lors de l'appel du formulaire.
        if (empty($taskid) == false) {
            $task = college::get_rule_from_task_id($taskid);
            // D'abord le nom de la population.
            $college = college::get_record(['id' => $task->collegeid], '*', MUST_EXIST);

            $title = html_writer::tag(
                'h5',
                get_string('target_population', 'enrol_select', $college->name),
                ['class' => 'info']
            );

            $mform->addElement('html', $title);

            // Puis le champ calendrier.
            $mform->addElement($datepicker);
            $mform->setDefault('nextdatetime', $task->datetime);

            // Champs cachés pour propager les données lors de la soumission du formulaire.
            // ID de la tâche.
            $mform->addElement('hidden', 'taskid', $task->id);
            $mform->setType('taskid', PARAM_INT);

            // ID de la population.
            $mform->addElement('hidden', 'collegeid', $college->id);
            $mform->setType('collegeid', PARAM_INT);

            // Champ caché  : témoin pour déclencher la suppression plutôt que la modification de la règle.
            $mform->addElement('hidden', 'deletesubmit', 0);
            $mform->setType('deletesubmit', PARAM_INT);
        } else { // Mode création (ajout d'une règle).
            // D'abord le calendrier.
            $mform->addElement($datepicker);

            // Puis le select pour la population.
            $colleges = $DB->get_records_menu('apsolu_colleges', $conditions = null, $sort = 'name', 'id, name');

            $mform->addElement('select', 'collegeid', get_string('college', 'enrol_select'), $colleges);
            $mform->setType('collegeid', PARAM_INT);
        }

        // Quotas.
        $mform->addElement('text', 'maxwish', get_string('maximum_wishes', 'enrol_select'));
        $mform->setType('maxwish', PARAM_INT);
        $mform->addRule('maxwish', get_string('required'), 'required', null, 'client');
        $mform->setDefault('maxwish', $task->maxwish);

        $mform->addElement('text', 'minregister', get_string('minimum_enrolments', 'enrol_select'));
        $mform->setType('minregister', PARAM_INT);
        $mform->addRule('minregister', get_string('required'), 'required', null, 'client');
        $mform->setDefault('minregister', $task->minregister);

        $mform->addElement('text', 'maxregister', get_string('maximum_enrolments', 'enrol_select'));
        $mform->setType('maxregister', PARAM_INT);
        $mform->addRule('maxregister', get_string('required'), 'required', null, 'client');
        $mform->setDefault('maxregister', $task->maxregister);

        // Champ qui permet d'afficher une erreur globale (aucun changement détecté).
        $mform->addElement('static', 'globalerror', '', '');
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     *
     * @return array The errors that were found.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (empty($data['deletesubmit']) == false) {
            return []; // Pas de validation nécessaire pour une suppression.
        }

        // Vérification de la date : doit être dans le futur.
        if ($data['nextdatetime'] < time()) {
            $errors['nextdatetime'] = get_string('program_date_error', 'enrol_select');
        }

        // Vérifie que le nombre de voeux n'est pas inférieur au nombre maximum d'inscription.
        if ($data['maxwish'] < $data['maxregister']) {
            $errors['maxwish'] = get_string('maximum_wishes_must_be_greater_than_or_equal_to_maximum_enrolments', 'enrol_select');
        }

        // Vérifie que le nombre maximum de voeux n'est pas inférieur au nombre minimum de voeux.
        if ($data['maxregister'] < $data['minregister']) {
            $label = get_string('maximum_enrolments_must_be_greater_than_or_equal_to_minimum_enrolments', 'enrol_select');
            $errors['maxregister'] = $label;
        }

        // Vérifie s'il y a déjà une autre tâche programmée pour cette population à cette date là.
        $collegetasks = college::get_college_wish_rules($data->collegeid);
        if (isset($collegetasks[$data['nextdatetime']])) {
            // La valeur de taskid envoyée par le formulaire est nulle en cas de création d'une règle, et doit être
            // différente de l'id de la tâche trouvée sur cette date en cas de modification d'une règle existante.
            if ($collegetasks[$data['nextdatetime']][$data['collegeid']]->taskid != $data['taskid']) {
                $errors['nextdatetime'] = get_string('invalid_dateval', 'enrol_select');
            } else if (empty($data['taskid']) == false) { // On vérifie s'il y a bien eu des changements (mode édition uniquement).
                // La date de la tâche n'a pas été changée donc on vérifie les autres champs.
                $current = $collegetasks[$data['nextdatetime']][$data['collegeid']];
                if (
                    $current->maxwish == $data['maxwish'] &&
                    $current->maxregister == $data['maxregister'] &&
                    $current->minregister == $data['minregister']
                ) {
                    // Aucune modification détectée, la date n'a pas changé : enregistrement impossible.
                    $errors['globalerror'] = get_string('no_changes_detected', 'enrol_select');
                }
            }
        }

        return $errors;
    }

    /**
     * Renvoie le contexte du formulaire.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $contextid = $this->optional_param('contextid', 0, PARAM_INT);

        return context::instance_by_id($contextid, MUST_EXIST);
    }

    /**
     * Vérifie les droits de l'utilisateur sur le formulaire.
     *
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {

        $capabilities = [
            'moodle/course:create',
            'moodle/site:config',
        ];
        $context = $this->get_context_for_dynamic_submission();

        if (!has_any_capability($capabilities, $context)) {
            throw new \required_capability_exception($context, implode(' or ', $capabilities), 'nopermissions', '');
        }
    }

    /**
     * Traite la soumission du formulaire.
     *
     * @return array
     */
    public function process_dynamic_submission() {
        global $DB;

        $data = $this->get_data();

        // On vérifie la pertinence de la valeur collegeid passé dans la requête.
        $college = college::get_record(['id' => $data->collegeid], 'name', MUST_EXIST);
        $notification = new stdClass();
        $notification->strfdate = userdate($data->nextdatetime, get_string('strftimedatetime', 'local_apsolu'));
        $notification->collegename = $college->name;

        if (empty($data->taskid) == false && empty($data->deletesubmit) == false) {
            // Suppression.
            $notificationstr = 'delete_rule_successful';
            college::delete_college_wishes_rule($data->taskid);
        } else {
            // Création ou mise à jour d'une règle.
            $notificationstr = empty($data->taskid) ? 'program_rule_successful' : 'update_rule_successful';
            college::set_college_wishes_rule($data);
        }

        \core\notification::success(get_string($notificationstr, 'enrol_select', $notification));

        return [
            'success' => true,
        ];
    }

    /**
     * Récupère les données envoyées par la requête AJAX.
     *
     */
    public function set_data_for_dynamic_submission(): void {
        global $arguments;
        $data = new stdClass();

        if (!empty($arguments)) {
            $decodedargs = json_decode($arguments, true);
            if (isset($decodedargs[0]['args']['formdata'])) {
                $formdata = $decodedargs[0]['args']['formdata'];

                $params = [];
                parse_str($formdata, $params);
                foreach ($params as $key => $value) {
                    if (empty($value) == false) {
                        $data->$key = $value;
                    }
                }
            }
        }
        $this->set_data($data);
    }

    /**
     * Renvoie l'url à utilser lors de la soumission du formulaire et l'envoi des données en AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/enrol/select/administration.php', ['tab' => 'enrolment_methods_overview']);
    }
}
