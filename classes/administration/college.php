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

namespace enrol_select\administration;

use local_apsolu\core\record;
use moodle_exception;
use stdClass;
use enrol_select\task\change_wishes as wishTask;
use core\task\manager as taskmanager;

/**
 * Classe gérant l'administration des populations.
 *
 * @package    enrol_select
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class college extends record {
    /**
     * Nom de la table de référence en base de données.
     */
    const TABLENAME = 'apsolu_colleges';

    /**
     * Nom de la table qui stocke les cohortes associées aux populations.
     */
    const MEMBERSTABLENAME = 'apsolu_colleges_members';

    /** @var int Identifiant numérique de la population. */
    public $id = 0;

    /** @var string $name Nom de la population. */
    public $name = '';

    /** @var int Nombre de voeux maximum autorisé par personne dans cette population. */
    public $maxwish = 0;

    /** @var int Nombre d'inscriptions minimum requises par personne dans cette population. */
    public $minregister = 0;

    /** @var int Nombre d'inscriptions maximum autorisé par personne dans cette population. */
    public $maxregister = 0;

    /**
     * Supprime une règle de programmation (tâches adhoc) pour gérer les voeux des populations.
     *
     * @param int $taskid la tâche à supprimer.
     * @return void
     */
    public static function delete_college_wishes_rule(int $taskid): void {
        global $DB;
        $record = wishTask::get_task($taskid); // On vérifie que la tâche existe.
        $DB->delete_records('task_adhoc', ['id' => $record->id]);
    }

    /**
     * Supprime toutes les règles de programmation (tâches adhoc) de gestion des voeux pour cette population.
     *
     * @return void
     */
    public function delete_college_wishes_rules(): void {
        global $DB;
        $records = wishTask::get_college_tasks($this->id); // On vérifie que la tâche existe.
        if (empty($records) == false) {
            $DB->delete_records_list('task_adhoc', 'id', array_keys($records));
        }
    }

    /**
     * Insère et met à jour une règle programmée (tâche adhoc) pour gérer les quotas de voeux des populations.
     *
     * @param stdClass $formdata les données envoyées par le formulaire.
     * @return void
     */
    public static function set_college_wishes_rule(stdClass $formdata): void {
        $customdata = new stdClass();
        $customdata->collegeid = $formdata->collegeid;
        $customdata->maxwish = $formdata->maxwish;
        $customdata->maxregister = $formdata->maxregister;
        $customdata->minregister = $formdata->minregister;

        if (empty($formdata->taskid) == false) { // Edition.
            wishtask::update_task($formdata->taskid, $customdata, $formdata->nextdatetime);
        } else { // Création.
            wishtask::create_task($customdata, $formdata->nextdatetime);
        }
    }

    /**
     * Recherches les règles programmées (tâches adhoc) pour gérer les quotas de voeux d'une ou plusieurs populations.
     * Si certaines tâches sont présentes plusieurs fois sur le même créneau horaire et pour la même population,
     * on supprime les tâches en trop pour ne conserver que la règle créée en dernier.
     * On supprime également une tâche dont la date d'exécution serait passée.
     *
     * @param integer|null $collegeid si fourni, on filtre les résultat par population.
     * @return array <int, array> la liste des règles, groupées et triées par date d'exécution de la tâche.
     *
     */
    public static function get_college_wish_rules(?int $collegeid = null): array {
        global $DB;

        // On récupère toutes les tâches de gestion des quotas de voeux, triées par date d'exécution et
        // par date de création (la plus récente en premier).
        $classname = taskmanager::get_canonical_class_name(wishTask::class);
        $sql = 'SELECT * FROM {task_adhoc} WHERE classname = ? ORDER BY nextruntime, timecreated DESC';
        $params = [$classname];
        $tasks = $DB->get_records_sql($sql, $params);

        $rules = [];

        foreach ($tasks as $task) {
            $data = json_decode($task->customdata);

            // Suppression des tâches "périmées".
            if ($task->nextruntime < time()) {
                taskmanager::delete_adhoc_task($task->id);
                debugging(get_string('delete_past_college_rule_task'), $level = DEBUG_DEVELOPER);
                continue;
            }

            // Si un id de population a été fourni, on ne conserve que les tâches qui concernent cette population.
            if ($collegeid == null || $data->collegeid == $collegeid) {
                // On regroupe les tâches par date d'exécution.
                if (isset($rules[$task->nextruntime]) == false) {
                    $rules[$task->nextruntime] = [];
                } else if (isset($rules[$task->nextruntime][$data->collegeid])) {
                    // Suppression des tâches "doublons" (même date & heure, même population) :
                    // seule la première est conservée soit la plus récente.
                    taskmanager::delete_adhoc_task($task->id);
                    debugging(get_string('delete_ovewritten_college_rule_task'), $level = DEBUG_DEVELOPER);
                    continue;
                }

                // On ajoute la règle dans la liste des règles prévues à cette date & heure.
                $rules[$task->nextruntime][$data->collegeid] = self::get_rule_from_task_record($task);
            }
        }

        return $rules;
    }

    /**
     * Renvoie un objet contenant toutes les informations relatives à la règle programmée à partir de la représentation de la tâche.
     *
     * @param stdClass $taskrecord la tâche (représentée en objet).
     * @return stdClass la règle programmée.
     */
    public static function get_rule_from_task_record(stdClass $taskrecord): stdClass {
        $data = json_decode($taskrecord->customdata);
        $rule = new stdClass();
        $rule->collegeid = (int) $data->collegeid;
        $rule->maxwish = $data->maxwish;
        $rule->minregister = $data->minregister;
        $rule->maxregister = $data->maxregister;
        $rule->taskid = $taskrecord->id;
        $rule->datetime = $taskrecord->nextruntime;

        return $rule;
    }

    /**
     * Renvoie la règle de programmation correspondant à la tâche.
     *
     * @param int $taskid id de la tâche.
     * @return stdClass la règle programmée.
     */
    public static function get_rule_from_task_id(int $taskid): stdClass {
        $taskrecord = wishTask::get_task($taskid);

        return self::get_rule_from_task_record($taskrecord);
    }

    /**
     * Retourne les membres (cohortes) appartenant à la population.
     *
     * @return void
     */
    public function get_members() {
        global $DB;
        return $DB->get_records(self::MEMBERSTABLENAME, ['collegeid' => $this->id], '', 'cohortid');
    }
}
