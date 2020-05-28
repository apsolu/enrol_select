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
 * Event to log user movements.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_select\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event to log user movements.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_moved extends \core\event\base {
    /**
     * Set all required data properties:
     *  1/ crud - letter [crud]
     *  2/ edulevel - using a constant self::LEVEL_*.
     *  3/ objecttable - name of database table if objectid specified
     *
     * Optionally it can set:
     * a/ fixed system context
     *
     * @return void
     */
    protected function init() {
        // Values: c(reate), r(ead), u(pdate) or d(elete).
        $this->data['crud'] = 'u';

        // Values: LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER.
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_user_moved', 'enrol_select');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "User #{$this->userid} moved user #{$this->relateduserid} to status '{$this->other['status']}'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('enrol/select/manage.php', array('enrolid' => 'value'));
    }

    /**
     * Doest this event replace add_to_log() statement?
     *
     * Note: do not use directly!
     *
     * @return null|array of parameters to be passed to legacy add_to_log() function.
     */
    public function get_legacy_logdata() {
        // Override if you are migrating an add_to_log() call.
        return array($this->courseid, 'enrol_select', 'moved', '...........', $this->objectid, $this->contextinstanceid);
    }

    /**
     * Legacy event data if get_legacy_eventname() is not empty.
     *
     * Note: do not use directly!
     *
     * @return mixed
     */
    protected function get_legacy_eventdata() {
        // Override if you migrating events_trigger() call.
        $data = new \stdClass();
        $data->id = $this->objectid;
        $data->userid = $this->relateduserid;
        return $data;
    }
}
