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
 * Unit tests for search API code.
 *
 * @package mod_oublog
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/oublog/tests/oublog_test_lib.php');
require_once($CFG->dirroot . '/mod/oublog/locallib.php');
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');

use \mod_oublog\search\post;
use \mod_oublog\search\comments;

 /**
  * Test case for generic functions in classes/search/ where covered.
  *
  * @package mod_oublog
  * @copyright 2017 The Open University
  * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */
class oublog_search_test extends oublog_test_lib {

    /**
     * Tests get_recordset_by_timestamp function (obtains modified document pages) and get_document
     * function (converts them into the format the search system wants).
     */
    public function test_post_search_index() {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        $search = testable_core_search::instance();

        // First check there are no results with empty database.
        $page = new post();
        $rs = $page->get_recordset_by_timestamp();
        $this->assertCount(0, self::recordset_to_array($rs));

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');

        $course = $this->get_new_course();
        $etuser = $this->get_new_user('editingteacher', $course->id);
        $suser1 = $this->get_new_user('student', $course->id);
        $suser2 = $this->get_new_user('student', $course->id);
        $suser3 = $this->get_new_user('student', $course->id);
        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);
        $this->get_new_group_member($group1->id, $suser1->id);
        $this->get_new_group_member($group2->id, $suser2->id);
        $grouping = $this->get_new_grouping($course->id);
        $this->get_new_grouping_group($grouping->id, $group1->id);
        $this->get_new_grouping_group($grouping->id, $group2->id);

