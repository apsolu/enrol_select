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
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;

define('AJAX_SCRIPT', true);

require(__DIR__.'/../../../config.php');
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
    $sql = "SELECT userid FROM {user_enrolments} WHERE enrolid = ? AND status IN (0, 2)";
    $mainlistenrolements = $DB->get_records_sql($sql, array($enrolid));
    $countmainlist = count($mainlistenrolements);
    $maxmainlist = $enrol->customint1;
    $usermainlist = isset($mainlistenrolements[$USER->id]);
    $leftmainliststr = ($maxmainlist - $countmainlist).' places restantes sur liste principale';

    $waitlistenrolements = $DB->get_records('user_enrolments', array('enrolid' => $enrol->id, 'status' => 3), '', 'userid');
    $countwaitlist = count($waitlistenrolements);
    $maxwaitlist = $enrol->customint2;
    $userwaitlist = isset($waitlistenrolements[$USER->id]);
    $leftwaitliststr = ($maxwaitlist - $countwaitlist).' places restantes sur liste complémentaire';

    $usernolist = !$usermainlist && !$userwaitlist;
    $fullregistration = ($countmainlist >= $maxmainlist) && ($countwaitlist >= $maxwaitlist);

    if ($maxmainlist > $countmainlist) {
        $leftplacesstr = ($maxmainlist - $countmainlist).' places restantes sur liste principale';
        $leftplacesstyle = 'success';
    } else if ($maxwaitlist > $countwaitlist) {
        $leftplacesstr = ($maxwaitlist - $countwaitlist).' places restantes sur liste complémentaire';
        $leftplacesstyle = 'warning';
    } else {
        $leftplacesstr = 'Aucune place disponible';
        $leftplacesstyle = 'danger';
    }
} else {
    // Les quotas sont désactivés.
    $leftplacesstr = 'Aucune restriction de places';
    $leftplacesstyle = 'success';
}

echo '<td id="apsolu-select-left-places-'.$enrolid.'-ajax" class="'.$leftplacesstyle.'">'.$leftplacesstr.'</td>';
