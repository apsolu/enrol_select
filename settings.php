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
 * Ajoute des entrées dans le menu d'administration du site.
 *
 * @package   enrol_select
 * @copyright 2020 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'moodle/category:manage',
    'moodle/course:create',
];

if ($hassiteconfig || has_any_capability($capabilities, context_system::instance())) {
    // Ajoute un noeud Apsolu au menu d'administration.
    if (empty($ADMIN->locate('apsolu')) === true) {
        // Crée le noeud.
        $apsoluroot = new admin_category('apsolu', get_string('settings_root', 'local_apsolu'));
        // Tri les enfants par ordre alphabétique.
        $apsoluroot->set_sorting($sort = true);
        // Place le noeud Apsolu avant le noeud Utilisateurs de Moodle.
        $ADMIN->add('root', $apsoluroot, 'users');
    }

    // Le fichier enrol/select/settings.php est lu automatiquement pour les administrateurs (uniquement).
    // Il y a donc un require() dans le fichier local/apsolu/settings.php pour les gestionnaires.
    // Ce if évite d'afficher 2x le menu pour les administrateurs.
    if (empty($ADMIN->locate('enrol_select')) === true) {
        // Inscriptions.
        $ADMIN->add('apsolu', new admin_category('enrol_select', get_string('enrolments', 'enrol')));

        // Inscriptions > Paramètrage par défaut.
        $label = get_string('default_settings', 'enrol_select');
        $url = new moodle_url('/enrol/select/administration.php?tab=settings');
        $page = new admin_externalpage('enrol_select_settings', $label, $url, $capabilities);
        $ADMIN->add('enrol_select', $page);

        // Inscriptions > Paramètrage par lots.
        $label = get_string('batch_settings', 'enrol_select');
        $url = new moodle_url('/enrol/select/administration.php?tab=batch_settings');
        $page = new admin_externalpage('enrol_select_batch_settings', $label, $url, $capabilities);
        $ADMIN->add('enrol_select', $page);

        // Inscriptions > Population.
        $label = get_string('colleges', 'enrol_select');
        $url = new moodle_url('/enrol/select/administration.php?tab=colleges');
        $page = new admin_externalpage('enrol_select_colleges', $label, $url, $capabilities);
        $ADMIN->add('enrol_select', $page);

        // Inscriptions > Réinscription en masse.
        $label = get_string('renewals', 'enrol_select');
        $url = new moodle_url('/enrol/select/administration.php?tab=renewals');
        $page = new admin_externalpage('enrol_select_renewals', $label, $url, $capabilities);
        $ADMIN->add('enrol_select', $page);

        // Inscriptions > Vue d'ensemble des inscriptions.
        $label = get_string('enrolments_overview', 'enrol_select');
        $url = new moodle_url('/enrol/select/administration.php?tab=enrolments_overview');
        $page = new admin_externalpage('enrol_select_enrolments_overview', $label, $url, $capabilities);
        $ADMIN->add('enrol_select', $page);

        // Inscriptions > Vue d'ensemble des méthodes d'inscription.
        $label = get_string('enrolment_methods_overview', 'enrol_select');
        $url = new moodle_url('/enrol/select/administration.php?tab=enrolment_methods_overview');
        $page = new admin_externalpage('enrol_select_enrolment_methods_overview', $label, $url, $capabilities);
        $ADMIN->add('enrol_select', $page);
    }
}
