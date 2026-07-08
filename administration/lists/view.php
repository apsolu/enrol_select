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
 * Page d'affichage du paramétrage des libellés des listes d'inscrits.
 *
 * @package    enrol_select
 * @copyright  2026 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use stdClass;
use moodle_url;
use html_writer;

require_once($CFG->dirroot . '/enrol/select/administration/lists/edit_form.php');
require_once($CFG->dirroot . '/enrol/select/lib.php');

// Edition d'une liste.
$editstate = optional_param('editstate', null, PARAM_INT);
$tab = required_param('tab', PARAM_ALPHANUM);
$listformats = enrol_select_plugin::get_listformats();
// Edition des chaînes de caractère pour l'un des statuts d'inscription.
if (isset($editstate) && enrol_select_plugin::get_state_from_code($editstate) != false) {
    $statusname = get_enrol_list_fieldvalue($editstate, 'status', true);

    $returnurl = new moodle_url($PAGE->url->out(false, ['tab' => $tab]));

    $formvalues = []; // Valeurs existantes en config pour remplir les champs du formulaire (si existante).
    $defaults = []; // Valeurs par défaut (fichier de langue).

    // On récupère les valeurs des différents formats de chaîne de caractère pour la liste à modifier.
    foreach ($listformats as $listformat) {
        // Valeurs pour les champs du formulaire.
        $formvalues[$listformat] = get_enrol_list_fieldvalue(
            $editstate,
            $listformat,
            false,
            $listformat == 'listname' || $listformat == 'description'
        );

        // Valeurs par défaut.
        $defaults[$listformat] = get_enrol_list_fieldvalue(
            $editstate,
            $listformat,
            true,
            $listformat == 'listname' || $listformat == 'description'
        );
    }

    $customdata = [$formvalues, $defaults, $editstate];
    $mform = new enrol_select_lists_form($PAGE->url->out(false, ['tab' => $tab, 'editstate' => $editstate]), $customdata);

    $title = get_string('edit_list_title', 'enrol_select', $statusname);

    echo $OUTPUT->heading($title);

    if ($mform->is_submitted()) {
        // Bouton 'Annuler'.
        if ($mform->is_cancelled()) {
            redirect($returnurl);
        }
        // Validation du formulaire.
        if ($data = $mform->get_data()) {
            // Effectue les modifications dans la configuration du plugin.
            $haschanged = enrol_select_plugin::save_state_custom_strings($editstate, (array) $data, $formvalues, $defaults);

            // Retour à la vue de toutes les listes, avec une notification si la liste a été modifiée.
            if ($haschanged) {
                $message = get_string('savingvalues_ok', 'enrol_select', $statusname);
                redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                $message = get_string('savingvalues_notok', 'enrol_select', $statusname);
                redirect($returnurl, $message, $delay = null, \core\output\notification::NOTIFY_WARNING);
            }
        }
    }

    $mform->display();
} else {
    // Affichage de la liste des statuts d'inscription dans un tableau.

    $lists = [];
    foreach (enrol_select_plugin::$states as $stateid => $state) {
        // Valeurs personnalisées si existantes (config), valeurs par défaut sinon (fichier de langue).
        $list = [];

        foreach ($listformats as $listformat) {
            $list[$listformat] = get_enrol_list_fieldvalue(
                $stateid,
                $listformat,
                false,
                $listformat == 'listname' || $listformat == 'description'
            );
        }
        $list['editurl'] = new moodle_url($PAGE->url->out(false, ['tab' => $tab, 'editstate' => $stateid]));
        $lists[] = $list;
    }

    $data = new stdClass();
    $data->lists = $lists;

    echo $OUTPUT->heading(get_string('lists', 'enrol_select'));
    echo $OUTPUT->render_from_template('enrol_select/configuration_lists', $data);
}
