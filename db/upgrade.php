<?php

// This file keeps track of upgrades to
// the oublog module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_oublog_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager(); /// loads ddl manager and xmldb classes

    if ($oldversion < 2012031500) {

        // Define field grade to be added to oublog
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10',
            XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'individual');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2012031500, 'oublog');
    }

    if ($oldversion < 2012052100) {
        //correct log table entries for oublog
        $rs = $DB->get_recordset_select('log',
                "module='participation' OR module='userparticipation'
                AND action='view' AND url LIKE '%participation.php%'");
        if ($rs->valid()) {
            foreach ($rs as $entry) {
                $entry->module = 'oublog';
                $DB->update_record('log', $entry);
            }
        }
        upgrade_mod_savepoint(true, 2012052100, 'oublog');
    }

    if ($oldversion < 2012061800) {
        // Define field maxbytes to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('maxbytes', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '512000', 'maxvisibility');

        // Conditionally launch add field maxbytes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field maxattachments to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('maxattachments', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '9', 'maxbytes');

        // Conditionally launch add field maxattachments.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // OUblog savepoint reached.
        upgrade_mod_savepoint(true, 2012061800, 'oublog');
    }

    if ($oldversion < 2012102301) {
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, '0', 'individual');
        // Redefining grade field (signed automatically in 2.3).
        $dbman->change_field_unsigned($table, $field);
        // OUblog savepoint reached.
        upgrade_mod_savepoint(true, 2012102301, 'oublog');
    }

    return true;
}
