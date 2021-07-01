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

namespace mod_oublog;

/**
 * Tests the tool_datamasking class for this plugin.
 *
 * @package mod_oublog
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_datamasking_test extends \advanced_testcase {

    /**
     * Tests actual behaviour of the masking applied in this plugin.
     */
    public function test_behaviour(): void {
        global $DB;

        $this->resetAfterTest();

        // Delete existing table data to make testing easier.
        $DB->delete_records('oublog');

        // Set up data to be masked.
        $generator = $this->getDataGenerator();
        $user = $generator->create_user(['firstname' => 'Anne', 'lastname' => 'Other']);

        $oublogid1 = $DB->insert_record('oublog', ['name' => '',
                'timemodified' => 0, 'reportingemail' => 'secret@example.org']);
        $DB->insert_record('oublog', ['name' => '', 'timemodified' => 0]);

        $DB->insert_record('oublog_comments', ['postid' => 0, 'title' => 'Q', 'message' => 'Q.',
                'timeposted' => 0, 'authorname' => 'Sally Secret']);
        $DB->insert_record('oublog_comments', ['postid' => 0, 'title' => '', 'message' => '',
                'timeposted' => 0]);

        $DB->insert_record('oublog_comments_moderated', ['postid' => 0, 'title' => 'Q', 'message' => 'Q.',
                'timeposted' => 0, 'authorname' => 'Sally Secret']);
        $DB->insert_record('oublog_comments_moderated', ['postid' => 0, 'title' => '', 'message' => '',
                'timeposted' => 0]);

        $DB->insert_record('oublog_edits', ['postid' => 0, 'userid' => 0, 'oldtitle' => 'Q',
                'oldmessage' => 'Q.', 'timeupdated' => 0]);
        $DB->insert_record('oublog_edits', ['postid' => 0, 'userid' => 0, 'oldtitle' => '',
                'oldmessage' => '', 'timeupdated' => 0]);

        $DB->insert_record('oublog_instances', ['oublogid' => 0, 'userid' => $user->id,
                'name' => 'Steve\'s blog', 'accesstoken' => '', 'summary' => 'Q.']);
        $DB->insert_record('oublog_instances', ['oublogid' => 0, 'userid' => $user->id + 1,
                'name' => 'Tulla\'s blog', 'accesstoken' => '']);
        $DB->insert_record('oublog_instances', ['oublogid' => 0,
                'name' => 'Some other pattern', 'accesstoken' => '']);

        $DB->insert_record('oublog_posts', ['oubloginstancesid' => 0, 'title' => 'Q', 'message' => 'Q.',
                'timeposted' => 0]);
        $DB->insert_record('oublog_posts', ['oubloginstancesid' => 0, 'title' => '', 'message' => '',
                'timeposted' => 0]);

        // Add some files.
        $fileids = [];
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_oublog', 'attachment',
                'a.txt', 'a');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_oublog', 'edit',
                'b.txt', 'bb');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_oublog', 'message',
                'c.txt', 'ccc');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_oublog', 'messagecomment',
                'd.txt', 'dddd');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_oublog', 'intro',
                'e.txt', 'eeeee');

        // Before checks.
        $oublogsql = 'SELECT reportingemail FROM {oublog} ORDER BY id';
        $this->assertEquals(['secret@example.org', null], $DB->get_fieldset_sql($oublogsql));
        $oublogcommentsauthornamesql = 'SELECT authorname FROM {oublog_comments} ORDER BY id';
        $this->assertEquals(['Sally Secret', null], $DB->get_fieldset_sql($oublogcommentsauthornamesql));
        $oublogcommentsmessagesql = 'SELECT message FROM {oublog_comments} ORDER BY id';
        $this->assertEquals(['Q.', ''], $DB->get_fieldset_sql($oublogcommentsmessagesql));
        $oublogcommentstitlesql = 'SELECT title FROM {oublog_comments} ORDER BY id';
        $this->assertEquals(['Q', ''], $DB->get_fieldset_sql($oublogcommentstitlesql));
        $oublogcommentsmoderatedauthornamesql = 'SELECT authorname FROM {oublog_comments_moderated} ORDER BY id';
        $this->assertEquals(['Sally Secret', null], $DB->get_fieldset_sql($oublogcommentsmoderatedauthornamesql));
        $oublogcommentsmoderatedmessagesql = 'SELECT message FROM {oublog_comments_moderated} ORDER BY id';
        $this->assertEquals(['Q.', ''], $DB->get_fieldset_sql($oublogcommentsmoderatedmessagesql));
        $oublogcommentsmoderatedtitlesql = 'SELECT title FROM {oublog_comments_moderated} ORDER BY id';
        $this->assertEquals(['Q', ''], $DB->get_fieldset_sql($oublogcommentsmoderatedtitlesql));
        $oublogeditsoldmessagesql = 'SELECT oldmessage FROM {oublog_edits} ORDER BY id';
        $this->assertEquals(['Q.', ''], $DB->get_fieldset_sql($oublogeditsoldmessagesql));
        $oublogeditsoldtitlesql = 'SELECT oldtitle FROM {oublog_edits} ORDER BY id';
        $this->assertEquals(['Q', ''], $DB->get_fieldset_sql($oublogeditsoldtitlesql));
        $oubloginstancesnamesql = 'SELECT name FROM {oublog_instances} ORDER BY id';
        $this->assertEquals(['Steve\'s blog', 'Tulla\'s blog', 'Some other pattern'],
                $DB->get_fieldset_sql($oubloginstancesnamesql));
        $oubloginstancessummarysql = 'SELECT summary FROM {oublog_instances} ORDER BY id';
        $this->assertEquals(['Q.', '', ''], $DB->get_fieldset_sql($oubloginstancessummarysql));
        $oublogpostsmessagesql = 'SELECT message FROM {oublog_posts} ORDER BY id';
        $this->assertEquals(['Q.', ''], $DB->get_fieldset_sql($oublogpostsmessagesql));
        $oublogpoststitlesql = 'SELECT title FROM {oublog_posts} ORDER BY id';
        $this->assertEquals(['Q', ''], $DB->get_fieldset_sql($oublogpoststitlesql));

        \tool_datamasking\testing_utils::check_file($this, $fileids[0], 'a.txt', 1);
        \tool_datamasking\testing_utils::check_file($this, $fileids[1], 'b.txt', 2);
        \tool_datamasking\testing_utils::check_file($this, $fileids[2], 'c.txt', 3);
        \tool_datamasking\testing_utils::check_file($this, $fileids[3], 'd.txt', 4);
        \tool_datamasking\testing_utils::check_file($this, $fileids[4], 'e.txt', 5);

        // Run the full masking plan including this plugin, but without requiring mapping tables.
        \tool_datamasking\api::get_plan()->execute([], [\tool_datamasking\tool_datamasking::TAG_SKIP_ID_MAPPING]);

        // After checks.
        $this->assertEquals(['email' . $oublogid1 . '@open.ac.uk.invalid', null], $DB->get_fieldset_sql($oublogsql));
        $this->assertEquals(['Masked User', null], $DB->get_fieldset_sql($oublogcommentsauthornamesql));
        $this->assertEquals(['X.', ''], $DB->get_fieldset_sql($oublogcommentsmessagesql));
        $this->assertEquals(['X', ''], $DB->get_fieldset_sql($oublogcommentstitlesql));
        $this->assertEquals(['Masked User', null], $DB->get_fieldset_sql($oublogcommentsmoderatedauthornamesql));
        $this->assertEquals(['X.', ''], $DB->get_fieldset_sql($oublogcommentsmoderatedmessagesql));
        $this->assertEquals(['X', ''], $DB->get_fieldset_sql($oublogcommentsmoderatedtitlesql));
        $this->assertEquals(['X.', ''], $DB->get_fieldset_sql($oublogeditsoldmessagesql));
        $this->assertEquals(['X', ''], $DB->get_fieldset_sql($oublogeditsoldtitlesql));
        // Get the user's new name (it got masked).
        $user = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals([$user->firstname . ' ' . $user->lastname .'\'s blog',
                'Masked User\'s blog', 'Masked blog name'],
                $DB->get_fieldset_sql($oubloginstancesnamesql));
        $this->assertEquals(['X.', '', ''], $DB->get_fieldset_sql($oubloginstancessummarysql));
        $this->assertEquals(['X.', ''], $DB->get_fieldset_sql($oublogpostsmessagesql));
        $this->assertEquals(['X', ''], $DB->get_fieldset_sql($oublogpoststitlesql));

        \tool_datamasking\testing_utils::check_file($this, $fileids[0], 'masked.txt', 224);
        \tool_datamasking\testing_utils::check_file($this, $fileids[1], 'masked.txt', 224);
        \tool_datamasking\testing_utils::check_file($this, $fileids[2], 'masked.txt', 224);
        \tool_datamasking\testing_utils::check_file($this, $fileids[3], 'masked.txt', 224);
        \tool_datamasking\testing_utils::check_file($this, $fileids[4], 'e.txt', 5);
    }
}
