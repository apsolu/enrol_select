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
 * Page pour configurer une instance du module enrol_select.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_apsolu\core\course;

require('../../config.php');
require_once('edit_form.php');
require_once('locallib.php');
require_once($CFG->libdir . '/gradelib.php');

$courseid = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course, $autologinguest = false);
require_capability('enrol/select:config', $context);

$PAGE->set_url('/enrol/select/edit.php', ['courseid' => $course->id, 'id' => $instanceid]);
$PAGE->set_pagelayout('admin');

$arguments = [get_string('warning_changing_calendar_may_result_in_loss_of_data', 'enrol_select')];
$PAGE->requires->js_call_amd('enrol_select/edit_calendar', 'initialise', $arguments);

$return = new moodle_url('/enrol/instances.php', ['id' => $course->id]);
if (!enrol_is_enabled('select')) {
    redirect($return);
}

$plugin = enrol_get_plugin('select');

if ($instanceid) {
    $conditions = ['courseid' => $course->id, 'enrol' => 'select', 'id' => $instanceid];
    $instance = $DB->get_record('enrol', $conditions, '*', MUST_EXIST);
    $instance->customdec1 = intval($instance->customdec1);
    $instance->cohorts = array_keys($DB->get_records('enrol_select_cohorts', ['enrolid' => $instance->id], '', 'cohortid'));
    $instance->roles = array_keys($DB->get_records('enrol_select_roles', ['enrolid' => $instance->id], '', 'roleid'));
    $instance->cards = array_keys($DB->get_records('enrol_select_cards', ['enrolid' => $instance->id], '', 'cardid'));
} else {
    require_capability('moodle/course:enrolconfig', $context);
    // No instance yet, we have to add new instance.
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', ['id' => $course->id]));

    $instance = (object) $plugin->get_instance_defaults();
    $instance->id       = null;
    $instance->courseid = $course->id;
    // Do not use default for automatically created instances here.
    $instance->status   = ENROL_INSTANCE_ENABLED;
}

$cohorts = $DB->get_records('cohort', $conditions = null, $sort = 'name');
$roles = enrol_select_get_custom_student_roles();
$cards = $DB->get_records('apsolu_payments_cards', $conditions = null, $sort = 'name');
$calendars = [(object) ['id' => 0, 'name' => get_string('none')]];
$calendars += $DB->get_records('apsolu_calendars', $conditions = null, $sort = 'name');
$enrolmethods = [0 => get_string('reenrolment_disabled', 'enrol_select')];
foreach ($DB->get_records('enrol', ['courseid' => $course->id, 'enrol' => 'select'], 'name') as $enrol) {
    if ($enrol->id === $instance->id) {
        // On ne met pas sa propre instance dans la liste des méthodes disponibles à la réinscription.
        continue;
    }

    $enrolmethods[$enrol->id] = $plugin->get_instance_name($enrol);
}

for ($i = 1; $i < 4; $i++) {
    // Positionne les attributs pour la gestion des messages de bienvenue.
    $customtext = sprintf('customtext%d', $i);
    $customtextswitch = sprintf('customtext%dswitch', $i);
    $instance->{$customtextswitch} = (int) !empty($instance->{$customtext});
    $instance->{$customtext} = ['text' => $instance->{$customtext}, 'format' => FORMAT_HTML];
}

$mform = new enrol_select_edit_form(null, [$instance, $plugin, $context, $cohorts, $roles, $enrolmethods, $calendars, $cards]);

