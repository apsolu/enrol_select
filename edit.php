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
 * Adds new instance of enrol_select to specified course
 * or edits current instance.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use UniversiteRennes2\Apsolu as apsolu;

require('../../config.php');
require_once('edit_form.php');
require_once('locallib.php');

$courseid = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/select:config', $context);

$PAGE->set_url('/enrol/select/edit.php', array('courseid' => $course->id, 'id' => $instanceid));
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', array('id' => $course->id));
if (!enrol_is_enabled('select')) {
    redirect($return);
}

$plugin = enrol_get_plugin('select');

if ($instanceid) {
    $conditions = array('courseid' => $course->id, 'enrol' => 'select', 'id' => $instanceid);
    $instance = $DB->get_record('enrol', $conditions, '*', MUST_EXIST);

    $instance->cohorts = array_keys($DB->get_records('enrol_select_cohorts', array('enrolid' => $instance->id), '', 'cohortid'));
    $instance->roles = array_keys($DB->get_records('enrol_select_roles', array('enrolid' => $instance->id), '', 'roleid'));
} else {
    require_capability('moodle/course:enrolconfig', $context);
    // No instance yet, we have to add new instance.
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id' => $course->id)));

    $instance = (object) $plugin->get_instance_defaults();
    $instance->id       = null;
    $instance->courseid = $course->id;
    // Do not use default for automatically created instances here.
    $instance->status   = ENROL_INSTANCE_ENABLED;
}

$cohorts = $DB->get_records('cohort');
$roles = apsolu\get_custom_student_roles();
$mform = new enrol_select_edit_form(null, array($instance, $plugin, $context, $cohorts, $roles));

if ($mform->is_cancelled()) {
    redirect($return);

} else if ($data = $mform->get_data()) {
    if ($instance->id) {
        $reset = ($instance->status != $data->status);

        $instance->status         = $data->status;
        $instance->name           = $data->name;
        $instance->customint1     = $data->customint1;
        $instance->customint2     = $data->customint2;
        $instance->customint3     = $data->customint3;
        $instance->enrolstartdate = $data->enrolstartdate;
        $instance->enrolenddate   = $data->enrolenddate;
        $instance->timemodified   = time();
        $DB->update_record('enrol', $instance);

        if ($reset) {
            $context->mark_dirty();
        }

    } else {
        $fields = array(
            'status'          => ENROL_INSTANCE_ENABLED,
            'name'            => $data->name,
            'customint1'      => $data->customint1,
            'customint2'      => $data->customint2,
            'customint3'      => $data->customint3,
            'enrolstartdate'  => $data->enrolstartdate,
            'enrolenddate'    => $data->enrolenddate);
        $instance->id = $plugin->add_instance($course, $fields);
    }

    $DB->delete_records('enrol_select_cohorts', array('enrolid' => $instance->id));
    if (isset($data->cohorts)) {
        foreach ($data->cohorts as $cohortid) {
            $DB->execute('INSERT INTO {enrol_select_cohorts}(enrolid, cohortid) VALUES(?, ?)', array($instance->id, $cohortid));
        }
    }

    $DB->delete_records('enrol_select_roles', array('enrolid' => $instance->id));
    if (isset($data->roles)) {
        foreach ($data->roles as $roleid) {
            $DB->execute('INSERT INTO {enrol_select_roles}(enrolid, roleid) VALUES(?, ?)', array($instance->id, $roleid));
        }
    }

    redirect($return);
}

$pluginname = get_string('pluginname', 'enrol_select');

$PAGE->set_heading($course->fullname);
$PAGE->set_title($pluginname);

$PAGE->navbar->add(get_string('users'));
$PAGE->navbar->add(get_string('enrolmentinstances', 'enrol'), new moodle_url('/enrol/instances.php', array('id' => $course->id)));
$PAGE->navbar->add($pluginname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_select'));
$mform->display();
echo $OUTPUT->footer();
