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

// phpcs:disable moodle.Commenting.TodoComment.MissingInfoInline

namespace enrol_select\output;

use context_system;
use enrol_select_plugin;
use local_apsolu\core\course;
use local_apsolu\output\courses as CoursesRenderer;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderer pour la page principale des inscriptions.
 *
 * @package    enrol_select
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview implements renderable, templatable {
    /** @var array $courses Tableau des cours indexés par courseformatid, puis courseid. */
    public array $courses;

    /** @var array $enrols Tableau des méthodes d'inscription indexés par courseid. */
    public array $enrols;

    /** @var ?array $cacheroles Tableau de cache des rôles. */
    private static ?array $cacheroles = null;

    /** @var ?array $cacheenrolments Tableau de cache des inscriptions. */
    private static ?array $cacheenrolments = null;

    /**
     * Constructeur.
     *
     * @param array $courses Tableau de cours. La structure du tableau est array[courseformatid][courseid] = apsolu course object.
     * @param array $enrols Tableau de méthodes d'inscription. La structure du tableau est array[courseid] = enrol db object.
     */
    public function __construct(array $courses, array $enrols) {
        $this->courses = $courses;
        $this->enrols = $enrols;

        // Formate les méthodes d'inscription pour récupérer les textes des places disponibles à afficher.
        $this->format_enrols();
    }

    /**
     * Exporte les données à passer au template Mustache.
     *
     * @param renderer_base $output
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $CFG, $DB, $OUTPUT;

        $coursetypes = $DB->get_records('apsolu_courses_types');
        $categories = $DB->get_records('course_categories');

        $data = new stdClass();

        // Variables that will be passed to the JS helper.
        $data->wwwroot = $CFG->wwwroot;
        $data->sesskey = sesskey();

        $data->info_pix_url = $OUTPUT->image_url('help');
        $data->marker_pix_url = $OUTPUT->image_url('a/marker', 'enrol_select');
        $data->www_url = $CFG->wwwroot;
        $data->is_courses_creator = has_capability('moodle/course:create', context_system::instance());

        // Formate les données pour afficher le tableau des activités proposées.
        $data->course_formats = [];
        $data->count_activities = 0;

        $formatindex = -1;
        $currentformat = null;
        foreach ($this->courses as $coursetype => $courses) {
            if (isset($coursetypes[$coursetype]) === false) {
                continue;
            }

            $formatindex++;
            $currentactivityid = null;

            // Regroupe les cours par format de cours et activités.
            foreach ($courses as $course) {
                if ($currentactivityid !== $course->category) {
                    if ($currentactivityid === null) {
                        // Initialise le format de cours et les entêtes attendus.
                        $data->course_formats[$formatindex] = $coursetypes[$coursetype];
                        $data->course_formats[$formatindex]->activities = [];

                        $headers = CoursesRenderer::get_headers($coursetype, CoursesRenderer::VISIBLE_ONLY_PUBLIC);
                        $data->course_formats[$formatindex]->headers = [];
                        foreach ($headers as $shortname => $label) {
                            $data->course_formats[$formatindex]->headers[] = (object) ['shortname' => $shortname,
                                'label' => $label];
                        }
                    } else {
                        // Trie les cours par nom, heure, niveau, etc au sein d'une activité.
                        $this->format_courses($data->course_formats[$formatindex]->activities[$category->id]->courses, $headers);
                    }

                    $category = $categories[$course->category];
                    $currentactivityid = $course->category;

                    $data->course_formats[$formatindex]->activities[$category->id] = new stdClass();
                    $data->course_formats[$formatindex]->activities[$category->id]->sportid = $category->id;
                    $data->course_formats[$formatindex]->activities[$category->id]->name = $category->name;
                    $data->course_formats[$formatindex]->activities[$category->id]->description = $category->description;
                    $data->course_formats[$formatindex]->activities[$category->id]->courses = [];
                    $data->count_activities++;
                }

                $data->course_formats[$formatindex]->activities[$category->id]->courses[$course->id] = $course;
            }

            if ($currentactivityid === null) {
                // Aucun cours n'était présent pour ce format de cours.
                unset($data->course_formats[$formatindex]);
                continue;
            }

            // Trie les cours par nom, heure, niveau, etc au sein d'une activité.
            $this->format_courses($data->course_formats[$formatindex]->activities[$category->id]->courses, $headers);

            // Trie les activités par nom au sein d'un format de cours.
            uasort($data->course_formats[$formatindex]->activities, function ($a, $b) {
                return strcoll($a->name, $b->name);
            });

            $data->course_formats[$formatindex]->activities = array_values($data->course_formats[$formatindex]->activities);
            $data->course_formats = array_values($data->course_formats);
        }

        $data->roles = array_values(enrol_select_get_custom_student_roles());
        $data->show_tabs = count($data->course_formats) > 1;

        $data->filters = '';
        if (isset($time, $cohorts) === true) {
            $data->filters = '&time=' . $time . '&cohorts=' . implode(',', $cohorts);
        }

        return $data;
    }

    /**
     * Ajoute les textes des places disponibles en propriété de dans l'objet $this->enrols.
     *
     * @return void
     */
    private function format_enrols(): void {
        global $DB;

        $sql = "SELECT e.id, e.courseid, e.customint1, e.customint2, e.customint3, ue.status, COUNT(userid) AS count
                  FROM {enrol} e
                  JOIN {user_enrolments} ue ON e.id = ue.enrolid
                 WHERE e.enrol = 'select'
              GROUP BY e.id, ue.status";
        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            if (isset($this->enrols[$record->courseid]) === false || $this->enrols[$record->courseid]->id !== $record->id) {
                continue;
            }

            if (isset($this->enrols[$record->courseid]->availability_status) === false) {
                $this->enrols[$record->courseid]->availability_status = new stdClass();
                $this->enrols[$record->courseid]->availability_status->main = 0;
                $this->enrols[$record->courseid]->availability_status->wait = 0;
            }

            switch ($record->status) {
                case enrol_select_plugin::ACCEPTED:
                case enrol_select_plugin::MAIN:
                    $this->enrols[$record->courseid]->availability_status->main += $record->count;
                    break;
                case enrol_select_plugin::WAIT:
                    $this->enrols[$record->courseid]->availability_status->wait += $record->count;
            }
        }
        $recordset->close();

        foreach ($this->enrols as $courseid => $enrol) {
            $this->enrols[$courseid]->availability_status = $this->get_availability_status($enrol);
        }
    }

    /**
     * Formate l'affichage des cours.
     *
     * @param array $courses Tableau de cours à formater pour l'affichage.
     * @param array $headers Entêtes des colonnes à afficher.
     *
     * @return void
     */
    private function format_courses(array &$courses, array $headers): void {
        $courses = Course::sort($courses);
        $courses = CoursesRenderer::get_data($courses, $headers, $usecache = true);

        $this->set_enrol_data($courses);
    }

    /**
     * Retourne le texte des places disponibles, le style bootstrap et si le cours est complet.
     *
     * @param stdClass $enrol Objet de la table mdl_enrol.
     *
     * @return stdClass Un objet avec les propriétés left_places_str, left_places_style et full_registration.
     */
    private function get_availability_status(stdClass $enrol): stdClass {
        $status = new stdClass();

        if (empty($enrol->customint3) === true) {
            // Les quotas sont désactivés.
            $status->left_places_str = get_string('no_seat_restrictions', 'enrol_select');
            $status->left_places_style = 'success';
            $status->full_registration = false;

            return $status;
        }

        // Les quotas sont activés.
        // TODO: refactoriser cette partie avec le script ajax/reload_column_left_places.php.
        // Récupère le nombre d'inscrits sur la liste des acceptés et sur la liste principale.
        $countmainlist = $enrol->availability_status->main ?? 0;

        // Récupère le quota de la liste principale.
        $maxmainlist = $enrol->customint1;

        // Récupère le nombre d'inscrits sur la liste complémentaire.
        $countwaitlist = $enrol->availability_status->wait ?? 0;

        // Récupère le quota de la liste complémentaire.
        $maxwaitlist = $enrol->customint2;

        if ($maxmainlist > $countmainlist && $countwaitlist === 0) {
            // Si la liste principale n'est pas complète et que la liste d'attente est vide.
            $count = $maxmainlist - $countmainlist;
            $status->left_places_str = $count . ' ' . get_string_on_list_x(
                enrol_select_plugin::MAIN,
                $count > 1 ? 'places_remaining_on_listname_X' : 'place_remaining_on_listname_X'
            );
            $status->left_places_style = 'success';
            $status->full_registration = false;

            return $status;
        }

        if ($maxwaitlist > $countwaitlist) {
            // Si la liste complémentaire n'est pas complète.
            // TODO: faire une option afin de laisser le choix entre afficher le nombre
            // de places restantes sur liste complémentaire
            // ou afficher un message générique indiquant qu'il reste des places sur liste complémentaire.
            $status->left_places_str = get_string_on_list_x(enrol_select_plugin::WAIT, 'there_are_still_places_on_listname_X');
            $status->left_places_style = 'warning';
            $status->full_registration = false;

            return $status;
        }

        // Si il ne reste plus de place.
        $status->left_places_str = get_string('no_places_available', 'enrol_select');
        $status->left_places_style = 'danger';
        $status->full_registration = true;

        return $status;
    }

    /**
     * Ajoute les propriétés de la méthode d'inscription à des objets de cours.
     *
     * @param array $courses Tableau de cours.
     *
     * @return void
     */
    private function set_enrol_data(&$courses): void {
        // Récupère tous les rôles.
        $roles = $this->get_roles();

        if (self::$cacheenrolments === null) {
            self::$cacheenrolments = enrol_select_get_user_activity_enrolments();
        }

        foreach ($courses as $key => $course) {
            if (isset($this->enrols[$course->id]) === false) {
                continue;
            }

            $enrol = $this->enrols[$course->id];
            $course->enrolid = $enrol->id;

            // Récupère tous les rôles acceptés par ce cours.
            if (isset($roles[$course->enrolid]) === false || count($roles[$course->enrolid]) === 0) {
                unset($courses[$key]);
                continue;
            }

            $course->role_options = $roles[$course->enrolid];
            $course->left_places_str = $enrol->availability_status->left_places_str;
            $course->left_places_style = $enrol->availability_status->left_places_style;
            $course->allow_enrolment = ($enrol->availability_status->full_registration === false);
            $course->enroled = isset(self::$cacheenrolments[$course->id]);
        }
    }

    /**
     * Retourn la liste des rôles disponibles pour chaque cours.
     *
     * @return array Un tableau au format roles[enrolid][] = $role.
     */
    public function get_roles(): array {
        global $DB;

        if (self::$cacheroles !== null) {
            return self::$cacheroles;
        }

        // Récupère tous les rôles acceptés par cours.
        $roles = [];

        $availableroles = enrol_select_get_custom_student_roles();

        $recordset = $DB->get_recordset('enrol_select_roles');
        foreach ($recordset as $record) {
            if (isset($availableroles[$record->roleid]) === false) {
                continue;
            }

            if (isset($roles[$record->enrolid]) === false) {
                $roles[$record->enrolid] = [];
            }

            $roles[$record->enrolid][] = $availableroles[$record->roleid];
        }
        $recordset->close();

        self::$cacheroles = $roles;

        return $roles;
    }
}
