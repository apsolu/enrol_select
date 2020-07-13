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

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$tab = optional_param('tab', 'register_types', PARAM_TEXT);
$action = optional_param('action', 'view', PARAM_ALPHA);

$tabslist = array('colleges', 'renewals', 'overview');

$tabsbar = array();
foreach ($tabslist as $tabname) {
    $url = new moodle_url('/enrol/select/administration.php', array('tab' => $tabname));
    $tabsbar[] = new tabobject($tabname, $url, get_string($tabname, 'enrol_select'));
}

if (!in_array($tab, $tabslist, true)) {
    $tab = $tabslist[0];
}

admin_externalpage_setup('enrol_select_'.$tab);

echo $OUTPUT->header();

echo $OUTPUT->tabtree($tabsbar, $tab);

require_once(__DIR__.'/administration/'.$tab.'/index.php');

echo $OUTPUT->footer();
