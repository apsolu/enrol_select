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
 * Select enrolment plugin.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/enrol/select/locallib.php');
require_once($CFG->dirroot.'/local/apsolu_payment/locallib.php');

class enrol_select_plugin extends enrol_plugin {
    const ACCEPTED = '0';
    const MAIN = '2';
    const WAIT = '3';
    const DELETED = '4';

    public static $states = array(
        self::ACCEPTED => 'accepted',
        self::MAIN => 'main',
        self::WAIT => 'wait',
        self::DELETED => 'deleted',
    );

    public static function get_enrolment_list_name($status, $type = null) {
        if ($type !== null ) {
            $type = '_'.$type;
        }

        switch ($status) {
            case self::ACCEPTED:
            case self::MAIN:
            case self::WAIT:
            case self::DELETED:
                return get_string(self::$states[$status].'_list'.$type, 'enrol_select');
        }

        return false;
    }

    public function get_name() {
        // Second word in class is always enrol name, sorry, no fancy plugin names with _.
        return 'select';
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'select') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/select:enrol', $context) or has_capability('enrol/select:unenrol', $context)) {
            $managelink = new moodle_url("/enrol/select/manage.php", array('enrolid' => $instance->id));

            $pixicon = new pix_icon('t/enrolusers', get_string('enrolusers', 'enrol_manual'), 'core', array('class' => 'iconsmall'));
            $icons[] = $OUTPUT->action_icon($managelink, $pixicon);
        }

        if (has_capability('enrol/select:config', $context)) {
            $editlink = new moodle_url("/enrol/select/edit.php", array('courseid' => $instance->courseid, 'id' => $instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('t/edit', get_string('edit'), 'core',
                array('class' => 'iconsmall')));
        }

        return $icons;
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/select:config', $context)) {
            return null;
        }
        // Multiple instances supported - different roles with different password.
        return new moodle_url('/enrol/select/edit.php', array('courseid' => $courseid));
    }

    /**
     * Returns defaults for new instances.
     * @return array
     */
    public function get_instance_defaults() {
        $fields = array();

        $fields['status']          = ENROL_INSTANCE_ENABLED;  // Enable method or not.
        $fields['roles']           = array(5);  // Default role.
        $fields['customint1']      = 20; // Max places on main list.
        $fields['customint2']      = 10; // Max places on wait list.
        $fields['customint3']      = 0;  // Enable quota or not.
        $fields['customint4']      = 0;  // Reenrol start date.
        $fields['customint5']      = 0;  // Reenrol end date.
        $fields['customint6']      = 0;  // Reenrol select_enrol instance.
        $fields['customint7']      = 0;  // Course start date.
        $fields['customint8']      = 0;  // Course end date.

        return $fields;
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);

        return has_capability('enrol/select:config', $context);
    }

    /**
     * Return true if we can add a new instance to this course.
     *
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid) {
        global $DB;

        $context = context_course::instance($courseid, MUST_EXIST);
        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/manual:config', $context)) {
            return false;
        }

        if ($DB->record_exists('enrol', array('courseid' => $courseid, 'enrol' => 'manual'))) {
            // Multiple instances not supported.
            return false;
        }

        return true;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param object $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/select:config', $context);
    }

    public function get_roles($instance, $context) {
        global $DB;

        $roles = $DB->get_records('enrol_select_roles', array('enrolid' => $instance->id), '', 'roleid');
        foreach (get_assignable_roles($context, ROLENAME_BOTH) as $id => $role) {
            if (!isset($roles[$id])) {
                unset($roles[$id]);
            } else {
                $roles[$id]->id = $id;
                $roles[$id]->name = $role;
            }
        }

        $roles = array_values($roles);

        return $roles;
    }

    /**
     * Retourne le rôle d'un utilisateur pour une méthode d'inscription donnée.
     *
     * @param object $instance Objet de l'instance de la méthode d'inscription
     * @param int    $userid   Identifiant de l'utilisateur. Si il n'est pas fourni, c'est l'identifiant de l'utilisateur courant qui sera utilisé.
     * @return object|false Retourne un object du rôle ou false si l'utilisateur n'est pas inscrit via cette méthode.
     */
    public function get_user_role($instance, $userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $time = time();

        $sql = "SELECT r.*".
            " FROM {role} r".
            " JOIN {role_assignments} ra ON r.id = ra.roleid".
            " JOIN {context} ctx ON ctx.id = ra.contextid".
            " JOIN {enrol} e ON ctx.instanceid = e.courseid AND e.id = ra.itemid".
            " JOIN {user_enrolments} ue ON e.id = ue.enrolid AND ue.userid = ra.userid".
            " WHERE e.id = :enrolid".
            " AND e.enrol = 'select'".
            " AND e.status = 0". // Active.
            " AND ue.userid = :userid".
            " AND (ue.timestart = 0 OR ue.timestart <= :timestart)".
            " AND (ue.timeend = 0 OR ue.timeend >= :timeend)".
            " AND ctx.contextlevel = 50";
        $params = array('enrolid' => $instance->id, 'userid' => $userid, 'timestart' => $time, 'timeend' => $time);

        $roles = role_fix_names($DB->get_records_sql($sql, $params));

        return current($roles);
    }

    public function get_available_user_roles($instance, $userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $sql = "SELECT r.*".
            " FROM {role} r".
            " JOIN {enrol_select_roles} esr ON r.id = esr.roleid".
            " JOIN {enrol} e ON e.id = esr.enrolid".
            " JOIN {enrol_select_cohorts} esc ON e.id = esc.enrolid".
            " JOIN {cohort_members} cm ON cm.cohortid = esc.cohortid".
            " JOIN {apsolu_colleges_members} acm ON acm.cohortid = cm.cohortid".
            " JOIN {apsolu_colleges} ac ON ac.id = acm.collegeid AND r.id = ac.roleid".
            " WHERE e.id = :enrolid".
            " AND e.enrol = 'select'".
            " AND e.status = 0". // Active.
            " AND cm.userid = :userid";
        $params = array('enrolid' => $instance->id, 'userid' => $userid);

        return role_fix_names($DB->get_records_sql($sql, $params));
    }


    public function set_available_status($instance, $user = null) {
        global $DB;

        $this->available_status = array();

        // Check main list.
        $sql = "SELECT userid".
            " FROM {user_enrolments}".
            " WHERE enrolid = :enrolid".
            " AND status IN (0, 2)";
        $mainlistenrolements = $DB->get_records_sql($sql, array('enrolid' => $instance->id));
        $this->count_main_list_enrolements = count($mainlistenrolements);

        if (isset($user, $mainlistenrolements[$user->id])) {
            unset($mainlistenrolements[$user->id]);
            $countmainlistenrolements = $this->count_main_list_enrolements - 1;
        } else {
            $countmainlistenrolements = $this->count_main_list_enrolements;
        }

        if ($countmainlistenrolements < $instance->customint1) {
            // Some slots are available on main list.
            $this->available_status[] = '2';
        }

        // Check wait list.
        $waitlistenrolements = $DB->get_records('user_enrolments', array('enrolid' => $instance->id, 'status' => 3), '', 'userid');
        $this->count_wait_list_enrolements = count($waitlistenrolements);

        if (isset($user, $waitlistenrolements[$user->id])) {
            unset($waitlistenrolements[$user->id]);
            $countwaitlistenrolements = $this->count_wait_list_enrolements - 1;
        } else {
            $countwaitlistenrolements = $this->count_wait_list_enrolements;
        }

        if ($countwaitlistenrolements < $instance->customint2) {
            // Some slots are available on wait list.
            $this->available_status[] = '3';
        }
    }

    public function is_enrol_period_active($instance) {
        $today = time();

        $opening = ($instance->enrolstartdate === '0' || $instance->enrolstartdate <= $today);
        $closing = ($instance->enrolenddate === '0' || $instance->enrolenddate >= $today);

        return ($opening && $closing);
    }

    public function can_enrol($instance, $user, $roleid) {
        global $DB;

        $today = time();

        // Check opening register period.
        if ($instance->enrolstartdate !== '0' && $instance->enrolstartdate > $today) {
            debugging($this->get_name().' not opened yet.');
            return false;
        }

        // Check closing register period.
        if ($instance->enrolenddate !== '0' && $instance->enrolenddate < $today) {
            debugging($this->get_name().' already closed.');
            return false;
        }

        // Check cohorts.
        if ($instance->customint3 === '1') {
            $usercohorts = $DB->get_records('cohort_members', array('userid' => $user->id));
            $enrolcohorts = $DB->get_records('enrol_select_cohorts', array('enrolid' => $instance->id), '', 'cohortid');

            $found = false;
            foreach ($usercohorts as $cohort) {
                if (isset($enrolcohorts[$cohort->cohortid])) {
                    $found = true;
                    break;
                }
            }

            if ($found !== true) {
                debugging($this->get_name().': '.$user->username.' and enrol cohort mismatch.');
                return false;
            }
        }

        // Dirty hack pour les activités complémentaires !
        // À virer, et mettre des auto-inscriptions à la place.
        if ($roleid == 5) {
            return true;
        }

        // Check available slots.
        // TODO: la méthode set_available_status() ne gère pas correctement les no quotas.
        $this->set_available_status($instance, $user);
        if ($this->available_status === array()) {
            debugging($this->get_name().' have no free slot anymore.');
            return false;
        }

        // Check user limit.
        $userchoices = UniversiteRennes2\Apsolu\get_user_colleges($userid = null, $count = true);
        $available = false;
        foreach ($userchoices as $choice) {
            if ($choice->roleid != $roleid) {
                continue;
            }

            if ($choice->maxwish == 0 || $choice->count < $choice->maxwish) {
                $available = true;
            }
        }

        if ($available === false) {
            // $role = current(role_fix_names(array($roleid => $DB->get_record('role', array('id' => $roleid)))));
            // debugging(get_string('error_reach_wishes_limit', 'enrol_select', $role->localname));
            return false;
        }

        // Check role.
        if ($DB->get_record('enrol_select_roles', array('enrolid' => $instance->id, 'roleid' => $roleid)) === false) {
            debugging($this->get_name().': roleid #'.$roleid.' is not available.');
            return false;
        }

        return true;
    }

    public function can_reenrol($instance, $userid = null, $roleid = null) {
        global $DB, $USER;

        $today = time();

        if ($userid === null) {
            $userid = $USER->id;
        }

        // Check reenrol enabled.
        if (empty($instance->customint6)) {
            debugging($this->get_name().' reenrol not enabled.');
            return false;
        }

        // Check reenrol exists.
        $enrol = $DB->get_record('enrol', array('id' => $instance->customint6, 'enrol' => 'select'));
        if ($enrol === false) {
            debugging($this->get_name().' reenrol id #'.$instance->customint6.' does not exist');
            return false;
        }

        // Check opening reenrol period.
        if ($instance->customint4 !== '0' && $instance->customint4 > $today) {
            debugging($this->get_name().' not opened yet.');
            return false;
        }

        // Check closing reenrol period.
        if ($instance->customint5 !== '0' && $instance->customint5 < $today) {
            debugging($this->get_name().' already closed.');
            return false;
        }

        // Check cohorts.
        if ($instance->customint3 === '1') {
            $usercohorts = $DB->get_records('cohort_members', array('userid' => $userid));
            $enrolcohorts = $DB->get_records('enrol_select_cohorts', array('enrolid' => $instance->id), '', 'cohortid');

            $found = false;
            foreach ($usercohorts as $cohort) {
                if (isset($enrolcohorts[$cohort->cohortid])) {
                    $found = true;
                    break;
                }
            }

            if ($found !== true) {
                debugging($this->get_name().': userid #'.$userid.' and enrol cohort mismatch.');
                return false;
            }
        }

        // We don't check available slots.
        // We don't check user limit.

        // Check role.
        if ($roleid !== null) {
            if ($DB->get_record('enrol_select_roles', array('enrolid' => $instance->id, 'roleid' => $roleid)) === false) {
                debugging($this->get_name().': roleid #'.$roleid.' is not available.');
                return false;
            }
        }

        return true;
    }

    public function enrol_user(stdClass $instance, $userid, $roleid = null, $timestart = 0, $timeend = 0, $status = null, $recovergrades = null) {
        global $DB;

        $currentenrol = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $userid));
        if ($currentenrol === false) {
            if (in_array($instance->courseid, array(249, 250))) {
                // Inscription à la ffsu ou à la musculation.
                $timestart = 0; // Pas de date de début.
                $timeend = 0; // Pas de date de fin.
                $status = 0; // Étudiant accepté automatiquement.
            } else {
                // Inscription à un cours du SIUAPS.
                if ($timestart === 0) {
                    $timestart = $instance->customint7;
                }

                if ($timeend === 0) {
                    $timeend = $instance->customint8;
                }
            }

            parent::enrol_user($instance, $userid, $roleid, $timestart, $timeend, $status, $recovergrades);
        } else {
            $coursecontext = context_course::instance($instance->courseid);

            $sql = "UPDATE {role_assignments} SET roleid = ? WHERE component = 'enrol_select' AND userid = ? AND contextid = ? AND itemid= ?";
            $DB->execute($sql, array($roleid, $userid, $coursecontext->id, $instance->id));
        }
    }
}