if ($mform->is_cancelled()) {
    redirect($return);
} else if ($data = $mform->get_data()) {
    // Définit les valeurs par défaut.
    if (isset($data->customint4) === false) {
        $data->customint4 = 0;
    }

    if (isset($data->customint5) === false) {
        $data->customint5 = 0;
    }

    if (isset($data->customint6) === false) {
        $data->customint6 = 0;
    }

    if (isset($data->customchar2) === false) {
        $data->customchar2 = 0;
    }

    if (isset($data->customchar3) === false) {
        $data->customchar3 = enrol_select_plugin::MAIN;
    }

    if (empty($data->customtext1switch) === true) {
        $data->customtext1 = ['text' => ''];
    }

    if (empty($data->customtext2switch) === true) {
        $data->customtext2 = ['text' => ''];
    }

    if (empty($data->customtext3switch) === true) {
        $data->customtext3 = ['text' => ''];
    }

    if (empty($data->customint3) === true) {
        // Réinitialise le champ "remontée de liste automatique" lorsque les quotas sont désactivés.
        $data->customchar2 = 0;
    }

    if (empty($data->customchar1) === false && isset($calendars[$data->customchar1]) === true) {
        $calendar = $calendars[$data->customchar1];
        // Note: afin de permettre la réouverture d'inscription en cours d'année,
        // on permet à un utilisateur de diverger avec le calendrier officiel.
        // Par contre, si une modification est effectuée dans le calendrier,
        // les dates d'inscriptions seront écrasées pour tous les cours.
        if ($instance->id === null) {
            // On applique les dates du calendrier, seulement lors de la création d'une méthode.
            $data->enrolstartdate = $calendar->enrolstartdate;
            $data->enrolenddate = $calendar->enrolenddate;
        }
        $data->customint4 = $calendar->reenrolstartdate;
        $data->customint5 = $calendar->reenrolenddate;
        $data->customint7 = $calendar->coursestartdate;
        $data->customint8 = $calendar->courseenddate;
    }

    if (isset($data->cards) === false || count($data->cards) === 0) {
        // Désactive le délai de paiement si aucune carte n'est disponible.
        $data->customdec1 = 0;
    }

    if ($instance->id) {
        $reset = ($instance->status != $data->status);

        $data->customtext1 = $data->customtext1['text'];
        $data->customtext2 = $data->customtext2['text'];
        $data->customtext3 = $data->customtext3['text'];

        $plugin->update_instance($instance, $data);

        // Mets à jour les dates d'accès au cours des étudiants.
        $sql = "UPDATE {user_enrolments} SET timestart = :timestart, timeend = :timeend WHERE enrolid = :enrolid";
        $params = ['timestart' => $instance->customint7, 'timeend' => $instance->customint8, 'enrolid' => $instance->id];
        $DB->execute($sql, $params);

        if ($reset) {
            $context->mark_dirty();
        }
    } else {
        $data->customtext1 = $data->customtext1['text'];
        $data->customtext2 = $data->customtext2['text'];
        $data->customtext3 = $data->customtext3['text'];

        $instance->id = $plugin->add_instance($course, (array) $data);
    }

    $DB->delete_records('enrol_select_cohorts', ['enrolid' => $instance->id]);
    if (isset($data->cohorts) === true) {
        foreach ($data->cohorts as $cohortid) {
            $DB->execute('INSERT INTO {enrol_select_cohorts}(enrolid, cohortid) VALUES(?, ?)', [$instance->id, $cohortid]);
        }
    }

    $DB->delete_records('enrol_select_roles', ['enrolid' => $instance->id]);
    if (isset($data->roles) === true) {
        foreach ($data->roles as $roleid) {
            $DB->execute('INSERT INTO {enrol_select_roles}(enrolid, roleid) VALUES(?, ?)', [$instance->id, $roleid]);
        }
    }

    $DB->delete_records('enrol_select_cards', ['enrolid' => $instance->id]);
    if (isset($data->cards) === true) {
        foreach ($data->cards as $cardid) {
            $DB->execute('INSERT INTO {enrol_select_cards}(enrolid, cardid) VALUES(?, ?)', [$instance->id, $cardid]);
        }
    }

    // Génère ou met à jour le carnet de notes sur tous les créneaux APSOLU.
    // TODO: correction temporaire. À supprimer lorsque la gestion des activités complémentaires sera implémentée.
    $apsolucourse = new course();
    $apsolucourse->load($course->id, $required = false);
    if (empty($apsolucourse->id) === false) {
        $apsolucourse->set_gradebook();
    }

    redirect($return);
}

$pluginname = get_string('pluginname', 'enrol_select');

$PAGE->set_heading($course->fullname);
$PAGE->set_title($pluginname);

$PAGE->navbar->add(get_string('users'));
$PAGE->navbar->add(get_string('enrolmentinstances', 'enrol'), new moodle_url('/enrol/instances.php', ['id' => $course->id]));
$PAGE->navbar->add($pluginname);

echo $OUTPUT->header();
echo $OUTPUT->heading($pluginname);
$mform->display();
echo $OUTPUT->footer();
