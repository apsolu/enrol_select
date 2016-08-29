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
 * Self enrolment plugin settings and presets.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'moodle/category:manage',
    'moodle/course:create',
);

// Needs this condition or there is error on login page.
if ($hassiteconfig or has_any_capability($capabilities, context_system::instance())) {
    if (empty($ADMIN->locate('apsolu'))) {
        $ADMIN->add('root', new admin_category('apsolu', 'Gestion du SIUAPS'), 'users');
    }

    $ADMIN->add('apsolu', new admin_category('enrol_select', 'Gestion des inscriptions'));

    $url = new moodle_url('/enrol/select/administration.php?tab=colleges');
    $ADMIN->add('enrol_select', new admin_externalpage('enrol_select_colleges', get_string('colleges', 'enrol_select'), $url));
}
