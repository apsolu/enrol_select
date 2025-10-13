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
 * Page d'affichage du paramétrage par lots des méthodes d'inscription.
 *
 * @package    enrol_select
 * @copyright  2025 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_select\event\batch_enrol_instance_updated;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/enrol/select/administration/batch_settings/edit_form.php');

// Build form.
$calendars = $DB->get_records('apsolu_calendars', $conditions = null, $sort = 'name');

$customdata = [$calendars];
$mform = new enrol_select_batch_settings_form(null, $customdata);

echo $OUTPUT->heading(get_string('batch_settings', 'enrol_select'));

if ($data = $mform->get_data()) {
    if (isset($data->submitbutton) === true) {
        // Modification des méthodes d'inscription par lots.
        $needupdate = false;
        foreach ((array) $data as $key => $value) {
            if (str_ends_with($key, 'switch') === false) {
                continue;
            }
            $needupdate = true;
            break;
        }

        $count = 0;
        if ($needupdate === true) {
            foreach ($DB->get_records('enrol', ['customchar1' => $data->calendar]) as $enrol) {
                for ($i = 1; $i < 4; $i++) {
                    $textswitch = sprintf('batch_customtext%sswitch', $i);
                    $text = sprintf('batch_customtext%s', $i);
                    $attribute = sprintf('customtext%s', $i);

                    if (isset($data->{$textswitch}, $data->{$text}['text']) === true) {
                        $enrol->{$attribute} = $data->{$text}['text'];
                    }
                }

                $DB->update_record('enrol', $enrol);
                $count++;
            }

            // Ajoute un évènement.
            $event = batch_enrol_instance_updated::create([
                'other' => ['criteria' => ['calendar' => $data->calendar]],
                'context' => context_system::instance(),
            ]);
            $event->trigger();
        }

        echo $OUTPUT->notification(get_string('x_enrolment_methods_changed', 'enrol_select', $count), 'notifysuccess');
    } else {
        // Gestion de l'aperçu.
        $sql = "SELECT e.id, e.name, e.courseid, c.fullname
                  FROM {enrol} e
                  JOIN {course} c ON c.id = e.courseid
                 WHERE e.customchar1 = :calendar
                   AND e.enrol = 'select'
              ORDER BY c.fullname, e.name";
        $enrols = [];
        foreach ($DB->get_records_sql($sql, ['calendar' => $data->calendar]) as $enrol) {
            if (isset($enrols[$enrol->courseid]) === false) {
                $enrols[$enrol->courseid] = new stdClass();
                $enrols[$enrol->courseid]->fullname = $enrol->fullname;
                $enrols[$enrol->courseid]->enrols = [];
            }

            if (empty($enrol->name) === true) {
                $enrols[$enrol->courseid]->enrols[] = get_string('pluginname', 'enrol_select');
            } else {
                $enrols[$enrol->courseid]->enrols[] = $enrol->name;
            }
        }

        $courses = [];
        foreach ($enrols as $enrol) {
            $summary = html_writer::tag('summary', $enrol->fullname);
            $list = html_writer::alist($enrol->enrols, $attributes = [], $tag = 'ul');

            $attributes = [];
            if (isset($enrol->enrols[1]) === true) {
                $attributes['open'] = 1;
            }
            $content = html_writer::tag('details', $summary . $list, $attributes);

            $courses[] = $content;
        }

        if (isset($courses[0]) === false) {
            $text = get_string('no_enrolment_method_available_with_these_selection_criteria', 'enrol_select');
            echo  html_writer::tag('div', $text, ['class' => 'alert alert-warning']);
        } else {
            $text = get_string('list_of_courses_for_which_the_enrolment_method_will_be_changed', 'enrol_select');
            $content = html_writer::tag('p', $text);
            $content .= html_writer::alist($courses, $attributes = ['class' => 'list-unstyled'], $tag = 'ul');
            echo  html_writer::tag('div', $content, ['class' => 'alert alert-info']);
        }
    }
}

$mform->display();
