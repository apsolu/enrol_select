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
 * Contrôleur pour les pages d'administration du module enrol_select.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$tab = optional_param('tab', 'default_settings', PARAM_TEXT);
$action = optional_param('action', 'view', PARAM_ALPHA);

$tabslist = [];
$tabslist['default_settings'] = 'settings';
$tabslist['batch_settings'] = 'batch_settings';
$tabslist['colleges'] = 'colleges';
$tabslist['renewals'] = 'renewals';
$tabslist['enrolments_overview'] = 'enrolments_overview';
$tabslist['enrolment_methods_overview'] = 'enrolment_methods_overview';

$tabsbar = [];
foreach ($tabslist as $stringid => $tabname) {
    $url = new moodle_url('/enrol/select/administration.php', ['tab' => $tabname]);
    $tabsbar[] = new tabobject($tabname, $url, get_string($stringid, 'enrol_select'));
}

if (!in_array($tab, $tabslist, true)) {
    $tab = $tabslist['default_settings'];
}

admin_externalpage_setup('enrol_select_' . $tab);

// Capture le contenu principal de la page.
ob_start();
require_once(__DIR__ . '/administration/' . $tab . '/index.php');
$content = ob_get_contents();
ob_end_clean();

// Affiche la page.
echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabsbar, $tab);
echo $content;
echo $OUTPUT->footer();
