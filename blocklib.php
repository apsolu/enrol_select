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

namespace UniversiteRennes2\Apsolu;

function get_remaining_choices_block() {
    global $OUTPUT;

    $roles = get_potential_user_roles();

    $overviewremainingchoicesdata = new \stdClass();
    $overviewremainingchoicesdata->choices = array_values(get_user_colleges($userid = null, $count = true));
    $overviewremainingchoicesdata->count_choices = count($overviewremainingchoicesdata->choices);
    $overviewremainingchoicesdata->summary = '<div id="apsolu-rules-summary" class="table-responsive">'.
            '<table class="table table-bordered">'.
            '<thead>'.
                '<tr>'.
                    '<th>En tant que...</th>'.
                    '<th>nombre de voeux maximums</th>'.
                    '<th>nombre d\'inscriptions minimums</th>'.
                    '<th>nombre d\'inscriptions maximums</th>'.
                    '<th>tarif</th>'.
                '</tr>'.
            '</thead>'.
            '<tbody>';

    foreach ($overviewremainingchoicesdata->choices as $choice) {
        if ($choice->count >= $choice->maxwish) {
            $choice->str = '<p class="alert-success">Vous avez atteint le maximum de voeux avec le statut <b>'.$roles[$choice->roleid]->name.'</b>.</p>';
        } else {
            // Pluriel.
            if ($choice->maxregister > 1) {
                $activitiesstr = 'activités';
            } else {
                $activitiesstr = 'activité';
            }

            if ($choice->minregister == 0) {
                $choice->str = '<p class="alert-info">Vous pouvez choisir '.$choice->maxregister.' '.$activitiesstr.' avec le statut <b>'.$roles[$choice->roleid]->name.'</b>.';
            } else {
                if ($choice->count < $choice->minregister) {
                    $style = 'alert-danger';
                } else {
                    $style = 'alert-success';
                }

                if ($choice->minregister === $choice->maxregister) {
                    $choice->str = '<p class="'.$style.'">Vous pouvez choisir <b>'.$choice->minregister.' '.$activitiesstr.'</b> avec le statut <b>'.$roles[$choice->roleid]->name.'</b>.';
                } else {
                    $choice->str = '<p class="'.$style.'">Vous pouvez choisir <b>entre '.$choice->minregister.' et '.$choice->maxregister.' '.$activitiesstr.'</b> avec le statut <b>'.$roles[$choice->roleid]->name.'</b>.';
                }
            }

            $countchoices = $choice->maxwish - $choice->count;
            if ($countchoices > 1) {
                $choicestr = 'voeux';
            } else {
                $choicestr = 'voeu';
            }
            $choice->str .= '<br />Il vous reste encore <b>'.$countchoices.' '.$choicestr.'</b>.</p>';
        }

        $overviewremainingchoicesdata->summary .= '<tr>'.
                '<td>'.$choice->name.'</td>'.
                '<td>'.$choice->maxwish.'</td>'.
                '<td>'.$choice->minregister.'</td>'.
                '<td>'.$choice->maxregister.'</td>'.
                '<td>'.number_format($choice->userprice, 2, ',', ' ').'&nbsp;€</td>'.
            '</tr>';
    }
    $overviewremainingchoicesdata->summary .= '</tbody></table></div>';

    $block = new \block_contents();
    $block->title = 'Choix restants';
    $block->attributes['class'] = 'block block_overview_remaining_choices';
    $block->content = $OUTPUT->render_from_template('enrol_select/overview_remaining_choices', $overviewremainingchoicesdata);

    return $block;
}

function get_enrolments_block() {
    global $DB, $CFG, $OUTPUT;

    $roles = role_fix_names($DB->get_records('role'));

    $instance = new \enrol_select_plugin();

    $overviewenrolmentsdata = new \stdClass();
    $overviewenrolmentsdata->wwwroot = $CFG->wwwroot;
    $overviewenrolmentsdata->activity_enrolments = array();
    foreach (get_real_user_activity_enrolments() as $enrolment) {
        if ($enrolment->status === \enrol_select_plugin::DELETED) {
            continue;
        }

        $enrolment->role = $roles[$enrolment->roleid]->localname;

        $enrol = $DB->get_record('enrol', array('id' => $enrolment->enrolid));
        if ($enrol) {
            $enrolment->is_enrol_period_active = $instance->is_enrol_period_active($enrol);
        } else {
            $enrolment->is_enrol_period_active = false;
        }

        $overviewenrolmentsdata->activity_enrolments[] = $enrolment;
    }
    $overviewenrolmentsdata->count_activity_enrolments = count($overviewenrolmentsdata->activity_enrolments);
    $overviewenrolmentsdata->complement_enrolments = array_values(get_user_complement_enrolments());
    $overviewenrolmentsdata->count_complement_enrolments = count($overviewenrolmentsdata->complement_enrolments);

    $block = new \block_contents();
    $block->title = 'Je souhaite m\'inscrire à...';
    $block->attributes['class'] = 'block block_overview_enrolments';
    $block->content = $OUTPUT->render_from_template('enrol_select/overview_enrolments', $overviewenrolmentsdata);

    return $block;
}

function get_filters_block($courses) {
    global $CFG, $OUTPUT;

    $overviewfiltersdata = new \stdClass();
    $overviewfiltersdata->form = (object) array('action' => $CFG->wwwroot.'/enrol/select/overview.php');
    $overviewfiltersdata->filters = generate_filters($courses);

    $block = new \block_contents();
    $block->title = get_string('filters', 'admin');
    $block->attributes['class'] = 'block block_book_toc';
    $block->content = $OUTPUT->render_from_template('enrol_select/overview_filters', $overviewfiltersdata);

    return $block;
}
