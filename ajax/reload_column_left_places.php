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
 * Script ajax permettant de recharger la colonne des places restantes sur la page d'inscription.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;

define('AJAX_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/enrol/select/lib.php');
require_once($CFG->dirroot.'/enrol/select/locallib.php');

$enrolid = required_param('enrolid', PARAM_INT);

// Set permissions.
require_login();

$context = context_user::instance($USER->id);

$PAGE->set_context($context);

// Generate column content.
$enrol = $DB->get_record('enrol', array('id' => $enrolid), '*', MUST_EXIST);

if ($enrol->customint3 == 1) {
    // Les quotas sont activés.
    // TODO: refactoriser cette partie avec la fonction enrol_select_get_potential_user_activities() du script locallib.php.
    // Calcule le nombre d'inscrits sur la liste des acceptés et sur la liste principale.
    $sql = "SELECT COUNT(userid) FROM {user_enrolments} WHERE enrolid = :enrolid AND status IN (:accepted, :main)";
    $conditions = array('enrolid' => $enrol->id, 'accepted' => enrol_select_plugin::ACCEPTED, 'main' => enrol_select_plugin::MAIN);
    $countmainlist = $DB->count_records_sql($sql, $conditions);

    // Récupère le quota de la liste principale.
    $maxmainlist = $enrol->customint1;

    // Calcule le nombre d'inscrits sur la liste complémentaire.
    $conditions = array('enrolid' => $enrol->id, 'status' => enrol_select_plugin::WAIT);
    $countwaitlist = $DB->count_records('user_enrolments', $conditions);

    // Récupère le quota de la liste complémentaire.
    $maxwaitlist = $enrol->customint2;

    if ($maxmainlist > $countmainlist && $countwaitlist === 0) {
        // Si la liste principale n'est pas complète et que la liste d'attente est vide.
        $count = $maxmainlist - $countmainlist;
        if ($count > 1) {
            $leftplacesstr = get_string('x_places_remaining_on_the_main_list', 'enrol_select', $count);
        } else {
            $leftplacesstr = get_string('x_place_remaining_on_the_main_list', 'enrol_select', $count);
        }
        $leftplacesstyle = 'success';
    } else if ($maxwaitlist > $countwaitlist) {
        // Si la liste complémentaire n'est pas complète.
        // TODO: faire une option afin de laisser le choix entre afficher le nombre
        // de places restantes ($maxwaitlist - $countwaitlist) sur liste complémentaire
        // ou afficher un message générique indiquant qu'il reste des places sur liste complémentaire.
        $leftplacesstr = get_string('there_are_still_places_on_the_wait_list', 'enrol_select');
        $leftplacesstyle = 'warning';
    } else {
        // Si il ne reste plus de place.
        $leftplacesstr = get_string('no_places_available', 'enrol_select');
        $leftplacesstyle = 'danger';
    }
} else {
    // Les quotas sont désactivés.
    $leftplacesstr = get_string('no_seat_restrictions', 'enrol_select');
    $leftplacesstyle = 'success';
}

echo '<td id="apsolu-select-left-places-'.$enrolid.'-ajax" class="table-'.$leftplacesstyle.'">'.$leftplacesstr.'</td>';
