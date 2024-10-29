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
 * Définition des observateurs.
 *
 * @package   enrol_select
 * @copyright 2022 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Gère la suppression des cohortes depuis l'interface Administration du site > Utilisateurs > Comptes > Cohortes.
    [
        'eventname'   => '\core\event\cohort_deleted',
        'callback'    => '\enrol_select\observer\cohort::deleted',
        'includefile' => null,
        'internal'    => true,
        'priority'    => 9999,
    ],
    // Gère la suppression des méthodes d'inscription.
    [
        'eventname'   => '\core\event\enrol_instance_deleted',
        'callback'    => '\enrol_select\observer\enrol_instance::deleted',
        'includefile' => null,
        'internal'    => true,
        'priority'    => 9999,
    ],
    // Gère la suppression des rôles depuis l'interface Administration du site > Utilisateurs > Permissions > Définition des roles.
    [
        'eventname'   => '\core\event\role_deleted',
        'callback'    => '\enrol_select\observer\role::deleted',
        'includefile' => null,
        'internal'    => true,
        'priority'    => 9999,
    ],
    // Gère les désinscriptions sur le cours FFSU.
    [
        'eventname'   => '\core\event\user_enrolment_deleted',
        'callback'    => '\enrol_select\observer\user_enrolment::deleted',
        'includefile' => null,
        'internal'    => true,
        'priority'    => 9999,
    ],
];
