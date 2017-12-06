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
 * Post installation hook for adding data.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade procedure.
 */
function xmldb_enrol_select_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    $version = 2016082900;
    if ($oldversion < $version) {
        $table = new xmldb_table('apsolu_colleges');

        // If the table does not exist, create it along with its fields.
        if (!$dbman->table_exists($table)) {
            // Adding fields.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $not_null = false, null, null, null);
            $table->add_field('maxwish', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, null, null);
            $table->add_field('minregister', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, null, null);
            $table->add_field('maxregister', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, null, null);
            $table->add_field('userprice', XMLDB_TYPE_FLOAT, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
            $table->add_field('institutionprice', XMLDB_TYPE_FLOAT, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
            $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_colleges_members');

        // If the table does not exist, create it along with its fields.
        if (!$dbman->table_exists($table)) {
            // Adding fields.
            $table->add_field('collegeid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, null, null);
            $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $not_null = false, null, null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('collegeid', 'cohortid'));

            // Create table.
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'enrol', 'select');
    }

    $version = 2017030100;
    if ($oldversion < $version) {
        // Add missing indexes !
        $tables = array();
        $tables['apsolu_colleges'] = array('roleid');
        $tables['apsolu_colleges_members'] = array('collegeid', 'cohortid');
        $tables['enrol_select_roles'] = array('enrolid', 'roleid');
        $tables['enrol_select_cohorts'] = array('enrolid', 'cohortid');
        $tables['enrol_select_cohorts_roles'] = array('roleid', 'cohortid');

        foreach ($tables as $tablename => $indexes) {
            $table = new xmldb_table($tablename);
            foreach ($indexes as $indexname) {
                $index = new xmldb_index($indexname, XMLDB_INDEX_NOTUNIQUE, array($indexname));

                if (!$dbman->index_exists($table, $index)) {
                    $dbman->add_index($table, $index);
                }
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'enrol', 'select');
    }

    $version = 2017120600;
    if ($oldversion < $version) {
        // Fix default value for apsolu_colleges.id.
        $table = new xmldb_table('apsolu_colleges');
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, $default = null, null);

        $dbman->change_field_default($table, $field);

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'enrol', 'select');
    }

    return true;
}
