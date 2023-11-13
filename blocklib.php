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
 * Fonctions pour le module enrol_select.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Retourne le rendu HTML du bloc comptant le nombre de choix restant sur la page des inscriptions.
 *
 * @return string Retourne le rendu HTML du bloc.
 */
function enrol_select_get_remaining_choices_block() {
    global $OUTPUT;

    $roles = enrol_select_get_potential_user_roles();
    $colleges = enrol_select_get_user_colleges($userid = null);

    $overviewremainingchoicesdata = new stdClass();
    $overviewremainingchoicesdata->choices = enrol_select_get_sum_user_choices($userid = null, $count = true);
    $overviewremainingchoicesdata->count_choices = 0;
    $overviewremainingchoicesdata->summary = '<div id="apsolu-rules-summary" class="table-responsive">'.
            '<table class="table table-bordered">'.
            '<thead>'.
                '<tr>'.
                    '<th>En tant que...</th>'.
                    '<th>nombre de voeux maximums</th>'.
                    '<th>nombre d\'inscriptions minimums</th>'.
                    '<th>nombre d\'inscriptions maximums</th>'.
                '</tr>'.
            '</thead>'.
            '<tbody>';
    foreach ($colleges as $college) {
        $overviewremainingchoicesdata->summary .= '<tr>'.
                '<td>'.$college->name.'</td>'.
                '<td>'.$college->maxwish.'</td>'.
                '<td>'.$college->minregister.'</td>'.
                '<td>'.$college->maxregister.'</td>'.
                '</tr>';
    }
    $overviewremainingchoicesdata->summary .= '</tbody></table></div>';

    foreach ($overviewremainingchoicesdata->choices as $index => $choice) {
        if ($choice->maxwish == 0) {
            unset($overviewremainingchoicesdata->choices[$index]);
            continue;
        }

        $choice->description = '';
        if ($choice->count >= $choice->maxwish) {
            $choice->description = '<p class="alert-success">Vous avez atteint le maximum de voeux avec le statut <b>'.
                $roles[$choice->roleid]->name.'</b>.</p>';
        } else {
            // Pluriel.
            if ($choice->maxregister > 1) {
                $activitiesstr = 'activités';
            } else {
                $activitiesstr = 'activité';
            }

            if ($choice->minregister == 0) {
                $choice->description = '<p class="alert-info">Vous pouvez choisir '.$choice->maxregister.' '.
                    $activitiesstr.' avec le statut <b>'.$roles[$choice->roleid]->name.'</b>.';
            } else {
                if ($choice->count < $choice->minregister) {
                    $style = 'alert-danger';
                } else {
                    $style = 'alert-success';
                }

                if ($choice->minregister === $choice->maxregister) {
                    $choice->description = '<p class="'.$style.'">Vous pouvez choisir <b>'.$choice->minregister.' '.
                        $activitiesstr.'</b> avec le statut <b>'.$roles[$choice->roleid]->name.'</b>.';
                } else {
                    $choice->description = '<p class="'.$style.'">Vous pouvez choisir <b>entre '.$choice->minregister.' et '.
                        $choice->maxregister.' '.$activitiesstr.'</b> avec le statut <b>'.$roles[$choice->roleid]->name.'</b>.';
                }
            }

            $countchoices = $choice->maxwish - $choice->count;
            if ($countchoices > 1) {
                $choicestr = 'voeux';
            } else {
                $choicestr = 'voeu';
            }
            $choice->description .= '<br />Il vous reste encore <b>'.$countchoices.' '.$choicestr.'</b>.</p>';
        }

    }

    // Réindexe les valeurs pour mustache.
    $overviewremainingchoicesdata->choices = array_values($overviewremainingchoicesdata->choices);
    $overviewremainingchoicesdata->count_choices = count($overviewremainingchoicesdata->choices);

    $block = new block_contents();
    $block->title = 'Choix restants';
    $block->attributes['class'] = 'block block_overview_remaining_choices';
    $block->content = $OUTPUT->render_from_template('enrol_select/overview_remaining_choices', $overviewremainingchoicesdata);

    return $block;
}

/**
 * Retourne le rendu HTML du bloc affichant les inscriptions de l'étudiant sur la page des inscriptions.
 *
 * @return string Retourne le rendu HTML du bloc.
 */
function enrol_select_get_enrolments_block() {
    global $DB, $CFG, $OUTPUT;

    require_once(__DIR__.'/lib.php');

    $roles = role_fix_names($DB->get_records('role'));

    $instance = new enrol_select_plugin();

    $overviewenrolmentsdata = new stdClass();
    $overviewenrolmentsdata->wwwroot = $CFG->wwwroot;
    $overviewenrolmentsdata->activity_enrolments = [];
    foreach (enrol_select_get_real_user_activity_enrolments() as $enrolment) {
        if ($enrolment->status === enrol_select_plugin::DELETED) {
            continue;
        }

        $enrolment->role = $roles[$enrolment->roleid]->localname;

        $enrol = $DB->get_record('enrol', ['id' => $enrolment->enrolid]);
        if ($enrol) {
            $enrolment->is_enrol_period_active = $instance->is_enrol_period_active($enrol);
        } else {
            $enrolment->is_enrol_period_active = false;
        }

        $overviewenrolmentsdata->activity_enrolments[] = $enrolment;
    }
    $overviewenrolmentsdata->count_activity_enrolments = count($overviewenrolmentsdata->activity_enrolments);
    $overviewenrolmentsdata->complement_enrolments = array_values(enrol_select_get_user_complement_enrolments());
    $overviewenrolmentsdata->count_complement_enrolments = count($overviewenrolmentsdata->complement_enrolments);

    $block = new block_contents();
    $block->title = 'Je souhaite m\'inscrire à...';
    $block->attributes['class'] = 'block block_overview_enrolments';
    $block->content = $OUTPUT->render_from_template('enrol_select/overview_enrolments', $overviewenrolmentsdata);

    return $block;
}

/**
 * Retourne le rendu HTML du bloc permettant de filter les activités sur la page des inscriptions.
 *
 * @param array $courses Liste des cours affichés sur la page des inscriptions.
 *
 * @return string Retourne le rendu HTML du bloc.
 */
function enrol_select_get_filters_block($courses) {
    global $CFG, $OUTPUT;

    $filters = enrol_select_generate_filters($courses);

    $overviewfiltersdata = new stdClass();
    $overviewfiltersdata->form = (object) ['action' => $CFG->wwwroot.'/enrol/select/overview.php'];
    $overviewfiltersdata->filters = array_values($filters);

    $block = new block_contents();
    $block->title = get_string('filters', 'admin');
    $block->attributes['class'] = 'block block_book_toc';
    $block->content = $OUTPUT->render_from_template('enrol_select/overview_filters', $overviewfiltersdata);
    $block->more_than_one_site = isset($filters['city']);

    return $block;
}
