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
 *
 * @throws moodle_exception If something goes wrong.
 *
 * @param int $oldversion Numéro courant de la version du plugin installé.
 *
 * @return true If success.
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
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, $notnull = false, null, null, null);
            $table->add_field('maxwish', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull = false, null, null, null);
            $table->add_field('minregister', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull = false, null, null, null);
            $table->add_field('maxregister', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull = false, null, null, null);
            $table->add_field('userprice', XMLDB_TYPE_FLOAT, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
            $table->add_field('institutionprice', XMLDB_TYPE_FLOAT, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
            $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull = false, null, null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            // Create table.
            $dbman->create_table($table);
        }

        $table = new xmldb_table('apsolu_colleges_members');

        // If the table does not exist, create it along with its fields.
        if (!$dbman->table_exists($table)) {
            // Adding fields.
            $table->add_field('collegeid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull = false, null, null, null);
            $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, $notnull = false, null, null, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['collegeid', 'cohortid']);

            // Create table.
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'enrol', 'select');
    }

    $version = 2017030100;
    if ($oldversion < $version) {
        // Add missing indexes !
        $tables = [];
        $tables['apsolu_colleges'] = ['roleid'];
        $tables['apsolu_colleges_members'] = ['collegeid', 'cohortid'];
        $tables['enrol_select_roles'] = ['enrolid', 'roleid'];
        $tables['enrol_select_cohorts'] = ['enrolid', 'cohortid'];
        $tables['enrol_select_cohorts_roles'] = ['roleid', 'cohortid'];

        foreach ($tables as $tablename => $indexes) {
            $table = new xmldb_table($tablename);
            foreach ($indexes as $indexname) {
                $index = new xmldb_index($indexname, XMLDB_INDEX_NOTUNIQUE, [$indexname]);

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
        $default = null;

        // Fix default value for apsolu_colleges.id.
        $table = new xmldb_table('apsolu_colleges');
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, $default, null);

        $dbman->change_field_default($table, $field);

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'enrol', 'select');
    }

    $version = 2018091700;
    if ($oldversion < $version) {
        $table = new xmldb_table('enrol_select_cards');
        if ($dbman->table_exists($table) === false) {
            $default = null;
            $sequence = null;

            // Adding fields.
            $table->add_field('enrolid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence, $default, null);
            $table->add_field('cardid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, $sequence, $default, null);

            // Adding key.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['enrolid', 'cardid']);

            // Create table.
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'enrol', 'select');
    }

    $version = 2022112100;
    if ($oldversion < $version) {
        // Nettoie les tables faisant référence à des cohortes supprimées.
        $queries = [];
        $queries[] = "DELETE FROM {apsolu_colleges_members} WHERE cohortid NOT IN (SELECT id FROM {cohort})";
        $queries[] = "DELETE FROM {enrol_select_cohorts} WHERE cohortid NOT IN (SELECT id FROM {cohort})";
        $queries[] = "DELETE FROM {enrol_select_cohorts_roles} WHERE cohortid NOT IN (SELECT id FROM {cohort})";
        foreach ($queries as $sql) {
            $DB->execute($sql);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'enrol', 'select');
    }

    $version = 2025031900;
    if ($oldversion < $version) {
        $queries = [];
        // Nettoie les tables faisant référence à des méthodes d'inscription supprimées.
        $queries[] = "DELETE FROM {enrol_select_cards} WHERE enrolid NOT IN (SELECT id FROM {enrol})";
        $queries[] = "DELETE FROM {enrol_select_cohorts} WHERE enrolid NOT IN (SELECT id FROM {enrol})";
        $queries[] = "DELETE FROM {enrol_select_roles} WHERE enrolid NOT IN (SELECT id FROM {enrol})";
        // Nettoie les tables faisant référence à des roles supprimés.
        $queries[] = "DELETE FROM {enrol_select_roles} WHERE roleid NOT IN (SELECT id FROM {role})";
        $queries[] = "DELETE FROM {enrol_select_cohorts_roles} WHERE roleid NOT IN (SELECT id FROM {role})";
        foreach ($queries as $sql) {
            $DB->execute($sql);
        }

        // Ajoute un champ 'id' sur la table 'enrol_select_roles'.
        $tablename = 'enrol_select_roles';

        $table = new xmldb_table($tablename);
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, $precision = '10', $unsigned = XMLDB_UNSIGNED, $notnull = XMLDB_NOTNULL,
            $sequence = XMLDB_SEQUENCE, $default = null, $previous = null);

        if ($dbman->field_exists($table, $field) === false) {
            // Renomme la table actuelle.
            $dbman->rename_table($table, $tablename.'tmp');

            // Ajoute la nouvelle table contenant la clé primaire 'id'.
            $table = new xmldb_table($tablename);

            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('enrolid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $table->add_index('enrolid', XMLDB_INDEX_NOTUNIQUE, ['enrolid']);
            $table->add_index('roleid', XMLDB_INDEX_NOTUNIQUE, ['roleid']);
            $table->add_index('enrolidroleid', XMLDB_INDEX_UNIQUE, ['enrolid', 'roleid']);

            $dbman->create_table($table);

            // Récupère le contenu de l'ancienne table et l'injecte dans la nouvelle table.
            $id = 1;
            $recordset = $DB->get_recordset($tablename.'tmp');
            foreach ($recordset as $record) {
                $DB->insert_record($tablename, $record);
            }
            $recordset->close();

            // Supprime l'ancienne table.
            $table = new xmldb_table($tablename.'tmp');
            $dbman->drop_table($table);
        }

        // Ajoute un champ 'id' sur la table 'enrol_select_cohorts'.
        $tablename = 'enrol_select_cohorts';

        $table = new xmldb_table($tablename);
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, $precision = '10', $unsigned = XMLDB_UNSIGNED, $notnull = XMLDB_NOTNULL,
            $sequence = XMLDB_SEQUENCE, $default = null, $previous = null);

        if ($dbman->field_exists($table, $field) === false) {
            // Renomme la table actuelle.
            $dbman->rename_table($table, $tablename.'tmp');

            // Ajoute la nouvelle table contenant la clé primaire 'id'.
            $table = new xmldb_table($tablename);

            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('enrolid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $table->add_index('enrolid', XMLDB_INDEX_NOTUNIQUE, ['enrolid']);
            $table->add_index('cohortid', XMLDB_INDEX_NOTUNIQUE, ['cohortid']);
            $table->add_index('enrolidcohortid', XMLDB_INDEX_UNIQUE, ['enrolid', 'cohortid']);

            $dbman->create_table($table);

            // Récupère le contenu de l'ancienne table et l'injecte dans la nouvelle table.
            $id = 1;
            $recordset = $DB->get_recordset($tablename.'tmp');
            foreach ($recordset as $record) {
                $DB->insert_record($tablename, $record);
            }
            $recordset->close();

            // Supprime l'ancienne table.
            $table = new xmldb_table($tablename.'tmp');
            $dbman->drop_table($table);
        }

        // Ajoute un champ 'id' sur la table 'enrol_select_cohorts_roles'.
        $tablename = 'enrol_select_cohorts_roles';

        $table = new xmldb_table($tablename);
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, $precision = '10', $unsigned = XMLDB_UNSIGNED, $notnull = XMLDB_NOTNULL,
            $sequence = XMLDB_SEQUENCE, $default = null, $previous = null);

        if ($dbman->field_exists($table, $field) === false) {
            // Renomme la table actuelle.
            $dbman->rename_table($table, $tablename.'tmp');

            // Ajoute la nouvelle table contenant la clé primaire 'id'.
            $table = new xmldb_table($tablename);

            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $table->add_index('roleid', XMLDB_INDEX_NOTUNIQUE, ['roleid']);
            $table->add_index('cohortid', XMLDB_INDEX_NOTUNIQUE, ['cohortid']);
            $table->add_index('roleidcohortid', XMLDB_INDEX_UNIQUE, ['roleid', 'cohortid']);

            $dbman->create_table($table);

            // Récupère le contenu de l'ancienne table et l'injecte dans la nouvelle table.
            $id = 1;
            $recordset = $DB->get_recordset($tablename.'tmp');
            foreach ($recordset as $record) {
                $DB->insert_record($tablename, $record);
            }
            $recordset->close();

            // Supprime l'ancienne table.
            $table = new xmldb_table($tablename.'tmp');
            $dbman->drop_table($table);
        }

        // Ajoute un champ 'id' sur la table 'enrol_select_cards'.
        $tablename = 'enrol_select_cards';

        $table = new xmldb_table($tablename);
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, $precision = '10', $unsigned = XMLDB_UNSIGNED, $notnull = XMLDB_NOTNULL,
            $sequence = XMLDB_SEQUENCE, $default = null, $previous = null);

        if ($dbman->field_exists($table, $field) === false) {
            // Renomme la table actuelle.
            $dbman->rename_table($table, $tablename.'tmp');

            // Ajoute la nouvelle table contenant la clé primaire 'id'.
            $table = new xmldb_table($tablename);

            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('enrolid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('cardid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $table->add_index('enrolid', XMLDB_INDEX_NOTUNIQUE, ['enrolid']);
            $table->add_index('cardid', XMLDB_INDEX_NOTUNIQUE, ['cardid']);
            $table->add_index('enrolidcardid', XMLDB_INDEX_UNIQUE, ['enrolid', 'cardid']);

            $dbman->create_table($table);

            // Récupère le contenu de l'ancienne table et l'injecte dans la nouvelle table.
            $id = 1;
            $recordset = $DB->get_recordset($tablename.'tmp');
            foreach ($recordset as $record) {
                $DB->insert_record($tablename, $record);
            }
            $recordset->close();

            // Supprime l'ancienne table.
            $table = new xmldb_table($tablename.'tmp');
            $dbman->drop_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'enrol', 'select');
    }

    // Modification à appliquer lors de la prochaine mise à jour.
    if (false) {
        // Savepoint reached.
        upgrade_plugin_savepoint(true, $version, 'enrol', 'select');
    }

    return true;
}
