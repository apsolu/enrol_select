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
 * Script ajax permettant de recharger les blocs des voeux restants sur la page d'inscription.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/enrol/select/blocklib.php');
require_once($CFG->dirroot.'/enrol/select/locallib.php');

// Set permissions.
require_login();

$context = context_user::instance($USER->id);

$PAGE->set_context($context);

// Generate block content.
$block = enrol_select_get_remaining_choices_block();
echo $block->content;
