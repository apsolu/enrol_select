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

use stdClass;
use core\task\manager as taskmanager;

/**
 * Classe représentant la tâche permettant de modifier le nombre de voeux max. et d'inscriptions (min/max) pour une population.
 *
 * @package   enrol_select
 * @copyright 2026 Université Rennes 2
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class change_wishes extends \core\task\adhoc_task {
    /**
     * Retourne le nom de la tâche.
     *
     * @return string
     */
    public function get_name(): string {
        // Shown in admin screens.
        return get_string('change_wishes', 'enrol_select');
    }

     /**
      * Execute la tâche.
      *
      * @return void
      */
    public function execute(): void {
        global $CFG, $DB;

        // Récupère la configuration.
        $customdata = $this->get_custom_data();
        $college = $DB->get_record('apsolu_colleges', ['id' => $customdata->collegeid]);

        if ($college === false) {
            // La population ne semble plus exister.
            return;
        }

        $college->id = $customdata->collegeid;
        $college->maxwish = $customdata->maxwish;
        $college->maxregister = $customdata->maxregister;
        $college->minregister = $customdata->minregister;

        // Met à jour les différents seuils dans la DB.
        $DB->update_record('apsolu_colleges', $college);

        mtrace(sprintf(
            'Modification des règles d’inscription pour la population « %s ». Voeux (max): %s. Inscriptions : %s (min) - %s (max)',
            $college->name,
            $customdata->maxwish,
            $customdata->minregister,
            $customdata->maxregister
        ));
    }

    /**
     * Retourne la représentation de la tâche adhoc correspondant à l'identifiant.
     *
     * @param int $taskid identifiant de la tâche.
     *
     * @return stdClass retourne false si l'id ne correspond pas à une tâche adhoc "change_wishes".
     */
    public static function get_task(int $taskid): stdClass {
        global $DB;
        $record = false;

        $taskname = self::class;

        $record = $DB->get_record('task_adhoc', ['id' => $taskid]);
        if (!$record) {
            throw new \moodle_exception('invalidtaskid');
        }
        if ($record->classname != taskmanager::get_canonical_class_name(self::class)) {
            throw new \moodle_exception('invalidtaskid');
        }

        return $record;
    }

    /**
     * Création d'une nouvelle tâche.
     *
     * @param stdClass $customdata
     * @param int $nextruntime
     *
     * @return void
     */
    public static function create_task(stdClass $customdata, int $nextruntime): void {
        $task = new self();
        $task->set_next_run_time($nextruntime);
        $task->set_custom_data($customdata);
        taskmanager::queue_adhoc_task($task);
    }


    /**
     * Mise à jour d'une tâche existante.
     *
     * @param int $taskid tâche à mettre à jour.
     * @param stdClass $customdata
     * @param int $nextruntime
     *
     * @return void
     */
    public static function update_task(int $taskid, stdClass $customdata, int $nextruntime): void {
        global $DB;
        $record = self::get_task($taskid);
        $task = taskmanager::adhoc_task_from_record($record);
        $task->set_custom_data($customdata);
        $task->set_next_run_time($nextruntime);
        $update = taskmanager::record_from_adhoc_task($task);
        $update->timecreated = time(); // On change la date de création de la tâche.
        $DB->update_record('task_adhoc', $update);
    }

    /**
     * Recherche et renvoie une tâche adhoc qui correspond à la date d'exécution et à la population, avec ou non
     * comparaison du contenu des autres variables (nombre de voeux etc.).
     *
     * @param int $collegeid l'id de la population.
     *
     * @return array<int, stdClass> la liste des tâches pour cette population.
     */
    public static function get_college_tasks(int $collegeid): array {
        global $DB;
        $record = false;

        $task = new self();
        $classname = taskmanager::get_canonical_class_name($task);
        $component = $task->get_component();

        // On recherche une tâche correspondant à la date et à la population, les autres variables ne sont pas comparées.
        $collegeparam = '{%"collegeid":"' . $collegeid . '"%}'; // Seul collegeid doit être recherché.
        $params = [$classname, $component, $collegeparam];
        $sql = 'classname = ? AND component = ? AND customdata LIKE ?';
        $records = $DB->get_records_select('task_adhoc', $sql, $params);

        if ($records !== false) {
            return $records;
        }

        return [];
    }
}
