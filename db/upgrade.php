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

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

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
        // Correct log table entries for oublog.
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

    if ($oldversion < 2013010800) {
        // Rename field summary on table oublog to intro
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('summary', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'name');

        // Launch rename field summary
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'intro');
        }

        // oublog savepoint reached
        upgrade_mod_savepoint(true, 2013010800, 'oublog');
    }

    if ($oldversion < 2013010801) {
        // Define field introformat to be added to oublog
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'intro');

        // Launch add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // conditionally migrate to html format in intro
        if ($CFG->texteditors !== 'textarea') {
            $rs = $DB->get_recordset('oublog', array('introformat' => FORMAT_MOODLE), '', 'id, intro, introformat');
            foreach ($rs as $b) {
                $b->intro = text_to_html($b->intro, false, false, true);
                $b->introformat = FORMAT_HTML;
                $DB->update_record('oublog', $b);
                upgrade_set_timeout();
            }
            unset($b);
            $rs->close();
        }

        // oublog savepoint reached
        upgrade_mod_savepoint(true, 2013010801, 'oublog');
    }

    // Add reporting email(s) for OU Alert plugin use.
    if ($oldversion < 2013101000) {
        // Define field maxbytes to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('reportingemail', XMLDB_TYPE_CHAR, '255',
                null, null, null, null, 'grade');

        // Conditionally launch add field maxbytes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // OUblog savepoint reached.
        upgrade_mod_savepoint(true, 2013101000, 'oublog');
    }

    if ($oldversion < 2013102800) {

        // Define field displayname to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('displayname', XMLDB_TYPE_CHAR, '255', null, null, null, null,
                'reportingemail');

        // Conditionally launch add field displayname.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Oublog savepoint reached.
        upgrade_mod_savepoint(true, 2013102800, 'oublog');
    }

    if ($oldversion < 2013102801) {

        // Define field statblockon to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('statblockon', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'displayname');

        // Conditionally launch add field statblockon.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2013102801, 'oublog');
    }

    if ($oldversion < 2013121100) {
        // Numerous keys and indexes added.
        // Define key oublog_posts_groupid_groups_fk (foreign) to be added to oublog_posts.
        $table = new xmldb_table('oublog_posts');
        $key = new xmldb_key('oublog_posts_groupid_groups_fk', XMLDB_KEY_FOREIGN, array('groupid'), 'groups', array('id'));

        // Launch add key oublog_posts_groupid_groups_fk.
        $dbman->add_key($table, $key);

        // Define index allowcomments (not unique) to be added to oublog_posts.
        $table = new xmldb_table('oublog_posts');
        $index = new xmldb_index('allowcomments', XMLDB_INDEX_NOTUNIQUE, array('allowcomments'));

        // Conditionally launch add index allowcomments.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index visibility (not unique) to be added to oublog_posts.
        $table = new xmldb_table('oublog_posts');
        $index = new xmldb_index('visibility', XMLDB_INDEX_NOTUNIQUE, array('visibility'));

        // Conditionally launch add index visibility.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index timeposted (not unique) to be added to oublog_comments.
        $table = new xmldb_table('oublog_comments');
        $index = new xmldb_index('timeposted', XMLDB_INDEX_NOTUNIQUE, array('timeposted'));

        // Conditionally launch add index timeposted.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Oublog savepoint reached.
        upgrade_mod_savepoint(true, 2013121100, 'oublog');
    }

    if ($oldversion < 2014012702) {

        // Define field allowimport to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('allowimport', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'statblockon');

        // Conditionally launch add field allowimport.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Oublog savepoint reached.
        upgrade_mod_savepoint(true, 2014012702, 'oublog');
    }

    if ($oldversion < 2014042400) {

        // Define field introonpost to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('introonpost', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'allowimport');

        // Conditionally launch add field introonpost.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Oublog savepoint reached.
        upgrade_mod_savepoint(true, 2014042400, 'oublog');
    }

    // Update oublog table, adding tags text field (can be null).
    if ($oldversion < 2014072501) {

        // Define field tags to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('tags', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'introonpost');
        // Conditionally launch add field introonpost.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Oublog savepoint reached.
        upgrade_mod_savepoint(true, 2014072501, 'oublog');
    }

    if ($oldversion < 2014102300) {
        // Define field assessed to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('assessed', XMLDB_TYPE_INTEGER, '10',
                        XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'tags');

        // Conditionally launch add field assessed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field assesstimestart to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('assesstimestart', XMLDB_TYPE_INTEGER, '10',
                        XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'assessed');

        // Conditionally launch add field assesstimestart.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field assesstimefinish to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('assesstimefinish', XMLDB_TYPE_INTEGER, '10',
                        XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'assesstimestart');

        // Conditionally launch add field assesstimefinish.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field scale to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('scale', XMLDB_TYPE_INTEGER, '10',
                        XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'assesstimefinish');

        // Conditionally launch add field scale.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field grading to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('grading', XMLDB_TYPE_INTEGER, '10',
                        XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'scale');

        // Conditionally launch add field grading.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Set the new grading field to 1 for everything that isn't a default grade.
        $DB->set_field_select('oublog', 'grading', 1, 'grade != 0');

        // OUblog savepoint reached.
        upgrade_mod_savepoint(true, 2014102300, 'oublog');
    }

    if ($oldversion < 2014122400) {
        // Define field restricttags to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('restricttags', XMLDB_TYPE_INTEGER, '10',
                        XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'grading');

        // Conditionally launch add field restricttags.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // OUblog savepoint reached.
        upgrade_mod_savepoint(true, 2014122400, 'oublog');
    }

    if ($oldversion < 2015090800) {

        // Define field postfrom to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('postfrom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'restricttags');

        // Conditionally launch add field postfrom.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field postuntil to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('postuntil', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'postfrom');

        // Conditionally launch add field postuntil.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field commentfrom to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('commentfrom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'postuntil');

        // Conditionally launch add field commentfrom.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field commentuntil to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('commentuntil', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'commentfrom');

        // Conditionally launch add field commentuntil.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Oublog savepoint reached.
        upgrade_mod_savepoint(true, 2015090800, 'oublog');
    }

    if ($oldversion < 2015101501) {
        global $DB;

        // Fix grade set when grading off.
        $DB->set_field_select('oublog', 'grade', 0, 'grading = 0 and grade != 0');

        // Oublog savepoint reached.
        upgrade_mod_savepoint(true, 2015101501, 'oublog');
    }

    if ($oldversion < 2016081600) {

        // Rename field tags on table oublog to tagslist.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('tags', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'introonpost');

        // Launch rename field tags.
        $dbman->rename_field($table, $field, 'tagslist');

        // Oublog savepoint reached.
        upgrade_mod_savepoint(true, 2016081600, 'oublog');
    }

    if ($oldversion < 2017061600) {

        // Add timemodified field for applying global search to oublog activity.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            // Add the field but allowing nulls.
            $dbman->add_field($table, $field);
            // Set the field to 0 for everything.
            $DB->set_field('oublog', 'timemodified', '0');
            // Changing nullability of field timemodified to not null.
            $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, null);
            // Launch change of nullability for field themetype.
            $dbman->change_field_notnull($table, $field);
        }

        // Oublog savepoint reached.
        upgrade_mod_savepoint(true, 2017061600, 'oublog');
    }

    if ($oldversion < 2018031300) {
        // Add postperpage field setting.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('postperpage', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '25', 'timemodified');

        // Conditionally launch add field postperpage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Oublog savepoint reached.
        upgrade_mod_savepoint(true, 2018031300, 'oublog');
    }

    if ($oldversion < 2018032001) {
        // Define field idsharedblog to be added to oublog.
        $table = new xmldb_table('oublog');
        $field = new xmldb_field('idsharedblog', XMLDB_TYPE_CHAR, '100',
            null, null, null, null, 'postperpage');

        // Conditionally launch add field idsharedblog.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // OUblog savepoint reached.
        upgrade_mod_savepoint(true, 2018032001, 'oublog');
    }

    return true;
}
