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

defined('MOODLE_INTERNAL') || die();

use local_apsolu\core\federation\course as FederationCourse;

require_once($CFG->dirroot.'/enrol/select/locallib.php');
require_once($CFG->dirroot.'/local/apsolu/locallib.php');

/**
 * Classe principale du module enrol_select.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_select_plugin extends enrol_plugin {
    /**
     * Code du statut accepté.
     */
    const ACCEPTED = '0';

    /**
     * Code du statut de la liste principale.
     */
    const MAIN = '2';

    /**
     * Code du statut de la liste secondaire.
     */
    const WAIT = '3';

    /**
     * Code du statut désinscrit.
     */
    const DELETED = '4';

    /** @var array Tableau indexé avec les constantes de classe ACCEPTED, MAIN, WAIT et DELETED. */
    public static $states = array(
        self::ACCEPTED => 'accepted',
        self::MAIN => 'main',
        self::WAIT => 'wait',
        self::DELETED => 'deleted',
    );

    /**
     * Retourne la chaine de caractères correspondant à une constante.
     *
     * @param int|string  $status Valeur correspondant à une des constantes de classe (ACCEPTED, MAIN, WAIT et DELETED).
     * @param null|string $type   Valeur pouvant être null, abbr ou short.
     *
     * @return string|false Retourne false si le $status n'est pas correct.
     */
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

    /**
     * Returns name of this enrol plugin.
     *
     * @return string
     */
    public function get_name() {
        // Second word in class is always enrol name, sorry, no fancy plugin names with _.
        return 'select';
    }

    /**
     * Returns edit icons for the page with list of instances.
     *
     * @param stdClass $instance
     *
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'select') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/select:enrol', $context) || has_capability('enrol/select:unenrol', $context)) {
            $managelink = new moodle_url("/enrol/select/manage.php", array('enrolid' => $instance->id));

            $label = get_string('enrolusers', 'enrol_manual');
            $pixicon = new pix_icon('t/enrolusers', $label, 'core', array('class' => 'iconsmall'));
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
     * Méthode permettant de récupérer la liste des utilisateurs dont l'inscription est valide/autorisée pour une péride donnée.
     *
     * @param int|string $courseid  Identifiant du cours.
     * @param int|null   $timestart Horodatage UNIX représentant la date de début de la période à valider.
     * @param int|null   $timeend   Horodatage UNIX représentant la date de fin de la période à valider.
     *
     * @return array Tableau contenant les utilisateurs indéxé par userid.
     */
    public static function get_authorized_registred_users($courseid, $timestart = null, $timeend = null) {
        global $DB;

        if ($timestart === null) {
            $timestart = time();
        }

        if ($timeend === null) {
            $timeend = time();
        }

        $sql = "SELECT DISTINCT u.*".
            " FROM {user} u".
            " JOIN {user_enrolments} ue ON u.id = ue.userid".
            " JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'select'".
            " JOIN {role_assignments} ra ON u.id = ra.userid AND e.id = ra.itemid AND ra.component = 'enrol_select'".
            " JOIN {role} r ON r.id = ra.roleid".
            " JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.instanceid = e.courseid".
            " JOIN {cohort_members} cm ON u.id = cm.userid".
            " JOIN {enrol_select_roles} esr ON r.id = esr.roleid AND e.id = esr.enrolid".
            " JOIN {enrol_select_cohorts} esc ON cm.cohortid = esc.cohortid AND e.id = esr.enrolid".
            " JOIN {apsolu_colleges_members} acm ON cm.cohortid = acm.cohortid".
            " JOIN {apsolu_colleges} ac ON ra.roleid = ac.roleid AND acm.collegeid = ac.id".
            " WHERE e.status = 0". // Only active enrolments.
            " AND ue.status = 0".
            " AND (ue.timestart < :timestart OR ue.timestart = 0)".
            " AND (ue.timeend > :timeend OR ue.timeend = 0)".
            " AND e.courseid = :courseid".
            " AND ctx.contextlevel = 50". // Course level.
            " AND r.archetype = 'student'";

        $params = array();
        $params['courseid'] = $courseid;
        $params['timestart'] = $timestart;
        $params['timeend'] = $timeend;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * This returns false for backwards compatibility, but it is really recommended.
     *
     * @since Moodle 3.1
     * @return boolean
     */
    public function use_standard_editing_ui() {
        // TODO: mettre à true pour afficher les méthodes dans le menu déroulant.
        return false;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        // TODO: ne pas utiliser ce hook moche.
        redirect(new moodle_url('/enrol/select/edit.php', array('courseid' => $instance->courseid, 'id' => $instance->id)));
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        // TODO.
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     *
     * @param int $courseid
     *
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) || !has_capability('enrol/select:config', $context)) {
            return null;
        }
        // Multiple instances supported - different roles with different password.
        return new moodle_url('/enrol/select/edit.php', array('courseid' => $courseid));
    }

    /**
     * Returns defaults for new instances.
     *
     * @return array
     */
    public function get_instance_defaults() {
        $instance = get_config('enrol_select');

        if (isset($instance->default_roles) === false) {
            $instance->default_cohorts = '';
            $instance->default_roles = 5;
            $instance->default_cards = '';
            $instance->default_customint1 = 20;
            $instance->default_customint2 = 10;
            $instance->default_customint3 = 0;
            $instance->default_customdec1 = 0;
            $instance->default_customchar1 = 0;
            $instance->default_customchar2 = 0;
            $instance->default_customchar3 = self::MAIN;
            $instance->default_customtext1 = '';
            $instance->default_customtext2 = '';
            $instance->default_customtext3 = '';
        }

        $fields = array();
        $fields['status'] = ENROL_INSTANCE_ENABLED; // Enable method or not.
        $fields['cohorts'] = explode(',', $instance->default_cohorts); // Cohortes par défaut.
        $fields['roles'] = explode(',', $instance->default_roles); // Rôles par défaut.
        $fields['cards'] = explode(',', $instance->default_cards); // Paiements par défaut.
        $fields['customint1'] = $instance->default_customint1; // Maximum de place sur la liste principale.
        $fields['customint2'] = $instance->default_customint2; // Maximum de place sur la liste d'attente.
        $fields['customint3'] = $instance->default_customint3; // Activer les quotas.
        $fields['customint4'] = 0; // Reenrol start date.
        $fields['customint5'] = 0; // Reenrol end date.
        $fields['customint6'] = 0; // Reenrol select_enrol instance.
        $fields['customint7'] = 0; // Course start date.
        $fields['customint8'] = 0; // Course end date.
        $fields['customdec1'] = $instance->default_customdec1; // Délai de paiement.
        $fields['customchar1'] = $instance->default_customchar1; // Type de calendrier.
        $fields['customchar2'] = $instance->default_customchar2; // Remontée de liste automatique.
        $fields['customchar3'] = $instance->default_customchar3; // Liste sur laquelle inscrire les étudiants.
        $fields['customtext1'] = $instance->default_customtext1; // Message de bienvenue pour les inscrits sur liste des acceptés.
        $fields['customtext2'] = $instance->default_customtext2; // Message de bienvenue pour les inscrits sur liste principale.
        $fields['customtext3'] = $instance->default_customtext3; // Message de bienvenue pour les inscrits sur liste complémentaire.

        return $fields;
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     *
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
     *
     * @return boolean
     */
    public function can_add_instance($courseid) {
        global $DB;

        $context = context_course::instance($courseid, MUST_EXIST);
        if (!has_capability('moodle/course:enrolconfig', $context) || !has_capability('enrol/manual:config', $context)) {
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
     *
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/select:config', $context);
    }

    /**
     * Retourne le code de la liste dans laquelle sera enregistré le prochain inscrit.
     *
     * @param object $instance Objet de l'instance de la méthode d'inscription.
     * @param object $user     Objet représentant le prochain utilisateur inscrit.
     *
     * @return string|false Retourne false si il n'y a plus de place cette méthode d'inscription par voeux.
     */
    public function get_available_status($instance, $user) {
        global $DB;

        if (empty($instance->customint3) === true) {
            // Lorsque les quota ne sont pas activés, on retourne le code de la liste principale.
            return self::get_default_enrolment_list($instance);
        }

        // Détermine si l'utilisateur est déjà inscrit sur cette instance.
        $userenrolment = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $user->id));
        if ($userenrolment !== false) {
            // Retourne la liste d'inscription actuelle de l'utilisateur.
            return $userenrolment->status;
        }

        // Détermine si il y a déjà des utilisateurs sur liste complémentaire.
        $params = array('enrolid' => $instance->id, 'status' => self::WAIT);
        $waitlistenrolements = $DB->get_records('user_enrolments', $params, '', 'userid');
        $this->count_wait_list_enrolements = count($waitlistenrolements);

        if (isset($user, $waitlistenrolements[$user->id])) {
            unset($waitlistenrolements[$user->id]);
            $countwaitlistenrolements = $this->count_wait_list_enrolements - 1;
        } else {
            $countwaitlistenrolements = $this->count_wait_list_enrolements;
        }

        if ($countwaitlistenrolements >= $instance->customint2 && empty($instance->customint2) === false) {
            // Il n'y a plus de place disponible sur la liste complémentaire
            // et les quotas sont définis pour la liste complémentaire.
            return false;
        }

        if ($countwaitlistenrolements !== 0) {
            // Il y a déjà des utilisateurs sur liste complémentaire. On est sûr qu'il reste de la place uniquement ici.
            return self::WAIT;
        }

        // Détermine si il y a déjà des utilisateurs sur la liste des acceptés et la liste principale.
        $sql = "SELECT userid".
            " FROM {user_enrolments}".
            " WHERE enrolid = :enrolid".
            " AND status IN (:accepted, :main)";
        $params = array('enrolid' => $instance->id, 'accepted' => self::ACCEPTED, 'main' => self::MAIN);
        $mainlistenrolements = $DB->get_records_sql($sql, $params);
        $this->count_main_list_enrolements = count($mainlistenrolements);

        if (isset($user, $mainlistenrolements[$user->id])) {
            unset($mainlistenrolements[$user->id]);
            $countmainlistenrolements = $this->count_main_list_enrolements - 1;
        } else {
            $countmainlistenrolements = $this->count_main_list_enrolements;
        }

        if ($countmainlistenrolements < $instance->customint1 && empty($instance->customint1) === false) {
            // Il reste des places disponibles sur liste principale et les quotas sont définis pour la liste principale.
            return self::get_default_enrolment_list($instance);
        }

        // Cas où la liste principale est pleine et la liste complémentaire est vide.
        if (empty($instance->customint2) === false) {
            // Si le quota sur la liste complémentaire n'est pas vide.
            return self::WAIT;
        }

        // Il n'y a pas de liste complémentaire. Il n'est plus possible de s'inscrire.
        return false;
    }

    /**
     * Retourne le code de la liste sur laquelle est inscrit l'utilisateur par défaut.
     *
     * @param object $instance Objet de l'instance de la méthode d'inscription.
     *
     * @return int Retourne soit self::ACCEPTED, soit self::MAIN.
     */
    public static function get_default_enrolment_list($instance) {
        if ($instance->customchar3 === self::ACCEPTED) {
            return self::ACCEPTED;
        }

        return self::MAIN;
    }

    /**
     * Retourne les rôles disponibles pour une méthode d'inscription donnée et un contexte.
     *
     * @param object $instance Objet de l'instance de la méthode d'inscription
     * @param object $context  Objet représentant un contexte.
     *
     * @return array Retourne un tableau de rôles.
     */
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
     * @param int    $userid   Identifiant de l'utilisateur. Si il n'est pas fourni,
     *                         c'est l'identifiant de l'utilisateur courant qui sera utilisé.
     *
     * @return object|false Retourne un object du rôle ou false si l'utilisateur n'est pas inscrit via cette méthode.
     */
    public function get_user_role($instance, $userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

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
            " AND ctx.contextlevel = 50";
        $params = array('enrolid' => $instance->id, 'userid' => $userid);

        $roles = role_fix_names($DB->get_records_sql($sql, $params));

        return current($roles);
    }

    /**
     * Retourne les rôles disponibles pour un utilisateur et une méthode d'inscription donnée.
     *
     * @param object $instance Objet de l'instance de la méthode d'inscription
     * @param int    $userid   Identifiant de l'utilisateur. Si il n'est pas fourni,
     *                         c'est l'identifiant de l'utilisateur courant qui sera utilisé.
     *
     * @return array Retourne un tableau de rôles.
     */
    public function get_available_user_roles($instance, $userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $sql = "SELECT DISTINCT r.*".
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

    /**
     * Fonction à documenter (TODO).
     *
     * @param object      $instance Objet de l'instance de la méthode d'inscription
     * @param null|object $user     Identifiant de l'utilisateur. Si il n'est pas fourni,
     *                              c'est l'identifiant de l'utilisateur courant qui sera utilisé.
     *
     * @return void.
     */
    public function set_available_status($instance, $user = null) {
        global $DB;

        debugging(sprintf('%s() is deprecated. Please see'.
            ' enrol_select_plugin::get_available_status() method instead.', __METHOD__), DEBUG_DEVELOPER);

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
        $params = array('enrolid' => $instance->id, 'status' => self::WAIT);
        $waitlistenrolements = $DB->get_records('user_enrolments', $params, '', 'userid');
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

    /**
     * Détermine si les inscriptions sont ouvertes.
     *
     * @param object $instance Objet de l'instance de la méthode d'inscription
     *
     * @return bool Vrai si les inscriptions sont ouvertes.
     */
    public function is_enrol_period_active($instance) {
        $today = time();

        $opening = ($instance->enrolstartdate === '0' || $instance->enrolstartdate <= $today);
        $closing = ($instance->enrolenddate === '0' || $instance->enrolenddate >= $today);

        return ($opening && $closing);
    }

    /**
     * Détermine si un utilisateur peut s'inscrire à une instance avec un rôle donné.
     *
     * @param object     $instance Objet de l'instance de la méthode d'inscription.
     * @param object     $user     Objet représentant l'utilisateur.
     * @param int|string $roleid   Identifiant d'un rôle.
     *
     * @return bool Vrai si les inscriptions sont ouvertes.
     */
    public function can_enrol($instance, $user, $roleid) {
        global $CFG, $DB;

        $today = time();

        // Check opening register period.
        if ($instance->enrolstartdate !== '0' && $instance->enrolstartdate > $today) {
            debugging($this->get_name().' not opened yet.', $level = DEBUG_DEVELOPER);
            return false;
        }

        // Check closing register period.
        if ($instance->enrolenddate !== '0' && $instance->enrolenddate < $today) {
            debugging($this->get_name().' already closed.', $level = DEBUG_DEVELOPER);
            return false;
        }

        // Check cohorts.
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
            debugging($this->get_name().': '.$user->username.' and enrol cohort mismatch.', $level = DEBUG_DEVELOPER);
            return false;
        }

        $federationcourse = new FederationCourse();
        if ($instance->courseid === $federationcourse->get_courseid()) {
            // Bidouille moche pour gérer l'inscription à la FFSU.
            return true;
        }

        if (isset($CFG->is_siuaps_rennes) === true) {
            // Dirty hack pour les activités complémentaires !
            // À virer, et mettre des auto-inscriptions à la place.
            if ($roleid == 5 || in_array($instance->courseid, array('249', '250'), $strict = true) === true) {
                return true;
            }
        }

        // Check available slots.
        if ($this->get_available_status($instance, $user) === false) {
            debugging($this->get_name().' n\'a plus aucune place disponible.', $level = DEBUG_DEVELOPER);
            return false;
        }

        // Check user limit.
        $userchoices = enrol_select_get_sum_user_choices($userid = null, $count = true);
        $available = false;
        foreach ($userchoices as $choice) {
            if ($choice->roleid != $roleid) {
                continue;
            }

            if ($choice->maxwish == 0 || $choice->count < $choice->maxwish) {
                $available = true;
                break;
            }
        }

        if ($available === false) {
            $parameters = ['userid' => $user->id, 'roleid' => $roleid];
            $message = get_string('the_user_X_has_reached_their_wish_limit_for_the_role_Y', 'enrol_select', $parameters);

            debugging($message, $level = DEBUG_DEVELOPER);
            return false;
        }

        // Check role.
        if ($DB->get_record('enrol_select_roles', array('enrolid' => $instance->id, 'roleid' => $roleid)) === false) {
            debugging($this->get_name().': roleid #'.$roleid.' is not available.', $level = DEBUG_DEVELOPER);
            return false;
        }

        return true;
    }

    /**
     * Détermine si un utilisateur peut se réinscrire.
     *
     * @param object          $instance Objet de l'instance de la méthode d'inscription.
     * @param null|int|string $userid   Identifiant d'un utilisateur.
     * @param null|int|string $roleid   Identifiant d'un rôle.
     *
     * @return bool Vrai si les inscriptions sont ouvertes.
     */
    public function can_reenrol($instance, $userid = null, $roleid = null) {
        global $DB, $USER;

        $today = time();

        if ($userid === null) {
            $userid = $USER->id;
        }

        // Check reenrol enabled.
        if (empty($instance->customint6)) {
            debugging($this->get_name().' reenrol not enabled.', $level = DEBUG_DEVELOPER);
            return false;
        }

        // Check reenrol exists.
        $enrol = $DB->get_record('enrol', array('id' => $instance->customint6, 'enrol' => 'select'));
        if ($enrol === false) {
            debugging($this->get_name().' reenrol id #'.$instance->customint6.' does not exist', $level = DEBUG_DEVELOPER);
            return false;
        }

        // Check opening reenrol period.
        if ($instance->customint4 !== '0' && $instance->customint4 > $today) {
            debugging($this->get_name().' not opened yet.', $level = DEBUG_DEVELOPER);
            return false;
        }

        // Check closing reenrol period.
        if ($instance->customint5 !== '0' && $instance->customint5 < $today) {
            debugging($this->get_name().' already closed.', $level = DEBUG_DEVELOPER);
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
                debugging($this->get_name().': userid #'.$userid.' and enrol cohort mismatch.', $level = DEBUG_DEVELOPER);
                return false;
            }
        }

        // We don't check available slots.
        // We don't check user limit.

        // Check role.
        if ($roleid !== null) {
            if ($DB->get_record('enrol_select_roles', array('enrolid' => $instance->id, 'roleid' => $roleid)) === false) {
                debugging($this->get_name().': roleid #'.$roleid.' is not available.', $level = DEBUG_DEVELOPER);
                return false;
            }
        }

        return true;
    }

    /**
     * Méthode permettant l'inscription d'un utilisateur à une instance.
     *
     * @param stdClass        $instance      Objet de l'instance de la méthode d'inscription.
     * @param int|string      $userid        Identifiant d'un utilisateur.
     * @param null|int|string $roleid        Identifiant d'un rôle.
     * @param int|string      $timestart     Timestamp de début de cours.
     * @param int|string      $timeend       Timestamp de fin de cours.
     * @param null|int|string $status        État de l'inscription (accepté, liste principale, liste secondaire, supprimé).
     * @param null|bool       $recovergrades Récupérer les notes ?
     *
     * @return void.
     */
    public function enrol_user(stdClass $instance, $userid, $roleid = null, $timestart = 0, $timeend = 0,
        $status = null, $recovergrades = null) {
        global $DB, $USER;

        // La méthode parent::enrol_user() ne remplace pas les rôles, mais cumul.
        // Il faut donc faire un traitement différent si il s'agit juste d'un changement de rôle.
        $enrolled = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $userid));
        if ($enrolled === false) {
            if ($timestart === 0) {
                $timestart = $instance->customint7;
            }

            if ($timeend === 0) {
                $timeend = $instance->customint8;
            }

            parent::enrol_user($instance, $userid, $roleid, $timestart, $timeend, $status, $recovergrades);

            // Ajoute une tâche pour contrôler le paiement après l'inscription.
            if (empty($instance->customdec1) === false && $status === self::ACCEPTED) {
                $customdata = (object) ['courseid' => $instance->courseid, 'enrolid' => $instance->id];

                $task = new enrol_select\task\check_enrolment_payment();
                $task->set_next_run_time(time() + intval($instance->customdec1));
                $task->set_custom_data($customdata);
                $task->set_userid($userid);

                core\task\manager::queue_adhoc_task($task);
            }

            // Notifie le nouvel inscrit.
            if ($userid === $USER->id) {
                $message = '';
                switch ($status) {
                    case self::ACCEPTED:
                        $message = $instance->customtext1;
                        break;
                    case self::MAIN:
                        $message = $instance->customtext2;
                        break;
                    case self::WAIT:
                        $message = $instance->customtext3;
                        break;
                }

                if (empty($message) === false) {
                    $course = $DB->get_record('course', array('id' => $instance->courseid));

                    $eventdata = new \core\message\message();
                    $eventdata->name = 'select_notification';
                    $eventdata->component = 'enrol_select';
                    $eventdata->userfrom = get_admin();
                    $eventdata->userto = $userid;
                    $params = format_string($course->fullname, $striplinks = true, $course->id);
                    $eventdata->subject = get_string('enrolment_to', 'enrol_select', $params);
                    $eventdata->fullmessage = $message;
                    $eventdata->fullmessageformat = FORMAT_HTML;
                    $eventdata->fullmessagehtml = $message;
                    $eventdata->smallmessage = '';
                    $eventdata->notification = 1;
                    $eventdata->courseid = $course->id;

                    message_send($eventdata);
                }
            }

            return;
        }

        // Traite le cas où on souhaite juste modifier le statut.
        if ($status !== null) {
            $sql = "UPDATE {user_enrolments}".
                " SET status = :status, timemodified = :now".
                " WHERE enrolid = :enrolid".
                " AND userid = :userid";
            $DB->execute($sql, array('status' => $status, 'now' => time(), 'enrolid' => $instance->id, 'userid' => $userid));
        }

        // Traite le cas où on souhaite juste modifier le rôle.
        if ($roleid !== null) {
            $coursecontext = context_course::instance($instance->courseid);

            $sql = "UPDATE {role_assignments}".
                " SET roleid = :roleid, timemodified = :now".
                " WHERE component = 'enrol_select'".
                " AND userid = :userid".
                " AND contextid = :contextid".
                " AND itemid= :itemid";
            $params = array('roleid' => $roleid, 'now' => time(), 'userid' => $userid,
                'contextid' => $coursecontext->id, 'itemid' => $instance->id);
            $DB->execute($sql, $params);
        }
    }

    /**
     * Méthode permettant de déinscrire un utilisateur d'une instance.
     *
     * @param stdClass   $instance      Objet de l'instance de la méthode d'inscription.
     * @param int|string $userid        Identifiant d'un utilisateur.
     *
     * @return void.
     */
    public function unenrol_user(stdClass $instance, $userid) {
        global $DB;

        parent::unenrol_user($instance, $userid);

        // Si la remontée de liste est activée.
        if (empty($instance->customchar2) === false) {
            $this->refill_main_list($instance, $userid);
        }

        // Si les paiements à l'inscription sont activées.
        if (empty($instance->customdec1) === false) {
            // On supprime la tâche adhoc associée à l'utilisateur.
            $classname = '\enrol_select\task\check_enrolment_payment';
            $params = ['component' => 'enrol_select', 'classname' => $classname, 'userid' => $userid];
            $tasks = $DB->get_records('task_adhoc', $params);
            foreach ($tasks as $taskid => $task) {
                $customdata = json_decode($task->customdata);
                if (isset($customdata->enrolid) === false) {
                    continue;
                }

                if ($customdata->enrolid !== $instance->id) {
                    continue;
                }

                $DB->delete_records('task_adhoc', ['id' => $taskid]);
            }
        }
    }

    /**
     * Réorganise la liste d'inscription principale.
     *
     * @param stdClass   $instance      Objet de l'instance de la méthode d'inscription.
     * @param int|string $userid        Identifiant d'un utilisateur.
     *
     * @return void.
     */
    public function refill_main_list(stdClass $instance, $userid) {
        global $DB, $USER;

        if ($this->is_enrol_period_active($instance) === false) {
            // On ne réalimente pas la liste principale si les inscriptions sont closes.
            return;
        }

        if ($USER->id !== $userid) {
            // L'utilisateur courant n'est pas l'utilisateur à désinscrire.
            // Il s'agit probablement d'un enseignant dans la partie management.
            // On ne provoque pas la réalimentation de la liste principale.
            return;
        }

        if (empty($instance->customint3) === true) {
            // Les quotas ne sont pas activés pour ce cours.
            return;
        }

        $sql = "SELECT COUNT(*) FROM {user_enrolments} WHERE enrolid = :enrolid AND status IN (:main, :accepted)";
        $params = array('enrolid' => $instance->id, 'main' => self::MAIN, 'accepted' => self::ACCEPTED);
        $countmain = $DB->count_records_sql($sql, $params);
        if ($countmain >= $instance->customint1) {
            // La liste principale (et des acceptés) est déjà pleine.
            return;
        }

        $countwait = $DB->count_records('user_enrolments', array('enrolid' => $instance->id, 'status' => self::WAIT));
        if ($countwait === 0) {
            // La liste complémentaire est vide.
            return;
        }

        // Détermine le nombre d'inscription à transférer de la liste complémentaire à la liste principale.
        $promote = $instance->customint1 - $countmain;

        // Récupère les utilisateurs sur liste complémentaire par ordre d'inscription.
        $params = array('enrolid' => $instance->id, 'status' => self::WAIT);
        $waitingusers = $DB->get_records('user_enrolments', $params, $sort = 'timecreated ASC');

        $course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
        foreach ($waitingusers as $user) {
            $user->status = self::MAIN;
            $DB->update_record('user_enrolments', $user);

            // Notifie l'utilisateur sur liste d'attente qui vient d'être basculé sur liste principale.
            $eventdata = new \core\message\message();
            $eventdata->courseid = $course->id;
            $eventdata->component = 'enrol_select';
            $eventdata->name = 'select_notification';
            $eventdata->userfrom = core_user::get_noreply_user();
            $eventdata->userto = $DB->get_record('user', array('id' => $user->userid));
            $eventdata->subject = get_string('enrolcoursesubject', 'enrol_select', $course);
            $eventdata->fullmessage = get_string('message_promote', 'enrol_select');
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = '';
            $eventdata->smallmessage = '';
            $eventdata->notification = 1;

            message_send($eventdata);

            $promote--;
            if ($promote === 0) {
                break;
            }
        }
    }
}
