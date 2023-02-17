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

$observers = array(
    // Gère la suppression des cohortes depuis l'interface Administration du site > Utilisateurs > Comptes > Cohortes.
    array(
        'eventname'   => '\core\event\cohort_deleted',
        'callback'    => '\enrol_select\observer\cohort::deleted',
        'includefile' => null,
        'internal'    => true,
        'priority'    => 9999,
    ),
);
