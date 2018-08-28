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
 * Tests for the Privacy Provider API implementation.
 *
 * @package mod_oublog
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_oublog;

use core_privacy\local\request\approved_contextlist;
use mod_oublog\privacy\provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/rating/lib.php');
require_once($CFG->dirroot . '/mod/oublog/locallib.php');

/**
 * Tests for the Privacy Provider API implementation.
 *
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

    // Include the privacy helper trait for the ratings API.
    use \core_rating\phpunit\privacy_helper;

    /**
     * All tests make database changes.
     */
    public function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Check no privacy information if user has never posted in the blog.
     */
    public function test_get_data_no_data() {
        list ($course, $blog, $user, $postid) = $this->create_basic_setup();

        // Test that no contexts are retrieved for the current user.
        $contextlist = $this->get_contexts_for_userid($user->id, 'mod_oublog');
        $contexts = $contextlist->get_contextids();
        $this->assertCount(0, $contexts);

        // Exporting data for this context should return nothing except basic context info.
        $context = \context_module::instance($blog->cmid);
        $this->export_context_data_for_user($user->id, $context, 'mod_oublog');
        $writer = \core_privacy\local\request\writer::with_context($context);
        $data = $writer->get_data([]);
        $this->assertEquals('MyBlog', $data->name);
        $this->assertContains('My intro', $data->intro);
        $this->assertObjectNotHasAttribute('posts', $data);
        $this->assertObjectNotHasAttribute('edits', $data);
        $this->assertObjectNotHasAttribute('comments', $data);
        $this->assertObjectNotHasAttribute('links', $data);
        $this->assertEmpty($writer->get_all_metadata([]));
        $this->assertEmpty($writer->get_files([]));
    }

    /**
     * Creates a course with a blog and two users. The 'other' user has made a post to the blog.
     *
     * @return array Course, blog, user, and other user's post
     * @throws \coding_exception
     */
    protected function create_basic_setup() {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $blog = $generator->create_module('oublog', ['course' => $course->id,
                'name' => 'MyBlog', 'intro' => 'My intro', 'scale' => 100, 'assessed' => 1]);
        $user = $generator->create_user();
        $otheruser = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $generator->enrol_user($otheruser->id, $course->id, 'student');
        $bloggenerator = $generator->get_plugin_generator('mod_oublog');
        $this->setUser($otheruser);
        $postid = $bloggenerator->create_post($blog,
                ['title' => 'OtherTitle', 'message' => 'OtherMessage']);
        return [$course, $blog, $user, $postid, $otheruser];
    }

    public function test_get_data_post() {
        list ($course, $blog, $user, $postid) = $this->create_basic_setup();

        // Create a post from this user.
        $bloggenerator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        $this->setUser($user);
        $postid = $bloggenerator->create_post($blog, ['title' => 'Greeting',
                'message' => 'Hello frogs!', 'tags' => 'frogs,amphibians']);

        // Now we should get the context.
        $contextlist = $this->get_contexts_for_userid($user->id, 'mod_oublog');
        $contexts = $contextlist->get_contextids();
        $this->assertCount(1, $contexts);
        $context = \context::instance_by_id($contexts[0]);

        // Test data export.
        $this->export_context_data_for_user($user->id, $context, 'mod_oublog');
        $writer = \core_privacy\local\request\writer::with_context($context);
        $data = $writer->get_data([]);
        $this->assertEquals('MyBlog', $data->name);
        $this->assertCount(1, $data->posts);
        $this->assertEquals($postid, $data->posts[0]->postid);
        $this->assertEquals('Greeting', $data->posts[0]->title);
        $this->assertEquals('Hello frogs!', $data->posts[0]->message);
        $this->assertNull($data->posts[0]->groupid);
        $timelag = time() - strtotime($data->posts[0]->timeposted);
        $this->assertLessThan(120, $timelag);
        $this->assertGreaterThanOrEqual(0, $timelag);
        $this->assertEquals(1, $data->posts[0]->allowcomments);
        $this->assertEquals(OUBLOG_VISIBILITY_COURSEUSER, $data->posts[0]->visibility);
        $this->assertEquals('amphibians, frogs', $data->posts[0]->tags);
        $this->assertEquals('You', $data->posts[0]->author);

        $this->assertObjectNotHasAttribute('edits', $data);
        $this->assertObjectNotHasAttribute('comments', $data);
        $this->assertObjectNotHasAttribute('links', $data);
        $this->assertEmpty($writer->get_all_metadata([]));
        $this->assertEmpty($writer->get_files([]));
    }

    public function test_get_data_comment() {
        list ($course, $blog, $user, $postid) = $this->create_basic_setup();

        // Create a comment from this user on somebody else's post.
        $bloggenerator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        $this->setUser($user);
        $commentid = $bloggenerator->create_comment($blog,
                ['postid' => $postid, 'title' => 'Greeting',  'message' => 'Hello frogs!']);

        // Now we should get the context.
        $contextlist = $this->get_contexts_for_userid($user->id, 'mod_oublog');
        $contexts = $contextlist->get_contextids();
        $this->assertCount(1, $contexts);
        $context = \context::instance_by_id($contexts[0]);

        // Test data export.
        $this->export_context_data_for_user($user->id, $context, 'mod_oublog');
        $writer = \core_privacy\local\request\writer::with_context($context);
        $data = $writer->get_data([]);
        $this->assertEquals('MyBlog', $data->name);

        $this->assertEquals($postid, $data->comments[0]->postid);
        $this->assertEquals($commentid, $data->comments[0]->commentid);
        $this->assertEquals('You', $data->comments[0]->author);
        $this->assertEquals('Greeting', $data->comments[0]->title);
        $this->assertEquals('Hello frogs!', $data->comments[0]->message);
        $timelag = time() - strtotime($data->comments[0]->timeposted);
        $this->assertLessThan(120, $timelag);
        $this->assertGreaterThanOrEqual(0, $timelag);

        $this->assertObjectNotHasAttribute('posts', $data);
        $this->assertObjectNotHasAttribute('edits', $data);
        $this->assertObjectNotHasAttribute('links', $data);
        $this->assertEmpty($writer->get_all_metadata([]));
        $this->assertEmpty($writer->get_files([]));
    }

    public function test_get_data_link() {
        global $DB;

        // User links are in the global blog so let's use that one.
        $globalblog = $DB->get_record('oublog', []);

        // Create user account for test.
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        // Add them a couple of links in the global blog.
        $instanceid = oublog_add_bloginstance(
                $globalblog->id, $user->id, 'Personal blog', 'Personal summary');
        oublog_add_link((object)['oubloginstancesid' => $instanceid,
                'title' => 'First link', 'url' => 'http://1.example.com/']);
        oublog_add_link((object)['oubloginstancesid' => $instanceid,
                'title' => 'Second link', 'url' => 'http://2.example.com/']);

        // Now we should get the context.
        $contextlist = $this->get_contexts_for_userid($user->id, 'mod_oublog');
        $contexts = $contextlist->get_contextids();
        $this->assertCount(1, $contexts);
        $context = \context::instance_by_id($contexts[0]);

        // Test data export.
        $this->export_context_data_for_user($user->id, $context, 'mod_oublog');
        $writer = \core_privacy\local\request\writer::with_context($context);
        $data = $writer->get_data([]);
        $this->assertEquals('Personal blog', $data->blogname);
        $this->assertEquals('Personal summary', $data->blogsummary);

        $this->assertEquals('First link', $data->links[0]->title);
        $this->assertEquals('http://1.example.com/', $data->links[0]->url);
        $this->assertEquals('Second link', $data->links[1]->title);
        $this->assertEquals('http://2.example.com/', $data->links[1]->url);

        $this->assertObjectNotHasAttribute('posts', $data);
        $this->assertObjectNotHasAttribute('comments', $data);
        $this->assertObjectNotHasAttribute('edits', $data);
        $this->assertEmpty($writer->get_all_metadata([]));
        $this->assertEmpty($writer->get_files([]));
    }

    public function test_get_data_current_edit() {
        list ($course, $blog, $user, $postid) = $this->create_basic_setup();

        // Create an edit from this user on somebody else's post.
        $bloggenerator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        $this->setUser($user);
        $bloggenerator->create_post($blog,
                ['id' => $postid, 'title' => 'Change',  'message' => 'Different message']);

        // Now we should get the context.
        $contextlist = $this->get_contexts_for_userid($user->id, 'mod_oublog');
        $contexts = $contextlist->get_contextids();
        $this->assertCount(1, $contexts);
        $context = \context::instance_by_id($contexts[0]);

        // Test data export.
        $this->export_context_data_for_user($user->id, $context, 'mod_oublog');
        $writer = \core_privacy\local\request\writer::with_context($context);
        $data = $writer->get_data([]);
        $this->assertEquals('MyBlog', $data->name);

        $this->assertEquals($postid, $data->posts[0]->postid);
        $this->assertEquals('Somebody else', $data->posts[0]->author);
        $this->assertEquals('You', $data->posts[0]->lasteditedby);
        $this->assertEquals('Change', $data->posts[0]->title);
        $this->assertEquals('Different message', $data->posts[0]->message);
        $timelag = time() - strtotime($data->posts[0]->timeupdated);
        $this->assertLessThan(120, $timelag);
        $this->assertGreaterThanOrEqual(0, $timelag);

        $this->assertObjectNotHasAttribute('comments', $data);
        $this->assertObjectNotHasAttribute('edits', $data);
        $this->assertObjectNotHasAttribute('links', $data);
        $this->assertEmpty($writer->get_all_metadata([]));
        $this->assertEmpty($writer->get_files([]));
    }

    public function test_get_data_old_edit() {
        list ($course, $blog, $user, $postid) = $this->create_basic_setup();

        // Create an edit from this user on somebody else's post.
        $bloggenerator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        $this->setUser($user);
        $bloggenerator->create_post($blog,
                ['id' => $postid, 'title' => 'Change',  'message' => 'Different message']);

        // But now somebody else edits it.
        $this->setAdminUser();
        $bloggenerator->create_post($blog,
                ['id' => $postid, 'title' => 'Change2',  'message' => 'Different message2']);

        // Now we should get the context.
        $contextlist = $this->get_contexts_for_userid($user->id, 'mod_oublog');
        $contexts = $contextlist->get_contextids();
        $this->assertCount(1, $contexts);
        $context = \context::instance_by_id($contexts[0]);

        // Test data export.
        $this->export_context_data_for_user($user->id, $context, 'mod_oublog');
        $writer = \core_privacy\local\request\writer::with_context($context);
        $data = $writer->get_data([]);
        $this->assertEquals('MyBlog', $data->name);

        $this->assertEquals($postid, $data->edits[0]->postid);
        $this->assertEquals('You', $data->edits[0]->author);
        $this->assertEquals('Change', $data->edits[0]->title);
        $this->assertEquals('Different message', $data->edits[0]->message);
        $timelag = time() - strtotime($data->edits[0]->timeupdated);
        $this->assertLessThan(120, $timelag);
        $this->assertGreaterThanOrEqual(0, $timelag);

        $this->assertObjectNotHasAttribute('comments', $data);
        $this->assertObjectNotHasAttribute('posts', $data);
        $this->assertObjectNotHasAttribute('links', $data);
        $this->assertEmpty($writer->get_all_metadata([]));
        $this->assertEmpty($writer->get_files([]));
    }

    public function test_get_data_ratings() {
        global $DB;

        list ($course, $blog, $user, $postid, $otheruser) = $this->create_basic_setup();

        self::allow_student_to_rate();

        // Rate the other user's post.
        $this->setUser($user);
        $rm = new \rating_manager();
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($blog->cmid);
        $context = \context_module::instance($cm->id);
        $rm->add_rating($cm, $context, 'mod_oublog', 'post', $postid,
                100, 50, $otheruser->id, RATING_AGGREGATE_AVERAGE);

        // Now we should get the context.
        $contextlist = $this->get_contexts_for_userid($user->id, 'mod_oublog');
        $contexts = $contextlist->get_contextids();
        $this->assertCount(1, $contexts);
        $context = \context::instance_by_id($contexts[0]);

        // Test data export main file.
        $this->export_context_data_for_user($user->id, $context, 'mod_oublog');
        $writer = \core_privacy\local\request\writer::with_context($context);
        $data = $writer->get_data([]);
        $this->assertEquals('MyBlog', $data->name);
        $this->assertCount(1, $data->posts);

        // Test there is a ratings file.
        $data = $writer->get_related_data(['Posts', 'OtherTitle (' . $postid . ')'], 'rating');
        $data = array_values($data);
        $this->assertEquals(50, $data[0]->rating);
        $this->assertEquals($user->id, $data[0]->author);
    }

    /**
     * Gives blog rating permission to the student role.
     */
    protected static function allow_student_to_rate() {
        global $DB;

        assign_capability('mod/oublog:rate', CAP_ALLOW,
                $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST),
                \context_system::instance());
    }

    /**
     * Creates a file area with a frog picture in, for use when attaching a file.
     *
     * @return int File area draft id
     */
    protected static function create_file_area_with_frog_picture() {
        global $USER;

        $fs = get_file_storage();
        $usercontextid = \context_user::instance($USER->id)->id;
        $draftid = file_get_unused_draft_itemid();
        $fs->create_file_from_pathname(['component' => 'user', 'filearea' => 'draft',
                'contextid' => $usercontextid, 'itemid' => $draftid, 'filepath' => '/',
                'filename' => 'frog.jpg'], __DIR__  . '/fixtures/pd-frog.jpg');

        return $draftid;
    }

    public function test_get_data_files() {
        list ($course, $blog, $user, $postid) = $this->create_basic_setup();

        // Create a post from this user with a file.
        $bloggenerator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        $this->setUser($user);
        $postid = $bloggenerator->create_post($blog,
                ['title' => 'Greeting',
                'message' => 'Hello frogs!',
                'attachments' => self::create_file_area_with_frog_picture()]);

        // Now we should get the context.
        $contextlist = $this->get_contexts_for_userid($user->id, 'mod_oublog');
        $contexts = $contextlist->get_contextids();
        $this->assertCount(1, $contexts);
        $context = \context::instance_by_id($contexts[0]);

        // Test data export includes the file.
        $this->export_context_data_for_user($user->id, $context, 'mod_oublog');
        $writer = \core_privacy\local\request\writer::with_context($context);
        $files = $writer->get_files(['Posts', 'Greeting (' . $postid . ')']);
        $this->assertEquals(['frog.jpg'], array_keys($files));
    }

    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        self::allow_student_to_rate();

        // Create two blogs. One is going to be deleted, the other not.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $victim = $generator->create_module('oublog', ['course' => $course->id,
                'name' => 'VictimBlog', 'intro' => 'My intro', 'scale' => 100, 'assessed' => 1]);
        $survivor = $generator->create_module('oublog', ['course' => $course->id,
                'name' => 'SurvivorBlog', 'intro' => 'My intro', 'scale' => 100, 'assessed' => 1]);

        // Create two users.
        $user = $generator->create_user();
        $otheruser = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $generator->enrol_user($otheruser->id, $course->id, 'student');

        // Fill the blogs with all types of content.
        $bloggenerator = $generator->get_plugin_generator('mod_oublog');
        $rm = new \rating_manager();
        $modinfo = get_fast_modinfo($course);
        foreach ([$victim, $survivor] as $blog) {
            $cm = $modinfo->get_cm($blog->cmid);
            $context = \context_module::instance($cm->id);

            // Create two posts, one of which has a file as attachment.
            $this->setUser($user);
            $postid = $bloggenerator->create_post($blog,
                    ['title' => 'PostTitle', 'message' => 'PostMessage']);
            $this->setUser($otheruser);
            $post2id = $bloggenerator->create_post($blog,
                    ['title' => 'Post2Title', 'message' => 'Post2Message',
                    'attachments' => self::create_file_area_with_frog_picture()]);

            // Rate a post.
            $rm->add_rating($cm, $context, 'mod_oublog', 'post', $postid,
                    100, 50, $user->id, RATING_AGGREGATE_AVERAGE);

            // Comment on a post (with file).
            $bloggenerator->create_comment($blog,
                    ['postid' => $postid, 'title' => 'Comment',
                    'messagecomment' => ['text' => 'CommentMessage',
                        'itemid' => self::create_file_area_with_frog_picture()]]);

            // Edit both posts - one has a message picture and tags, the other an attachment.
            $bloggenerator->create_post($blog,
                    ['id' => $postid, 'title' => 'Change',  'message' => ['text' => 'Different',
                        'itemid' => self::create_file_area_with_frog_picture()],
                    'tags' => 'frogs,amphibians']);
            $bloggenerator->create_post($blog,
                    ['id' => $post2id, 'title' => 'Change2',  'message' => 'Different2',
                    'attachments' => self::create_file_area_with_frog_picture()]);

            // Create a blog link (note: you can't really do this except on the global one, but
            // just to make it a (fairly) complete test).
            oublog_add_link((object)['oubloginstancesid' => $DB->get_field('oublog_instances', 'id',
                    ['oublogid' => $blog->id, 'userid' => $otheruser->id]),
                    'title' => 'First link', 'url' => 'http://1.example.com/']);
        }

        // Count all the relevant tables.
        $this->assertEquals(4, $DB->count_records('oublog_instances'));
        $this->assertEquals(4, $DB->count_records('oublog_posts'));
        $this->assertEquals(4, $DB->count_records('oublog_edits'));
        $this->assertEquals(2, $DB->count_records('oublog_comments'));
        $this->assertEquals(2, $DB->count_records('oublog_links'));
        $this->assertEquals(4, $DB->count_records('oublog_taginstances'));
        $this->assertEquals(8, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg']));
        $this->assertEquals(2, $DB->count_records('rating', ['component' => 'mod_oublog']));

        // Delete data for one of the blogs and check it deletes (only) half this data.
        provider::delete_data_for_all_users_in_context(\context_module::instance($victim->cmid));

        $this->assertEquals(2, $DB->count_records('oublog_instances'));
        $this->assertEquals(2, $DB->count_records('oublog_posts'));
        $this->assertEquals(2, $DB->count_records('oublog_edits'));
        $this->assertEquals(1, $DB->count_records('oublog_comments'));
        $this->assertEquals(1, $DB->count_records('oublog_links'));
        $this->assertEquals(2, $DB->count_records('oublog_taginstances'));
        $this->assertEquals(4, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg']));
        $this->assertEquals(1, $DB->count_records('rating', ['component' => 'mod_oublog']));
    }

    public function test_delete_data_for_user() {
        global $DB;
        self::allow_student_to_rate();

        // Create two blogs. One is going to be deleted, the other not.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $targetblog = $generator->create_module('oublog', ['course' => $course->id,
                'name' => 'VictimBlog', 'intro' => 'My intro', 'scale' => 100, 'assessed' => 1]);
        $otherblog = $generator->create_module('oublog', ['course' => $course->id,
                'name' => 'SurvivorBlog', 'intro' => 'My intro', 'scale' => 100, 'assessed' => 1]);

        // Create some users.
        $user = $generator->create_user();
        $otheruser1 = $generator->create_user();
        $otheruser2 = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $generator->enrol_user($otheruser1->id, $course->id, 'student');
        $generator->enrol_user($otheruser2->id, $course->id, 'student');

        // Fill the blogs with all types of content.
        $bloggenerator = $generator->get_plugin_generator('mod_oublog');
        $rm = new \rating_manager();
        $modinfo = get_fast_modinfo($course);
        foreach ([$targetblog, $otherblog] as $blog) {
            $cm = $modinfo->get_cm($blog->cmid);
            $context = \context_module::instance($cm->id);

            // Create a post owned by the target user (with file).
            $this->setUser($user);
            $postid = $bloggenerator->create_post($blog,
                    ['title' => 'PostTitle', 'message' => 'PostMessage',
                    'attachments' => self::create_file_area_with_frog_picture()]);

            // Create a blog link (note: you can't really do this except on the global one, but
            // just to make it a (fairly) complete test).
            oublog_add_link((object)['oubloginstancesid' => $DB->get_field('oublog_instances', 'id',
                    ['oublogid' => $blog->id, 'userid' => $user->id]),
                    'title' => 'First link', 'url' => 'http://1.example.com/']);

            // As other user, rate and comment on the post (with file).
            $this->setUser($otheruser1);
            $rm->add_rating($cm, $context, 'mod_oublog', 'post', $postid,
                    100, 50, $user->id, RATING_AGGREGATE_AVERAGE);
            $bloggenerator->create_comment($blog,
                    ['postid' => $postid, 'title' => 'Comment',
                    'messagecomment' => ['text' => 'CommentMessage',
                    'itemid' => self::create_file_area_with_frog_picture()]]);

            // Also edit the post (with file), twice (so that there is an old edit owned by
            // $otheruser1). While we're there, add tags to the post.
            $bloggenerator->create_post($blog,
                    ['id' => $postid, 'title' => 'Change',  'message' => ['text' => 'Different1',
                    'itemid' => self::create_file_area_with_frog_picture()],
                    'attachments' => self::create_file_area_with_frog_picture()]);
            $bloggenerator->create_post($blog,
                    ['id' => $postid, 'title' => 'Change',  'message' => ['text' => 'Different2',
                    'itemid' => self::create_file_area_with_frog_picture()],
                    'tags' => 'frogs,amphibians',
                    'attachments' => self::create_file_area_with_frog_picture()]);

            // Now as other user, make a post.
            $postid = $bloggenerator->create_post($blog,
                    ['title' => 'OtherTitle', 'message' => 'OtherMessage',
                    'attachments' => self::create_file_area_with_frog_picture()]);

            // Create a blog link.
            oublog_add_link((object)['oubloginstancesid' => $DB->get_field('oublog_instances', 'id',
                    ['oublogid' => $blog->id, 'userid' => $otheruser1->id]),
                    'title' => 'Second link', 'url' => 'http://2.example.com/']);

            // Change to the second other user account and add a rating.
            $this->setUser($otheruser2);
            $rm->add_rating($cm, $context, 'mod_oublog', 'post', $postid,
                    100, 50, $otheruser1->id, RATING_AGGREGATE_AVERAGE);

            // Also add a comment.
            $bloggenerator->create_comment($blog,
                    ['postid' => $postid, 'title' => 'Comment2',
                    'messagecomment' => ['text' => 'CommentMessage2',
                    'itemid' => self::create_file_area_with_frog_picture()]]);

            // Edit the post twice.
            $bloggenerator->create_post($blog,
                    ['id' => $postid, 'title' => 'Change2',  'message' => ['text' => 'Different1',
                            'itemid' => self::create_file_area_with_frog_picture()]]);
            $bloggenerator->create_post($blog,
                    ['id' => $postid, 'title' => 'Change2',  'message' => ['text' => 'Different2',
                            'itemid' => self::create_file_area_with_frog_picture()]]);

            // Go back to being the first user and make a comment.
            $this->setUser($user);
            $bloggenerator->create_comment($blog,
                    ['postid' => $postid, 'title' => 'CommentU',
                    'messagecomment' => ['text' => 'CommentMessageU',
                    'itemid' => self::create_file_area_with_frog_picture()]]);

            // Do a rating.
            $rm->add_rating($cm, $context, 'mod_oublog', 'post', $postid,
                    100, 50, $otheruser1->id, RATING_AGGREGATE_AVERAGE);

            // Edit the post twice.
            $bloggenerator->create_post($blog,
                    ['id' => $postid, 'title' => 'Change3',  'message' => ['text' => 'Different1',
                    'itemid' => self::create_file_area_with_frog_picture()],
                    'attachments' => self::create_file_area_with_frog_picture()]);
            $bloggenerator->create_post($blog,
                    ['id' => $postid, 'title' => 'Change3',  'message' => ['text' => 'Different2',
                    'itemid' => self::create_file_area_with_frog_picture()],
                    'tags' => 'frogs,amphibians',
                    'attachments' => self::create_file_area_with_frog_picture()]);

            // Mark it deleted by the target user as well.
            $DB->update_record('oublog_posts',
                    ['id' => $postid, 'deletedby' => $user->id, 'timedeleted' => 12345]);
        }

        // Check existing data before delete. In this section, we are trying to count the number of
        // rows and also explain the ones that are deleted, so that we can later recount and confirm
        // that all the ones we expected to be deleted were deleted.
        $this->assertEquals('1, v',
                self::get_blog_instances($targetblog->id, $user, $otheruser1, $otheruser2));
        $this->assertEquals('1, v',
                self::get_blog_instances($otherblog->id, $user, $otheruser1, $otheruser2));

        $userposts = self::get_posts_by_user($targetblog->id, $user);
        $this->assertCount(1, $userposts);
        $otherposts = self::get_posts_by_user($targetblog->id, $otheruser1);
        $this->assertCount(1, $otherposts);
        $this->assertEquals($user->id, $otherposts[0]->lasteditedby);
        $this->assertEquals($user->id, $otherposts[0]->deletedby);
        $this->assertCount(1, self::get_posts_by_user($otherblog->id, $user));
        $this->assertCount(1, self::get_posts_by_user($otherblog->id, $otheruser1));

        $this->assertEquals(12, $DB->count_records('oublog_edits'));
        $originaledits = array_values($DB->get_records('oublog_edits',
                ['postid' => $userposts[0]->id], 'oldtitle'));
        $this->assertCount(2, $originaledits);
        $this->assertEquals(4, $DB->count_records('oublog_edits', ['postid' => $otherposts[0]->id]));
        $useredits = array_values($DB->get_records('oublog_edits',
                ['postid' => $otherposts[0]->id, 'userid' => $user->id], 'oldtitle'));
        $this->assertCount(1, $useredits);

        $this->assertEquals(6, $DB->count_records('oublog_comments'));
        $usercomments = array_values(
                $DB->get_records('oublog_comments', ['postid' => $userposts[0]->id], 'title'));
        $this->assertCount(1, $usercomments);
        $othercomments = array_values(
                $DB->get_records('oublog_comments', ['postid' => $otherposts[0]->id], 'title'));
        $this->assertCount(2, $othercomments);
        $this->assertEquals('Comment2', $othercomments[0]->title);
        $this->assertEquals('CommentU', $othercomments[1]->title);
        $this->assertEquals($user->id, $othercomments[1]->userid);

        $this->assertEquals(4, $DB->count_records('oublog_links'));

        $this->assertEquals(8, $DB->count_records('oublog_taginstances'));
        $this->assertEquals(2, $DB->count_records('oublog_taginstances', ['postid' => $userposts[0]->id]));

        $this->assertEquals(22, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg']));
        $this->assertEquals(1, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $userposts[0]->id,
                'filearea' => 'message']));
        $this->assertEquals(1, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $userposts[0]->id,
                'filearea' => 'attachment']));
        $this->assertEquals(1, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $othercomments[1]->id,
                'filearea' => 'messagecomment']));
        $this->assertEquals(1, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $useredits[0]->id,
                'filearea' => 'edit']));
        $this->assertEquals(1, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $usercomments[0]->id,
                        'filearea' => 'messagecomment']));
        foreach ($originaledits as $edit) {
            $this->assertEquals(1, $DB->count_records('files',
                    ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $edit->id,
                    'filearea' => 'edit']));
        }

        $this->assertEquals(6, $DB->count_records('rating', ['component' => 'mod_oublog']));
        $this->assertEquals(1, $DB->count_records('rating', ['component' => 'mod_oublog',
                'itemid' => $userposts[0]->id]));
        $this->assertEquals(1, $DB->count_records('rating', ['component' => 'mod_oublog', 'userid' => $user->id,
                'itemid' => $otherposts[0]->id]));

        // Delete data on one of the blogs for one of the users.
        $victimcontext = \context_module::instance($targetblog->cmid);
        $contextlist = new approved_contextlist($user, 'mod_oublog', [$victimcontext->id]);
        provider::delete_data_for_user($contextlist);

        // Blog instances: targetblog's instance removed.
        $admin = get_admin();
        $this->assertEquals('1',
                self::get_blog_instances($targetblog->id, $user, $otheruser1, $otheruser2));
        $this->assertEquals('1, v',
                self::get_blog_instances($otherblog->id, $user, $otheruser1, $otheruser2));

        $this->assertCount(0, self::get_posts_by_user($targetblog->id, $user));
        $otherposts = self::get_posts_by_user($targetblog->id, $otheruser1);
        $this->assertCount(1, $otherposts);
        $this->assertEquals($admin->id, $otherposts[0]->lasteditedby);
        $this->assertEquals($admin->id, $otherposts[0]->deletedby);
        $this->assertCount(1, self::get_posts_by_user($otherblog->id, $user));
        $this->assertCount(1, self::get_posts_by_user($otherblog->id, $otheruser1));

        $this->assertEquals(9, $DB->count_records('oublog_edits'));
        $this->assertEquals(0, $DB->count_records('oublog_edits', ['postid' => $userposts[0]->id]));
        $this->assertEquals(3, $DB->count_records('oublog_edits', ['postid' => $otherposts[0]->id]));

        $this->assertEquals(5, $DB->count_records('oublog_comments'));
        $this->assertEquals(0, $DB->count_records('oublog_comments', ['postid' => $userposts[0]->id]));
        $othercomments = array_values(
                $DB->get_records('oublog_comments', ['postid' => $otherposts[0]->id], 'title'));
        $this->assertCount(2, $othercomments);
        $this->assertEquals('Comment2', $othercomments[1]->title);
        $this->assertEquals('', $othercomments[0]->title);
        $this->assertEquals('(Comment deleted by user request)', $othercomments[0]->message);
        $this->assertEquals($admin->id, $othercomments[0]->userid);

        $this->assertEquals(3, $DB->count_records('oublog_links'));

        $this->assertEquals(6, $DB->count_records('oublog_taginstances'));
        $this->assertEquals(0, $DB->count_records('oublog_taginstances', ['postid' => $userposts[0]->id]));

        $this->assertEquals(15, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg']));
        $this->assertEquals(0, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $userposts[0]->id,
                        'filearea' => 'message']));
        $this->assertEquals(0, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $userposts[0]->id,
                        'filearea' => 'attachment']));
        // Note, this one is $othercomments[0] not [1] because the numbers switched due to title ordering.
        $this->assertEquals(0, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $othercomments[0]->id,
                        'filearea' => 'messagecomment']));
        $this->assertEquals(0, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $useredits[0]->id,
                        'filearea' => 'edit']));
        $this->assertEquals(0, $DB->count_records('files',
                ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $usercomments[0]->id,
                        'filearea' => 'messagecomment']));
        foreach ($originaledits as $edit) {
            $this->assertEquals(0, $DB->count_records('files',
                    ['component' => 'mod_oublog', 'filename' => 'frog.jpg', 'itemid' => $edit->id,
                            'filearea' => 'edit']));
        }

        // Note the user's rating is left, which is consistent with what forum does, although it
        // seems a bit rubbish.
        $this->assertEquals(5, $DB->count_records('rating', ['component' => 'mod_oublog']));
        $this->assertEquals(0, $DB->count_records('rating', ['component' => 'mod_oublog',
                'itemid' => $userposts[0]->id]));
        $this->assertEquals(1, $DB->count_records('rating', ['component' => 'mod_oublog', 'userid' => $user->id,
                'itemid' => $otherposts[0]->id]));
    }

    protected static function get_blog_instances($oublogid, $victim, $otheruser1, $otheruser2) {
        global $DB;

        $result = [];
        foreach ($DB->get_records('oublog_instances', ['oublogid' => $oublogid]) as $rec) {
            $result[] = self::get_user_letter($rec->userid, $victim, $otheruser1, $otheruser2);
        }
        sort($result);
        return implode(', ', $result);
    }

    protected static function get_posts_by_user($oublogid, $user) {
        global $DB;

        return array_values($DB->get_records_sql("
                SELECT bp.*
                  FROM {oublog_instances} bi
                  JOIN {oublog_posts} bp ON bp.oubloginstancesid = bi.id
                 WHERE bi.userid = ? AND bi.oublogid = ?", [$user->id, $oublogid]));
    }

    protected static function get_blog_posts($oublogid, $userid) {
        global $DB;

        $result = [];
        foreach ($DB->get_records('oublog_instances', ['oublogid' => $oublogid]) as $rec) {
            $result[] = self::get_user_letter($rec->userid, $victim, $otheruser1, $otheruser2);
        }
        sort($result);
        return implode(', ', $result);

    }

    /**
     * Gets the letter used to refer to a particular user in expectations.
     *
     * @param int $userid User id to check
     * @param \stdClass $victim User object
     * @param \stdClass $otheruser1 Another user object
     * @param \stdClass $otheruser2 Yet another user object
     * @return string A one-character value
     * @throws \coding_exception If user isn't one of the expected people
     */
    protected static function get_user_letter($userid, $victim, $otheruser1, $otheruser2) {
        $admin = get_admin();
        if ($userid == $victim->id) {
            return 'v';
        } else if ($userid == $otheruser1->id) {
            return '1';
        } else if ($userid == $otheruser2->id) {
            return '2';
        } else if ($userid == $admin->id) {
            return 'a';
        } else {
            throw new \coding_exception('Unexpected userid');
        }
    }
}
