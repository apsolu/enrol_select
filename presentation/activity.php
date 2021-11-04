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
 * Redirige la page vers la nouvelle adresse.
 *
 * @package    enrol_select
 * @copyright  2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');

require_course_login($site);

$id = optional_param('id', null, PARAM_INT);

$redirection = $CFG->wwwroot.'/local/apsolu/presentation/activity.php';
if ($id !== null) {
    $redirection .= '?id='.$id;
}

header('Location: '.$redirection, $repalce = true, $httpresponsecode = 301);
exit();