        // Test posts using standard course blog.
        $oublog = $this->get_new_oublog($course->id,
                array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS, 'groupmode' => SEPARATEGROUPS,
                        'tagslist' => 'blogtag1,blogtag2'));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        $titlecheck = 'test_oublog_get_posts';
        $messagecheck = 'test_oublog_mesage';

        // First make sure we have some posts to use.
        $post1stub = $this->get_post_stub($oublog->id);
        $post1stub->title = $titlecheck . '_1';
        $post1stub->message['text'] = $messagecheck . '_1';
        $post1stub->userid = $suser1->id;
        $post1stub->tags = 'blogtag1';
        $post1id = oublog_add_post($post1stub, $cm, $oublog, $course);

        // Get a list of the posts.
        $context = context_module::instance($cm->id);
        $results = self::recordset_to_array($page->get_recordset_by_timestamp());

        // Now check we get results.
        $this->assertCount(1, $results);

        // Check first one in detail using the get_document function.
        $out = $page->get_document($results[0], array('lastindexedtime' => 0));
        $this->assertEquals('test_oublog_get_posts_1', $out->get('title'));
        $this->assertEquals('test_oublog_mesage_1', $out->get('content'));
        $this->assertEquals('blogtag1', $out->get('description1'));
        $this->assertEquals($context->id, $out->get('contextid'));
        $this->assertEquals(\core_search\manager::TYPE_TEXT, $out->get('type'));
        $this->assertEquals($course->id, $out->get('courseid'));
        $this->assertEquals($post1id, $out->get('itemid'));
        $this->assertEquals(\core_search\manager::NO_OWNER_ID, $out->get('owneruserid'));
        $this->assertTrue($out->get_is_new());

        // Check access.
        $this->setUser($suser1);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($post1id));
        $this->setUser($suser2);
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $page->check_access($post1id));
        $this->setUser($etuser);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($post1id));

        // Check search result url.
        $url = $page->get_doc_url($out)->out(false);
        $this->assertEquals($CFG->wwwroot . '/mod/oublog/viewpost.php?post=' . $post1id, $url);

        // Check post attachment.
        $fs = get_file_storage();
        $filerecord = array(
            'contextid' => $context->id,
            'component' => 'mod_oublog',
            'filearea'  => post::FILEAREA['ATTACHMENT'],
            'itemid'    => $post1id,
            'filepath'  => '/',
            'filename'  => 'file1.txt'
        );
        $file1 = $fs->create_file_from_string($filerecord, 'File 1 content');

        $filerecord = array(
            'contextid' => $context->id,
            'component' => 'mod_oublog',
            'filearea'  => post::FILEAREA['MESSAGE'],
            'itemid'    => $post1id,
            'filepath'  => '/',
            'filename'  => 'file2.txt'
        );
        $file2 = $fs->create_file_from_string($filerecord, 'File 2 content');

        $oublogpostareaid = \core_search\manager::generate_areaid('mod_oublog', 'post');
        $searcharea = \core_search\manager::get_search_area($oublogpostareaid);

        $this->assertCount(0, $out->get_files());
        $searcharea->attach_files($out);
        $files = $out->get_files();
        $this->assertCount(4, $files);
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            switch ($file->get_filearea()) {

                case post::FILEAREA['ATTACHMENT']:
                    $this->assertEquals('file1.txt', $file->get_filename());
                    $this->assertEquals('File 1 content', $file->get_content());
                    break;

                case post::FILEAREA['MESSAGE']:
                    $this->assertEquals('file2.txt', $file->get_filename());
                    $this->assertEquals('File 2 content', $file->get_content());
                    break;

                default:
                    break;
            }
        }

        // Create a second blog with one post.
        $otherblog = $this->getDataGenerator()->create_module('oublog', ['course' => $course->id]);
        $post = $this->get_post_stub($otherblog->id);
        oublog_add_post($post, $cm, $oublog, $course);
        $otherblogcontext = context_module::instance($otherblog->cmid);

        // Test get_document_recordset with and without context.
        $results = self::recordset_to_array($page->get_document_recordset(0));
        $this->assertCount(2, $results);
        $results = self::recordset_to_array($page->get_document_recordset(0, $otherblogcontext));
        $this->assertCount(1, $results);
    }

    /**
     * Tests that the get_recordset_by_timestamp function works correctly when there are some
     * edited posts.
     */
    public function test_post_search_index_with_editing() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $oublog = $generator->create_module('oublog', ['course' => $course->id]);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($oublog->cmid);

        // Add 5 posts with time 10, 20, 30, 40, 50.
        $postids = [];
        for ($timeposted = 10; $timeposted <= 50; $timeposted += 10) {
            $post = $this->get_post_stub($oublog->id);
            $post->timeposted = $timeposted;
            $postids[$timeposted] = oublog_add_post($post, $cm, $oublog, $course);
        }

        // Edit the post at time 20 so that it has time 60, and the post at time 30 so it has time 45.
        $DB->set_field('oublog_posts', 'timeupdated', 60, ['id' => $postids[20]]);
        $DB->set_field('oublog_posts', 'timeupdated', 45, ['id' => $postids[30]]);

        // Get a list of the posts and check the order.
        $area = new post();
        $this->assertEquals([$postids[10], $postids[40], $postids[30], $postids[50], $postids[20]],
                self::recordset_to_ids($area->get_recordset_by_timestamp(), 'postid'));

        // List starting from time 45.
        $this->assertEquals([$postids[30], $postids[50], $postids[20]],
                self::recordset_to_ids($area->get_recordset_by_timestamp(45), 'postid'));

        // Get a list starting from time 61 (no results).
        $this->assertEquals([],
                self::recordset_to_ids($area->get_recordset_by_timestamp(61), 'postid'));
    }

    /**
     * Turns a recordset into an array of ids and closes it.
     *
     * @param moodle_recordset $rs Recordset
     * @param string $field Name of id field
     * @return array Array of ids
     */
    protected static function recordset_to_ids(moodle_recordset $rs, $field = 'id') {
        $result = [];
        foreach ($rs as $rec) {
            $result[] = $rec->{$field};
        }
        $rs->close();
        return $result;
    }

    /**
     * Tests get_recordset_by_timestamp function (obtains modified document pages) and get_document
     * function (converts them into the format the search system wants).
     */
    public function test_comments_search_index() {
        global $CFG, $USER, $SITE, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        $search = testable_core_search::instance();

        // First check there are no results with empty database.
        $page = new comments();
        $rs = $page->get_recordset_by_timestamp();
        $this->assertCount(0, self::recordset_to_array($rs));

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');

        $course = $this->get_new_course();
        $suser1 = $this->get_new_user('student', $course->id);

        // Test posts using standard course blog.
        $oublog = $this->get_new_oublog($course->id,
                array('individual' => OUBLOG_NO_INDIVIDUAL_BLOGS, 'groupmode' => SEPARATEGROUPS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        // First make sure we have some posts to use.
        $post1stub = $this->get_post_stub($oublog->id);
        $post1stub->title = 'test_oublog_get_posts_1';
        $post1stub->message['text'] = 'test_oublog_mesage_1';
        $post1stub->userid = $suser1->id;
        $post1id = oublog_add_post($post1stub, $cm, $oublog, $course);

        // Add new comment for post.
        $comment1 = new stdClass();
        $comment1->title = 'Test Seach Comment 1';
        $comment1->messagecomment = array();
        $comment1->messagecomment['text'] = 'Message for test comment 1';
        $comment1->timeposted = 1;
        $comment1->postid = $post1id;
        $comment1->userid = $USER->id;
        $comment1id = oublog_add_comment($SITE, $cm, $oublog, $comment1);
        $comment2 = new stdClass();
        $comment2->title = 'Test Seach Comment 2';
        $comment2->messagecomment = array();
        $comment2->messagecomment['text'] = 'Message for test comment 2';
        $comment2->timeposted = 2;
        $comment2->postid = $post1id;
        $comment2->userid = $USER->id;
        $comment2id = oublog_add_comment($SITE, $cm, $oublog, $comment2);

        // Get a list of the posts.
        $context = context_module::instance($cm->id);
        $results = self::recordset_to_array($page->get_recordset_by_timestamp());

        // Now check we get results.
        $this->assertCount(2, $results);

        // Check first one in detail using the get_document function.
        $out = $page->get_document($results[0], array('lastindexedtime' => 0));
        $this->assertEquals('Test Seach Comment 1', $out->get('title'));
        $this->assertEquals('Message for test comment 1', $out->get('content'));
        $this->assertEquals($context->id, $out->get('contextid'));
        $this->assertEquals(\core_search\manager::TYPE_TEXT, $out->get('type'));
        $this->assertEquals($course->id, $out->get('courseid'));
        $comment = $DB->get_record('oublog_comments', array('id' => $out->get('itemid')));
        $post = oublog_get_post($comment->postid);
        $this->assertCount(2, $post->comments);
        $this->assertTrue(isset($post->comments[$comment1id]));
        $this->assertEquals(\core_search\manager::NO_OWNER_ID, $out->get('owneruserid'));
        $this->assertTrue($out->get_is_new());

        // Check access.
        $this->setUser($suser1);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($comment1id));

        // Check search result url.
        $url = $page->get_doc_url($out)->out(false);
        $this->assertEquals($CFG->wwwroot . '/mod/oublog/viewpost.php?post=' . $post1id.'#cid'.$comment1id, $url);

        // Check comment attachment.
        $fs = get_file_storage();
        $filerecord = array(
            'contextid' => $context->id,
            'component' => 'mod_oublog',
            'filearea'  => comments::FILEAREA['MESSAGE'],
            'itemid'    => $post1id,
            'filepath'  => '/',
            'filename'  => 'file2.txt'
        );
        $file2 = $fs->create_file_from_string($filerecord, 'File 2 content');

        $oublogpostareaid = \core_search\manager::generate_areaid('mod_oublog', 'comments');
        $searcharea = \core_search\manager::get_search_area($oublogpostareaid);

        $this->assertCount(0, $out->get_files());
        $searcharea->attach_files($out);
        $files = $out->get_files();
        $this->assertCount(0, $files);
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            switch ($file->get_filearea()) {
                case comments::FILEAREA['MESSAGE']:
                    $this->assertEquals('file2.txt', $file->get_filename());
                    $this->assertEquals('File 2 content', $file->get_content());
                    break;

                default:
                    break;
            }
        }

        // Create a second blog with one post and one comment.
        $otherblog = $this->getDataGenerator()->create_module('oublog', ['course' => $course->id]);
        $post = $this->get_post_stub($otherblog->id);
        $otherpostid = oublog_add_post($post, $cm, $oublog, $course);
        $otherblogcontext = context_module::instance($otherblog->cmid);
        $comment = new stdClass();
        $comment->title = 'Test Seach Comment Other';
        $comment->messagecomment = [];
        $comment->messagecomment['text'] = 'Message for test comment Other';
        $comment->timeposted = 1;
        $comment->postid = $otherpostid;
        $comment->userid = $USER->id;
        oublog_add_comment($SITE, $cm, $oublog, $comment);

        // Test get_document_recordset with and without context.
        $results = self::recordset_to_array($page->get_document_recordset(0));
        $this->assertCount(3, $results);
        $results = self::recordset_to_array($page->get_document_recordset(0, $otherblogcontext));
        $this->assertCount(1, $results);
    }

    /**
     * Converts recordset to array, indexed numberically (0, 1, 2).
     *
     * @param moodle_recordset $rs Record set to convert
     * @return \stdClass[] Array of converted records
     */
    protected static function recordset_to_array(moodle_recordset $rs) {
        $result = array();
        foreach ($rs as $rec) {
            $result[] = $rec;
        }
        $rs->close();
        return $result;
    }

    public function test_oublog_posts_for_group_support() {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        $search = testable_core_search::instance();

        $searcharea = new post();
        $course = $this->get_new_course();
        $suser1 = $this->get_new_user('student', $course->id);
        $group1 = $this->get_new_group($course->id);
        $this->get_new_group_member($group1->id, $suser1->id);

        // Create an OUBlog.
        $oublog1 = $this->get_new_oublog($course->id,
                ['individual' => OUBLOG_NO_INDIVIDUAL_BLOGS, 'groupmode' => SEPARATEGROUPS, 'tagslist' => 'blogtag1,blogtag2']);

        $cm1 = get_coursemodule_from_id('oublog', $oublog1->cmid);

        $titlecheck = 'test_oublog_get_posts';
        $messagecheck = 'test_oublog_mesage';

        $post1stub = $this->get_post_stub($oublog1->id);
        $post1stub->title = $titlecheck . '_1';
        $post1stub->message['text'] = $messagecheck . '_1';
        $post1stub->userid = $suser1->id;
        $post1stub->tags = 'blogtag1';
        $post1stub->groupid = $group1->id;
        $post1id = oublog_add_post($post1stub, $cm1, $oublog1, $course);

        // Create an OUBlog without groups.
        $oublog2 = $this->get_new_oublog($course->id,
                ['individual' => OUBLOG_NO_INDIVIDUAL_BLOGS, 'groupmode' => NOGROUPS, 'tagslist' => 'blogtag1,blogtag2']);
        $cm2 = get_coursemodule_from_id('oublog', $oublog2->cmid);

        $post2stub = $this->get_post_stub($oublog2->id);
        $post2stub->title = 'No groups';
        $post2stub->userid = $suser1->id;
        $post2stub->tags = 'blogtag1';
        $post2id = oublog_add_post($post2stub, $cm2, $oublog2, $course);

        // OU Blog with Individual blogs and No groups.
        $oublog3 = $this->get_new_oublog($course->id,
                ['individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS, 'groupmode' => VISIBLEGROUPS, 'tagslist' => 'blogtag1,blogtag2']);
        $cm3 = get_coursemodule_from_id('oublog', $oublog3->cmid);
        $post3stub = $this->get_post_stub($oublog3->id);
        $post3stub->title = 'Individual blogs';
        $post3stub->userid = $suser1->id;
        $post3stub->groupid = 0;
        $post3id = oublog_add_post($post3stub, $cm3, $oublog3, $course);

        // Get a list of the posts.
        $results = self::recordset_to_array($searcharea->get_recordset_by_timestamp());

        // Now check results.
        $this->assertCount(3, $results);

        // Check detail using the get_document function.
        $out1 = $searcharea->get_document($results[0], array('lastindexedtime' => 0));
        $this->assertEquals('test_oublog_get_posts_1', $out1->get('title'));
        $this->assertTrue($out1->is_set('groupid'));
        $this->assertEquals($group1->id, $out1->get('groupid'));
        $this->assertTrue($out1->get_is_new());

        $out2 = $searcharea->get_document($results[1], array('lastindexedtime' => 0));
        $this->assertEquals('No groups', $out2->get('title'));
        $this->assertFalse($out2->is_set('groupid'));
        $this->assertTrue($out2->get_is_new());

        $out3 = $searcharea->get_document($results[2], array('lastindexedtime' => 0));
        $this->assertEquals('Individual blogs', $out3->get('title'));
        $this->assertFalse($out3->is_set('groupid'));
        $this->assertTrue($out3->get_is_new());
    }
}
