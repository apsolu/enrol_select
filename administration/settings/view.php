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
 * Page d'affichage du paramétrage par défaut des méthodes d'inscription.
 *
 * @package    enrol_select
 * @copyright  2021 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/enrol/select/administration/settings/edit_form.php');

// Build form.
$attributes = [
    'default_cohorts', // Cohortes par défaut.
    'default_roles', // Rôles par défaut.
    'default_cards', // Paiements par défaut.
    'default_customint1', // Maximum de place sur la liste principale.
    'default_customint2', // Maximum de place sur la liste d'attente.
    'default_customint3', // Activer les quotas.
    'default_customdec1', // Délai de paiement.
    'default_customchar1', // Type de calendrier.
    'default_customchar2', // Remontée de liste automatique.
    'default_customchar3', // Liste sur laquelle inscrire les étudiants.
    'default_customtext1', // Message de bienvenue pour les inscrits sur liste des acceptés.
    'default_customtext2', // Message de bienvenue pour les inscrits sur liste principale.
    'default_customtext3', // Message de bienvenue pour les inscrits sur liste complémentaire.
    ];

$config = get_config('enrol_select');

$defaults = new stdClass();
foreach ($attributes as $attribute) {
    $defaults->{$attribute} = '';

    if (isset($config->{$attribute}) === false) {
        continue;
    }

    $defaults->{$attribute} = $config->{$attribute};
}

for ($i = 1; $i < 4; $i++) {
    // Positionne les attributs pour la gestion des messages de bienvenue.
    $customtext = sprintf('default_customtext%d', $i);
    $customtextswitch = sprintf('default_customtext%dswitch', $i);
    $defaults->{$customtextswitch} = (int) !empty($defaults->{$customtext});
    $defaults->{$customtext} = ['text' => $defaults->{$customtext}, 'format' => FORMAT_HTML];
}

$cohorts = $DB->get_records('cohort', $conditions = null, $sort = 'name');
$roles = enrol_select_get_custom_student_roles();
$cards = $DB->get_records('apsolu_payments_cards', $conditions = null, $sort = 'name');
$calendars = [(object) ['id' => 0, 'name' => get_string('none')]];
$calendars += $DB->get_records('apsolu_calendars', $conditions = null, $sort = 'name');

$customdata = [$defaults, $calendars, $cohorts, $roles, $cards];

$mform = new enrol_select_default_settings_form(null, $customdata);

echo $OUTPUT->heading(get_string('default_settings', 'enrol_select'));

if ($data = $mform->get_data()) {
    if (empty($data->default_customtext1switch) === true) {
        $data->default_customtext1 = ['text' => ''];
    }

    if (empty($data->default_customtext2switch) === true) {
        $data->default_customtext2 = ['text' => ''];
    }

    if (empty($data->default_customtext3switch) === true) {
        $data->default_customtext3 = ['text' => ''];
    }

    foreach ($attributes as $attribute) {
        if (isset($data->{$attribute}['text']) === true) {
            $oldvalue = $defaults->{$attribute}['text'];
            $newvalue = $data->{$attribute}['text'];
        } else if (is_array($data->{$attribute}) === true) {
            $oldvalue = $defaults->{$attribute};
            $newvalue = implode(',', $data->{$attribute});
        } else {
            $oldvalue = $defaults->{$attribute};
            $newvalue = $data->{$attribute};
        }

        if ($newvalue == $oldvalue) {
            continue;
        }

        add_to_config_log($attribute, $oldvalue, $newvalue, 'enrol_select');
        set_config($attribute, $newvalue, 'enrol_select');
    }

    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

$mform->display();
