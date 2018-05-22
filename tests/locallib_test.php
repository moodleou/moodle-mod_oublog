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
 * Unit tests testing locallib functions inc access etc.
 * To test all unit tests in this folder use cmd:
 * find mod/oublog/tests/*_test.php -type f -print | xargs -n1 vendor/bin/phpunit --debug
 *
 * @package    mod_oublog
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');// It must be included from a Moodle page.
}
global $CFG;
require_once($CFG->dirroot . '/mod/oublog/tests/oublog_test_lib.php');
require_once($CFG->dirroot . '/mod/oublog/locallib.php');

class oublog_locallib_test extends oublog_test_lib {

    public $userid;
    public $modules = array();
    public $usercount = 0;

    /*

    Unit tests cover:
    * Adding posts
    * Adding comments
    * Getting a single post
    * Getting a list of posts
    * Tags
    * Time limited posting
    * Last modified

    // TODO: Unit tests do NOT cover:
     * Personal blog auto creation on install has worked
     * Access permissions (oublog_check_view_permissions + oublog_can_view_post, oublog_can_post + oublog_can_comment)
     * Post edits (+ history)
     * oublog_get_typical_approval_time() + oublog_too_many_comments_from_ip() [moderated comments]
     * Usage stats block functions
     * Importing posts (inc webservices + attachments)
     * Portfolio exporting (Can unit test this?)
     * Feeds
     * Post reporting (OU alerts)
     * Deleting a Post (inc email) - there is no back end function for this, the code is inline
     * Deleting blog (and checking no data left behind)
    */

    public function test_oublog_add_post() {
        global $SITE, $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Whole course.
        $course = $this->get_new_course();

        $oublog = $this->get_new_oublog($course->id);
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        // Whole course - basic post.
        $poststub = $this->get_post_stub($oublog->id);

        $postid = oublog_add_post(clone $poststub, $cm, $oublog, $course);
        $this->assertTrue(is_int($postid));

        $retpost = oublog_get_post($postid);
        $this->assertInstanceOf('stdClass', $retpost);

        $this->assertEquals($poststub->message['text'], $retpost->message);
        $this->assertEquals($poststub->title, $retpost->title);
        $this->assertEquals(fullname($USER), fullname($retpost));

        // Personal blog.
        $oublog = $this->get_new_oublog($SITE->id, array('global' => 1, 'visibility' => OUBLOG_VISIBILITY_PUBLIC));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        // Personal - basic post.
        $poststub = $this->get_post_stub($oublog->id);
        $postid = oublog_add_post(clone $poststub, $cm, $oublog, $SITE);
        $this->assertTrue(is_int($postid));
        $retpost = oublog_get_post($postid);
        $this->assertInstanceOf('stdClass', $retpost);
        $this->assertEquals($poststub->message['text'], $retpost->message);
        $this->assertEquals($poststub->title, $retpost->title);
        $this->assertEquals(fullname($USER), fullname($retpost));
    }

    /* test_oublog_add_comment */
    public function test_oublog_add_comment() {
        global $SITE, $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Test comment using Personal blog.
        $oublog = $this->get_new_oublog($SITE->id, array('global' => 1, 'visibility' => OUBLOG_VISIBILITY_PUBLIC));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        $post = $this->get_post_stub($oublog->id);
        $postid = oublog_add_post($post, $cm, $oublog, $SITE);

        $comment = new stdClass();
        $comment->title = 'Test Comment';
        $comment->messagecomment = array();
        $comment->messagecomment['text'] = 'Message for test comment';
        $comment->postid = $postid;
        $comment->userid = $USER->id;

        $commentid = oublog_add_comment($SITE, $cm, $oublog, $comment);
        $this->assertTrue(is_int($commentid));
        // Get post with comments to check created correctly.
        $post = oublog_get_post($postid);
        $this->assertNotEmpty($post->comments);
        $this->assertTrue(array_key_exists($commentid, $post->comments));
        $this->assertEquals($comment->message, $post->comments[$commentid]->message);
        $this->assertEquals($comment->title, $post->comments[$commentid]->title);
        $this->assertEquals(fullname($USER), fullname($post->comments[$commentid]));

        // Check $canaudit sees deleted comments (and other users don't).
        $DB->update_record('oublog_comments', (object) array('id' => $commentid, 'deletedby' => $USER->id));
        $post = oublog_get_post($postid, true);
        $this->assertNotEmpty($post->comments);
        $post = oublog_get_post($postid);
        $this->assertFalse(isset($post->comments));

        // Check moderated (not logged-in comments).
        $bloginstance = $DB->get_record('oublog_instances', array('id' => $post->oubloginstancesid));
        $adminid = $USER->id;
        $this->setGuestUser();
        $this->assertFalse(oublog_too_many_comments_from_ip());
        $modcomment = new stdClass();
        $modcomment->messagecomment = 'TEST';
        $modcomment->title = 'TITLE';
        $modcomment->postid = $postid;
        $modcomment->authorname = 'Unittest';

        // Catch email sent.
        unset_config('noemailever');
        $sink = $this->redirectEmails();
        // Update our admin user email as default is blank.
        $DB->update_record('user', (object) array('id' => $adminid, 'email' => 'no-reply@www.example.com'));

        $result = oublog_add_comment_moderated($oublog, $bloginstance, $post, $modcomment);
        $messages = $sink->get_messages();
        $this->assertTrue($result);
        $this->assertEquals(1, count($messages));

        $modcomment = $DB->get_record('oublog_comments_moderated', array('postid' => $postid));
        $this->assertInstanceOf('stdClass', $modcomment);
        $id = oublog_approve_comment($modcomment, true);
        $this->assertTrue(is_int($id));
        $saved = $DB->get_record('oublog_comments', array('authorname' => $modcomment->authorname));
        $this->assertInstanceOf('stdClass', $saved);

        // Check post without allowcomments returns no comments (even if added already).
        $DB->update_record('oublog_posts', (object) array('id' => $postid, 'allowcomments' => 0));
        $post = oublog_get_post($postid);
        $this->assertFalse(isset($post->comments));
    }

    /*
     Test getting mulitple posts
    */
    public function test_oublog_get_posts_course() {
        global $SITE, $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->get_new_course();
        // Test posts using standard course blog.
        $oublog = $this->get_new_oublog($course->id);
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        $postcount = 3; // Number of posts to test.

        $titlecheck = 'test_oublog_get_posts';

        // First make sure we have some posts to use.
        $posthashes = array();
        for ($i = 1; $i <= $postcount; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title = $titlecheck . '_' . $i;
        }

        // Create the posts - assumes oublog_add_post is working.
        $postids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
        }

        // Get a list of the posts.
        $context = context_module::instance($cm->id);

        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0);

        // Same name of records returned that were added?
        $this->assertEquals($postcount, $recordcount);

        // First post returned should match the last one added.
        $this->assertEquals($titlecheck . '_' . $postcount, $posts[max($postids)]->title);
        // Check fullname details returned for post author.
        $this->assertEquals(fullname($USER), fullname($posts[max($postids)]));

        // Check deleted posts not shown.
        $deleteinfo = new stdClass();
        $deleteinfo->deletedby = $USER->id;
        $deleteinfo->id = $posts[max($postids)]->id;
        $DB->update_record('oublog_posts', $deleteinfo);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0);
        $this->assertEquals($postcount, $recordcount);// User should see own deleted posts.

        $user = $this->get_new_user('student', $course->id);
        $this->setUser($user);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0);
        $this->assertEquals($postcount - 1, $recordcount);// User should not see deleted posts.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, -1, null, '', true);
        $this->assertEquals($postcount, $recordcount);// User should see deleted posts as can audit.
    }

    public function test_oublog_get_posts_group() {
        global $SITE, $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->get_new_course();
        $groups = array();
        // Test posts using standard course group blog.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => SEPARATEGROUPS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        $postcount = 3; // Number of posts to test.

        $titlecheck = 'test_oublog_get_posts';

        // First make sure we have some posts to use.
        $posthashes = array();
        for ($i = 1; $i <= $postcount; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title = $titlecheck . '_' . $i;
            $group = $this->get_new_group($course->id);
            $groups[$i] = $group->id;
            $posthashes[$i]->groupid = $group->id;
        }

        // Create the posts - assumes oublog_add_post is working.
        $postids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
        }

        // Get a list of the posts.
        $context = context_module::instance($cm->id);

        // All groups.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0);

        // Same name of records returned that were added?
        $this->assertEquals($postcount, $recordcount);

        // First post returned should match the last one added.
        $this->assertEquals($titlecheck . '_' . $postcount, $posts[max($postids)]->title);

        // Specific (last) group.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, $groups[count($groups)]);
        $this->assertEquals(1, $recordcount);
        $this->assertEquals($titlecheck . '_' . $postcount, $posts[max($postids)]->title);
    }

    public function test_oublog_get_posts_individual() {
        global $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->get_new_course();

        $stud1 = $this->get_new_user('student', $course->id);
        $stud2 = $this->get_new_user('student', $course->id);

        // Test 1 -  posts using separate individual.
        $oublog = $this->get_new_oublog($course->id, array('individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        // First make sure we have some posts to use.
        $post1stub = $this->get_post_stub($oublog->id);
        $post1stub->userid = $stud1->id;
        oublog_add_post($post1stub, $cm, $oublog, $course);
        $post2stub = $this->get_post_stub($oublog->id);
        $post2stub->userid = $stud2->id;
        oublog_add_post($post2stub, $cm, $oublog, $course);

        // Get a list of the posts.
        $context = context_module::instance($cm->id);

        // All individuals.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0);

        // Same name of records returned that were added?
        $this->assertEquals(2, $recordcount);

        // Admin see one individual.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, $stud1->id);
        $this->assertEquals(1, $recordcount);

        // User see own.
        $this->setUser($stud1);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, $stud1->id);
        $this->assertEquals(1, $recordcount);

        // User see others?
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, $stud2->id);
        // Odd behaviour in oublog_get_posts() + oublog_individual_add_to_sqlwhere() in this case.
        // Due to user not being able to see blog the individual filter does not get applied.
        $this->assertEquals(2, $recordcount);
        // Give user permission to see other individuals.
        $role = $DB->get_record('role', array('shortname' => 'student'));
        role_change_permission($role->id, $context, 'mod/oublog:viewindividual', CAP_ALLOW);

        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, $stud2->id);
        $this->assertEquals(1, $recordcount);

        // Test 2. posts using visible individual with separate groups.
        $this->setAdminUser();
        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);
        $this->get_new_group_member($group1->id, $stud1->id);
        $this->get_new_group_member($group2->id, $stud2->id);
        $stud3 = $this->get_new_user('student', $course->id);
        $this->get_new_group_member($group1->id, $stud3->id);// New user also in group 1.
        $oublog = $this->get_new_oublog($course->id,
                array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS, 'groupmode' => SEPARATEGROUPS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        // First make sure we have some posts to use.
        $post1stub = $this->get_post_stub($oublog->id);
        $post1stub->userid = $stud1->id;
        oublog_add_post($post1stub, $cm, $oublog, $course);
        $post2stub = $this->get_post_stub($oublog->id);
        $post2stub->userid = $stud2->id;
        oublog_add_post($post2stub, $cm, $oublog, $course);

        // Get a list of the posts.
        $context = context_module::instance($cm->id);

        // Admin - group + individual 0.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, 0);
        $this->assertEquals(2, $recordcount);
        // Admin - group.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, $group1->id, 0);
        $this->assertEquals(1, $recordcount);
        // Admin - individual.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, $group1->id, $stud1->id);
        $this->assertEquals(1, $recordcount);
        // User Own group (but not their post).
        $this->setUser($stud3);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, $group1->id, 0);
        $this->assertEquals(1, $recordcount);
        // User own group and another individual.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, $group1->id, $stud1->id);
        $this->assertEquals(1, $recordcount);
        // User other group (Note as don't have access all get returned as no filter applied).
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, $group2->id, 0);
        $this->assertEquals(2, $recordcount);
        // User other individual (Note as don't have access all get returned as no filter applied).
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, $group2->id, $stud2->id);
        $this->assertEquals(2, $recordcount);
    }

    public function test_oublog_get_posts_personal() {
        global $SITE, $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $stud1 = $this->get_new_user();
        $stud2 = $this->get_new_user();

        if (!$oublog = $DB->get_record('oublog', array('global' => 1))) {
            $oublog = $this->get_new_oublog($SITE->id, array('global' => 1, 'maxvisibility' => OUBLOG_VISIBILITY_PUBLIC,
                'postperpage' => 25));
        }

        $cm = get_coursemodule_from_instance('oublog', $oublog->id);
        $context = context_module::instance($cm->id);

        // First make sure we have some posts to use.
        $post1stub = $this->get_post_stub($oublog->id);
        $post1stub->userid = $stud1->id;
        $post1stub->visibility = OUBLOG_VISIBILITY_COURSEUSER;// Private.
        oublog_add_post($post1stub, $cm, $oublog, $SITE);
        $post2stub = $this->get_post_stub($oublog->id);
        $post2stub->userid = $stud2->id;
        $post2stub->visibility = OUBLOG_VISIBILITY_LOGGEDINUSER;// User must be logged in.
        oublog_add_post($post2stub, $cm, $oublog, $SITE);
        $post3stub = $this->get_post_stub($oublog->id);
        $post3stub->userid = $stud2->id;
        $post3stub->visibility = OUBLOG_VISIBILITY_PUBLIC;// Any user.
        oublog_add_post($post3stub, $cm, $oublog, $SITE);

        // Test 1 - posts using admin.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0);
        $this->assertEquals(3, $recordcount);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, -1, null, null, true, true);
        $this->assertEquals(2, $recordcount);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, $stud2->id);
        $this->assertEquals(2, $recordcount);
        // Test 2 -  posts using logged in user.
        $this->setUser($stud2);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0);
        $this->assertEquals(2, $recordcount);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, $stud1->id);
        $this->assertEquals(0, $recordcount);
        // Test 3 - posts using guest (not logged in).
        $this->setGuestUser();
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0);
        $this->assertEquals(1, $recordcount);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, $stud1->id);
        $this->assertEquals(0, $recordcount);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, $stud2->id);
        $this->assertEquals(1, $recordcount);

        // Test 4 - create multiple posts for pagination tests.
        $this->setAdminUser();
        // Number of posts/comments for tests.
        $postcount = $postperpage = (int)$oublog->postperpage;
        // Create further post stubs for students, ie 3 pages.
        // Create student 1s post stubs.
        $posthashes = $postids = array();
        for ($i = 1; $i <= $postcount; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->userid = $stud1->id;
            $posthashes[$i]->visibility = OUBLOG_VISIBILITY_PUBLIC;// Any user.
        }
        // Create student 1s posts.
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $SITE);
        }
        // Create student 2s post stubs.
        for ($i = 1; $i <= $postcount; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->userid = $stud2->id;
            $posthashes[$i]->visibility = OUBLOG_VISIBILITY_PUBLIC;
        }
        // Create student 2s posts.
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $SITE);
        }
        // Setup pagination offset count for page 1.
        $page = 0;
        $offset = $page * $postperpage;
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, 0, -1, null, '', true, true);
        $this->assertEquals($postperpage * 2 + 2, $recordcount);// Includes count of lesser visibility posts.
        $this->assertCount($postperpage, $posts);// Includes only paged visibile posts.
        // Setup pagination offset count for page 2.
        $page = 1;
        $offset = $page * $postperpage;
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, 0, -1, null, '', true, true);
        $this->assertCount($postperpage, $posts);// Includes only paged visibile posts.
        // Setup pagination offset count for page 3.
        $page = 2;
        $offset = $page * $postperpage;
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, 0, -1, null, '', true, true);
        $this->assertCount(2, $posts);// Includes only paged visibile posts.
    }

    /* test_oublog_get_last_modified */
    public function test_oublog_get_last_modified() {
        global $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->get_new_course();
        $oublog = $this->get_new_oublog($course->id);
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $stud1 = $this->get_new_user('student', $course->id);
        $oublog2 = $this->get_new_oublog($course->id);

        $post = $this->get_post_stub($oublog->id);
        $postid = oublog_add_post($post, $cm, $oublog, $course);
        $timeposted = $DB->get_field('oublog_posts', 'timeposted', array('id' => $postid));
        $lastmodified = oublog_get_last_modified($cm, $course, $USER->id);
        $this->assertTrue(is_numeric($lastmodified));
        $this->assertEquals($timeposted, $lastmodified);

        $result = oublog_get_last_modified($oublog2->cm, $course);
        $this->assertEmpty($result);
        $this->get_new_post($oublog2);

        // Should static cache result, so remains empty.
        $result = oublog_get_last_modified($oublog2->cm, $course);
        $this->assertEmpty($result);

        $result = oublog_get_last_modified($oublog2->cm, $course, $stud1->id);
        $this->assertNotEmpty($result);
        $result1 = oublog_get_last_modified($oublog2->cm, $course, $stud1->id);
        $this->assertEquals($result, $result1);
        // TODO: More comprehensive checking with separate group/individual blogs.
    }

    /*
     * Add unit test to cover main oublog_get_participation_details(
       * $oublog, $groupid, $individual, $start = null, $end = null,
       * $page = 0, $getposts = true, $getcomments = true,
       * $limitfrom = null, $limitnum = null) function.
    */
    public function test_oublog_get_participation_details() {
        global $SITE, $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $now = time();
        // Whole course.
        // Single course for all subsequent tests?
        $course = $this->get_new_course();
        $oublog = $this->get_new_oublog($course->id);
        $oublog->allowcomments = OUBLOG_COMMENTS_ALLOW;
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $student1 = $this->get_new_user('student', $course->id);
        $student2 = $this->get_new_user('student', $course->id);
        // Number of posts and comments to create for whole course tests.
        $postcountwc = 3;
        $titlecheck = 'test_oublog_get_parts_wc';
        // Prepare to make some posts using the posts stub.
        $posthashes = array();
        for ($i = 1; $i <= $postcountwc; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title = 'Test Post ' . $titlecheck;
            // Add the posting student.
            $posthashes[$i]->userid = $student1->id;
        }
        // Create the posts - assumes oublog_add_post is working,
        // also add student comments to those posts.
        $postids = $commentids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
            // Add the commenting student.
            $comment = $this->get_comment_stub($posthash->id, $student2->id);
            $comment->title .= " ".$titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment);
        }
        // Get the participation object with counts of posts and comments.
        $curgroup = oublog_get_activity_group($cm);
        $curindividual = 0;
        $studentnamed = fullname($student1);
        $limitnum = 4;
        $start = $end = $page = $limitfrom = 0;
        $getposts = true;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test postscount && commentscount.
        // The same number of records should be 'discovered' that were added.
        $this->assertEquals($postcountwc, $participation->postscount);
        $this->assertEquals($postcountwc, $participation->commentscount);
        // This blog activity  allows comments.
        $this->assertEquals(OUBLOG_COMMENTS_ALLOW, $oublog->allowcomments);
        // The number of records should be 'returned' that were added.
        $this->assertCount($postcountwc, $participation->posts);
        $this->assertCount($postcountwc, $participation->comments);
        // The posts returned should match the ones added.
        foreach ($participation->posts as $post) {
            // Do some basic checks - does it match our test post created above.
            $this->assertInstanceOf('stdClass', $post);
            $this->assertEquals('Test Post ' . $titlecheck, $post->title);
            $this->assertEquals($student1->id, $post->userid);
            $this->assertEquals(0, $post->groupid);
            $postername = fullname($post);
            $this->assertEquals($studentnamed, $postername);
            $this->assertEquals($oublog->allowcomments, $post->allowcomments);
        }
        // Test comments object.
        foreach ($participation->comments as $comment) {
            // Same basic checks - does it match our test comment created above.
            $this->assertInstanceOf('stdClass', $comment);
            $this->assertEquals('Test Comment '. $titlecheck, $comment->title);
            $this->assertEquals($oublog->id, $comment->oublogid);
            $this->assertEquals($post->title, $comment->posttitle);
            $this->assertEquals($post->userid, $comment->posterid);
            $this->assertEquals($post->userid, $comment->postauthorid);
            $this->assertEquals($post->groupid, $comment->groupid);
            $this->assertEquals($post->visibility, $comment->visibility);
            $this->assertEquals($studentnamed, $comment->posterfirstname ." ". $comment->posterlastname);
        }

        // Test for comments turned ON the blog.
        // But turned OFF on ONE post.
        $oublog->allowcomments = OUBLOG_COMMENTS_ALLOW;
        $getposts = false;// Dont need to see posts.
        $getcomments = true;// Do want to see comments.
        // Create additional post.
        $posttest = $this->get_post_stub($oublog->id);
        $posttest->title = 'Test Post ' . $titlecheck;
        // Add the posting student.
        $posttest->userid = $student1->id;
        // Add one post which doesnt allow comments
        $posttest->allowcomments = OUBLOG_COMMENTS_PREVENT;
        $postids[] = oublog_add_post($posttest, $cm, $oublog, $course);
        // Attemt to add a student comment, though not allowed.
        $comment3 = $this->get_comment_stub($posttest->id, $student2->id);
        $comment3->title .= " NOT ALLOWED ".$titlecheck;
        $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment3);
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // The number of posts that should be 'discovered' plus the new one.
        $this->assertEquals($postcountwc + 1, $participation->postscount);
        // The number of posts that should be 'returned'.
        $this->assertCount(0, $participation->posts);
        // This blog activity DOES allow comments.
        $this->assertEquals(OUBLOG_COMMENTS_ALLOW, $oublog->allowcomments);
        // The number of comments that should be 'discovered', does not not include the new one.
        $this->assertEquals($postcountwc, $participation->commentscount);
        // The number of comments to be 'returned' does not not include the new one.
        $this->assertCount($postcountwc, $participation->comments);

        // Separate groups.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => SEPARATEGROUPS));
        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);
        $this->get_new_group_member($group1->id, $student1->id);
        $this->get_new_group_member($group2->id, $student2->id);
        $postcountsg = 3;
        $titlecheck = ' test_oublog_get_sg';
        // Make some posts to use from post stub.
        $posthashes = array();
        for ($i = 1; $i <= $postcountsg; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title = $titlecheck;
            // Add the posting student.
            $posthashes[$i]->userid = $student1->id;
            $posthashes[$i]->groupid = $group1->id;
        }
        // Create the posts assuming oublog_add_post is working.
        $postids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
            // Not adding comments for this test.
        }

        $curgroup = 0;// All groups.
        $curindividual = 0;
        $getposts = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // The number of posts that should be 'discovered' plus the new one.
        $this->assertEquals($postcountsg, $participation->postscount);
        // The number of posts that should be 'returned'.
        $this->assertCount($postcountsg, $participation->posts);
        // The number of comments should be 'discovered'.
        $this->assertEquals(0, $participation->commentscount);
        // The number of comments should be 'returned'.
        $this->assertCount(0, $participation->comments);

        // Test posts object. Only Group ONE
        foreach ($participation->posts as $post) {
            // Basic checks.
            $this->assertInstanceOf('stdClass', $post);
            $this->assertEquals($group1->id, $post->groupid);
            $this->assertEquals($student1->id, $post->userid);
            $postername = fullname($post);
            $this->assertEquals($postername, $post->firstname ." ". $post->lastname);
        }
        // Prepare some more post stubs to use.
        $posthashes = array();
        for ($i = 1; $i <= $postcountsg; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title = $titlecheck;
            // Add the posting student.
            $posthashes[$i]->userid = $student2->id;
            $posthashes[$i]->groupid = $group2->id;
        }
        // Creating the posts, and add comments.
        $postids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
            // Add the commenting student.
            $comment = $this->get_comment_stub($posthash->id, $student2->id);
            $comment->title .= $titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment);
        }

        // Test posts object. ONLY Group TWO.
        $curgroup = $group2->id;
        $curindividual = 0;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        $this->assertEquals($postcountsg, $participation->postscount);
        // No: of comments made same as posts.
        $this->assertEquals($postcountsg, $participation->commentscount);
        // Test posts object. ONLY Group TWO.
        foreach ($participation->posts as $post) {
            // Do some basic checks.
            $this->assertInstanceOf('stdClass', $post);
            $this->assertEquals($group2->id, $post->groupid);
            $this->assertEquals($student2->id, $post->userid);
        }
        // Test comments object.
        foreach ($participation->comments as $comment) {
            // Do some basic checks - does it match our test post created above?.
            $this->assertInstanceOf('stdClass', $comment);
            $this->assertEquals('Test Comment' . $titlecheck, $comment->title);
            $this->assertEquals($oublog->id, $comment->oublogid);
            $this->assertEquals($post->title, $comment->posttitle);
            $this->assertEquals($post->userid, $comment->posterid);
        }

        // Separate groups, separate individuals.
        $oublog = $this->get_new_oublog($course->id, array(
                'groupmode' => SEPARATEGROUPS,
                'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $group5 = $this->get_new_group($course->id);
        $group6 = $this->get_new_group($course->id);
        $this->get_new_group_member($group5->id, $student1->id);
        $this->get_new_group_member($group6->id, $student2->id);
        // Number of posts/comments for these tests.
        $postcountsgsi = 2;
        $titlecheck = ' testing_oublog_sgsi_get_posts';
        // Some post stubs to use in group 5.
        $posthashes = array();
        for ($i = 1; $i <= $postcountsgsi; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title = $titlecheck;
            // Add the posting student.
            $posthashes[$i]->userid = $student1->id;
        }
        // Create group 5's posts and comments.
        $postids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
            $comment = $this->get_comment_stub($posthash->id, $student1->id);
            $comment->title .= $titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment);
        }
        // Make sure we have some post stubs to use in group 6.
        $posthashes = array();
        for ($i = 1; $i <= $postcountsgsi; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title = $titlecheck;
            $posthashes[$i]->userid = $student2->id;

        }
        // Create group 6s posts and comments.
        $postids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
            $comment = $this->get_comment_stub($posthash->id, $student2->id);
            $comment->title .= $titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment);
        }

        // Test no groups.
        $curgroup = 0;
        $curindividual = 0;
        $getposts = true;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // The number of posts that should be 'discovered', from both groups.
        $this->assertEquals($postcountsgsi * 2, $participation->postscount);
        // The number of posts that should be 'returned'.
        $this->assertCount($postcountsgsi * 2, $participation->posts);
        // The number of comments should be 'discovered', from both groups.
        $this->assertEquals($postcountsgsi * 2, $participation->commentscount);
        // The number of comments should be 'returned'.
        $this->assertCount($postcountsgsi * 2, $participation->comments);

        // Test of student in group 6.
        $curgroup = $group6->id;
        $curindividual = $student2->id;
        $getposts = false;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // The number of one students posts that should be 'discovered' from group.
        $this->assertEquals($postcountsgsi, $participation->postscount);
        // There are posts but should not be 'returned'.
        $this->assertCount(0, $participation->posts);
        // The number of comments that should be 'discovered', from both groups.
        $this->assertEquals($postcountsgsi, $participation->commentscount);
        // The number of comments that should be 'returned'.
        $this->assertCount($postcountsgsi, $participation->comments);

        // Test for student not in group 6.
        $curgroup = $group6->id;
        $curindividual = $student1->id;
        $getposts = false;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Student not in the group could make no posts or comments
        $this->assertEquals(0, $participation->postscount);
        $this->assertCount(0, $participation->posts);
        $this->assertEquals(0, $participation->commentscount);
        $this->assertCount(0, $participation->comments);

        // Separate individuals.
        $oublog = $this->get_new_oublog($course->id, array(
                'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        // Number of posts/comments for these tests.
        $postcountsi = 2;
        $titlecheck = 'oublog_si_tests';
        // Creating some post stubs for student 1 to use.
        $posthashes = array();
        for ($i = 1; $i <= $postcountsi; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title .= $titlecheck;
            $posthashes[$i]->userid = $student1->id;
        }
        // Create student 1s posts and comments.
        $postids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
            $comment1 = $this->get_comment_stub($posthash->id, $student1->id);
            $comment1->title .= $titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment1);
            $comment2 = $this->get_comment_stub($posthash->id, $student2->id);
            $comment2->title .= $titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment2);
        }
        // Create student 2s posts and comments.
        for ($i = 1; $i <= $postcountsi; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title = $titlecheck;
            $posthashes[$i]->userid = $student2->id;
        }
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
            $comment1 = $this->get_comment_stub($posthash->id, $student2->id);
            $comment1->title .= $titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment1);
            $comment2 = $this->get_comment_stub($posthash->id, $student2->id);
            $comment2->title .= $titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment2);
        }

        // Test All individuals.
        $curgroup = 0;
        $curindividual = 0;
        $oublog->individual = OUBLOG_SEPARATE_INDIVIDUAL_BLOGS;
        $getposts = true;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test count of posts by both students.
        $this->assertEquals($postcountsi * 2, $participation->postscount);
        // The number of student posts should be 'returned' that were added.
        $this->assertCount($postcountsi * 2, $participation->posts);
        // Test count of all students comments.
        $this->assertEquals($postcountsi * 4, $participation->commentscount);
        // The number of comments which should be 'returned'.
        $this->assertCount($postcountsi * 2, $participation->comments);

        // Test an individual.
        $curgroup = 0;
        $curindividual = $student2->id;
        $studentnamed = fullname($student2);
        $getposts = true;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test count of posts by both students.
        $this->assertEquals($postcountsi, $participation->postscount);
        // The number of student posts should be 'returned' that were added.
        $this->assertCount($postcountsi, $participation->posts);
        // Test count of all students comments.
        $this->assertEquals($postcountsi * 2, $participation->commentscount);
        // The number of comments which should be 'returned'.
        $this->assertCount($postcountsi * 2, $participation->comments);

        // The posts returned should match the ones added.
        foreach ($participation->posts as $post) {
            // Do some basic checks - does it match our test post created above.
            $this->assertInstanceOf('stdClass', $post);
            $this->assertEquals($titlecheck, $post->title);
            $this->assertEquals($student2->id, $post->userid);
            $this->assertEquals(0, $post->groupid);
            $postername = fullname($post);
            $this->assertEquals($studentnamed, $postername);
        }

        // Global blog = Personal blog.
        if (!$oublog = $DB->get_record('oublog', array(
                'course' => $SITE->id, 'global' => 1))) {
            $oublog = $this->get_new_oublog($SITE->id, array(
                    'global' => 1,
                    'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS,
                    'allowcomments' => OUBLOG_COMMENTS_ALLOWPUBLIC,
                    'maxvisibility' => OUBLOG_VISIBILITY_PUBLIC));
        }
        $cm = get_coursemodule_from_instance('oublog', $oublog->id);
        // Number of posts and comments to create for Global course tests.
        $postcountpb = 2;
        $titlecheck = 'PERSBLOG_parts';
        // Prepare to make some posts to use for test 1.
        $posthashes1 = array();
        for ($ai = 1; $ai <= $postcountpb; $ai++) {
            $posthashes1[$ai] = $this->get_post_stub($oublog->id);
            $posthashes1[$ai]->title = "TP " . $titlecheck;
            $posthashes1[$ai]->userid = $student1->id;
            $posthashes1[$ai]->visibility = OUBLOG_VISIBILITY_COURSEUSER;
        }
        // Create the posts - assumes oublog_add_post is working,
        // also add student comments to those posts.
        $postids = $commentids = array();
        foreach ($posthashes1 as $posthasha) {
            $postids[] = oublog_add_post($posthasha, $cm, $oublog, $SITE);
            $comment1 = $this->get_comment_stub($posthasha->id, $student1->id);
            $comment1->title = "TC " .$titlecheck;
            $commentids[] = oublog_add_comment($SITE, $cm, $oublog, $comment1);
            $comment2 = $this->get_comment_stub($posthasha->id, $student2->id);
            $comment2->title = "TC " .$titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment2);
        }

        // Get the participation object with counts of posts and comments.
        $curgroup = 0;
        $curindividual = 0;
        $limitnum = 8;
        $start = $end = $page = 0;
        $limitfrom = 0;
        $getposts = false;// Dont need posts on global blog.
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test postscount && commentscount;
        // When visibility is private course user only return none.
        $this->assertEquals(0, $participation->postscount);
        $this->assertEquals(0, $participation->commentscount);

        // Prepare to make some posts to use for test two.
        $posthashes1 = array();
        for ($ai = 1; $ai <= $postcountpb; $ai++) {
            $posthashes1[$ai] = $this->get_post_stub($oublog->id);
            $posthashes1[$ai]->title = "TP " . $titlecheck;
            $posthashes1[$ai]->userid = $student1->id;
            $posthashes1[$ai]->visibility = OUBLOG_VISIBILITY_LOGGEDINUSER;
        }
        // Create the posts - assumes oublog_add_post is working,
        // also add student comments to those posts.
        $postids = $commentids = array();
        foreach ($posthashes1 as $posthasha) {
            $postids[] = oublog_add_post($posthasha, $cm, $oublog, $SITE);
            $comment1 = $this->get_comment_stub($posthasha->id, $student1->id);
            $comment1->title = "TC " .$titlecheck;
            $commentids[] = oublog_add_comment($SITE, $cm, $oublog, $comment1);
            $comment2 = $this->get_comment_stub($posthasha->id, $student2->id);
            $comment2->title = "TC " .$titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment2);
        }

        // Get the participation object with counts of posts and comments.
        $curgroup = 0;
        $curindividual = 0;
        $limitnum = 8;
        $start = $end = $page = $limitfrom = 0;
        $getposts = false;// Dont need posts on global blog.
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // For this test each post commented on twice.
        $this->assertEquals($postcountpb, $participation->postscount);
        $this->assertEquals($postcountpb * 2, $participation->commentscount);
        // Test comments object.
        foreach ($participation->comments as $comment) {
            // Same basic checks - does it match our test comment created above.
            $this->assertInstanceOf('stdClass', $comment);
            $this->assertEquals("TC " . $titlecheck, $comment->title);
            $this->assertEquals(OUBLOG_VISIBILITY_LOGGEDINUSER, $comment->visibility);
        }
        // Prepare to make some posts to use for test 3.
        $posthashes1 = array();
        for ($ai = 1; $ai <= $postcountpb; $ai++) {
            $posthashes1[$ai] = $this->get_post_stub($oublog->id);
            $posthashes1[$ai]->title = "TP " . $titlecheck;
            $posthashes1[$ai]->userid = $student1->id;
            $posthashes1[$ai]->visibility = OUBLOG_VISIBILITY_PUBLIC;
        }
        // Create the posts - assumes oublog_add_post is working,
        // also add student comments to those posts.
        $postids = $commentids = array();
        foreach ($posthashes1 as $posthasha) {
            $postids[] = oublog_add_post($posthasha, $cm, $oublog, $SITE);
            $comment1 = $this->get_comment_stub($posthasha->id, $student1->id);
            $comment1->title = "TC " .$titlecheck;
            $commentids[] = oublog_add_comment($SITE, $cm, $oublog, $comment1);
            $comment2 = $this->get_comment_stub($posthasha->id, $student2->id);
            $comment2->title = "TC " .$titlecheck;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment2);
        }

        // Get the participation object with counts of posts and comments.
        $curgroup = 0;
        $curindividual = 0;
        $limitnum = 8;
        $start = $end = $page = $limitfrom = 0;
        $getposts = false;// Dont need posts on global blog.
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test postscount && commentscount; the same number of records
        // or multiples of $postcountpb, should be discovered.
        $this->assertEquals($postcountpb * 2, $participation->postscount);
        $this->assertEquals($postcountpb * 4, $participation->commentscount);
        // But we should only return the limited amount of 8 comments.
        $this->assertCount(0, $participation->posts);
        $this->assertCount($limitnum, $participation->comments);
        // Test comments object.
        foreach ($participation->comments as $comment) {
            // Same basic checks - does it match our test comment created above.
            $this->assertInstanceOf('stdClass', $comment);
            $this->assertEquals("TC " . $titlecheck, $comment->title);
        }

        // Set as guest user not logged in.
        // Get the participation object with counts of posts and comments.
        $curgroup = 0;
        $this->setUser(0);
        $curindividual = 0;
        $limitnum = 8;
        $start = $end = $page = $limitfrom = 0;
        $getposts = false;// Dont need posts on global blog.
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test postscount && commentscount; the same number of records
        // or multiples of $postcountpb, should be returned that were added.
        $this->assertEquals($postcountpb, $participation->postscount);
        $this->assertEquals($postcountpb * 2, $participation->commentscount);
        // Test that we only return the limited amount of 8 commentss.
        $this->assertCount(0, $participation->posts);
        $this->assertLessThan($limitnum, count($participation->comments));
        // Test comments object.
        foreach ($participation->comments as $comment) {
            // Same basic checks - does it match our test comment created above.
            $this->assertInstanceOf('stdClass', $comment);
            $this->assertEquals("TC " . $titlecheck, $comment->title);
        }

        // Separate individuals time checking.
        // Reset the User previously set as guest user not logged in.
        $this->setAdminUser();
        $yesterday = time() - (24*60*60) - 60;
        $beforeyesterday = time() - (2*24*60*60);
        $sometimetoday = time();
        $oublog = $this->get_new_oublog($course->id, array(
                'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        // Number of posts/comments for these tests.
        $postcountsit = 4;
        $titlecheck = ' oublog_sit_test_times';
        // Creating some post stubs for student 1 to use
        $posthashes = array();
        for ($i = 1; $i <= $postcountsit; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title .= $titlecheck;
            $posthashes[$i]->userid = $student1->id;
            $posthashes[$i]->timeposted = $sometimetoday;
        }
        // Create student 1s posts with comments by S1 and S2.
        $postids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
            $comment1 = $this->get_comment_stub($posthash->id, $student1->id);
            $comment1->title .=  $titlecheck;
            $comment1->timeposted = $sometimetoday;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment1);
            $comment2 = $this->get_comment_stub($posthash->id, $student2->id);
            $comment2->title .= $titlecheck;
            $comment2->timeposted = $sometimetoday;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment2);
        }
        // Create student 2s yesterday posts and comments.
        for ($i = 1; $i <= $postcountsit; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title .= $titlecheck;
            $posthashes[$i]->userid = $student2->id;
            $posthashes[$i]->timeposted = $yesterday;
        }
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
            $comment1 = $this->get_comment_stub($posthash->id, $student2->id);
            $comment1->title .= $titlecheck;
            $comment1->timeposted = $yesterday;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment1);
            $comment2 = $this->get_comment_stub($posthash->id, $student1->id);
            $comment2->title .= $titlecheck;
            $comment2->timeposted = $yesterday;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment2);
        }
        // Create more student 2 posts before yesterdays posts and comments.
        // A limited set of these will be returned in a later test.
        for ($i = 1; $i <= $postcountsit; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title = $titlecheck. $student2->id . $beforeyesterday . $i;
            $posthashes[$i]->userid = $student2->id;
            $posthashes[$i]->timeposted = $beforeyesterday;
        }
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
            $comment1 = $this->get_comment_stub($posthash->id, $student1->id);
            $comment1->title = $titlecheck . $student1->id . $beforeyesterday . $posthash->id;
            $comment1->timeposted = $beforeyesterday;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment1);
            $comment2 = $this->get_comment_stub($posthash->id, $student2->id);
            $comment2->title = $titlecheck . $student2->id . $beforeyesterday . $posthash->id;
            $comment2->timeposted = $beforeyesterday;
            $commentids[] = oublog_add_comment($course, $cm, $oublog, $comment2);
            // Saving last entries for limited beforeyesterday assertions later.
            $posttitlebeforeyesterday = $posthash->title;
            $comment1titlebeforeyesterday = $comment1->title;
            $comment2titlebeforeyesterday = $comment2->title;
        }
        // Test All time entries.
        $curgroup = 0;
        $curindividual = 0;
        $oublog->individual = OUBLOG_SEPARATE_INDIVIDUAL_BLOGS;
        $limitnum = 8;
        $start = 0;
        $end = 0;
        $page = 0;
        $limitfrom = 0;
        $getposts = true;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test count of posts.
        $this->assertEquals($postcountsit * 3, $participation->postscount);
        // The number of student posts should be 'returned' that were added.
        $this->assertCount($postcountsit * 2, $participation->posts);
        // Test count of all students comments.
        $this->assertEquals($postcountsit * 6, $participation->commentscount);
        // The number of comments should be 'returned'.
        $this->assertCount($postcountsit * 2, $participation->comments);

        // Test that the posts returned match the ones added.
        foreach ($participation->posts as $post) {
            // Do some basic checks - does it match our test post created above.
            $this->assertInstanceOf('stdClass', $post);
            $this->assertEquals("testpost" . $titlecheck, $post->title);
            $postername = fullname($post);
            $this->assertEquals($postername, $post->firstname ." ". $post->lastname);
        }
        // Test comments object.
        foreach ($participation->comments as $comment) {
            // Same basic checks - does it match our test comment created above.
            $this->assertInstanceOf('stdClass', $comment);
        }

        // Test previous day timed entries.
        $limitnum = 8;
        $start = $yesterday - 60;
        $end = $sometimetoday;
        $page = $limitfrom = 0;
        $getposts = true;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test count of posts for period.
        $this->assertEquals($postcountsit, $participation->postscount);
        // The number of student posts for period which should be 'returned'
        $this->assertCount($postcountsit, $participation->posts);
        // Test count of all students comments for test period.
        $this->assertEquals($postcountsit * 2, $participation->commentscount);
        // The number of comments should be 'returned'.
        $this->assertCount($postcountsit * 2, $participation->comments);

        // Test a limited time period during previous day.
        $curgroup = 0;
        $curindividual = 0;
        $limitnum = 8;
        $start = $yesterday - (60*60);
        $end = $yesterday + (60*60);
        $page = $limitfrom = 0;
        $getposts = true;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test count of posts for period.
        $this->assertEquals($postcountsit, $participation->postscount);
        // The number of student posts for period which should be 'returned'
        $this->assertCount($postcountsit, $participation->posts);
        // Test count of all students comments for test period.
        $this->assertEquals($postcountsit * 2, $participation->commentscount);
        // The number of comments should be 'returned'.
        $this->assertCount($postcountsit * 2, $participation->comments);

        // Test extended daily time period
        $curgroup = 0;
        $curindividual = 0;
        $limitnum = 8;
        $start = $beforeyesterday - 60;
        $end = $sometimetoday + 60;
        $page = $limitfrom = 0;
        $getposts = true;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test count of posts to be 'discovered' for period.
        $this->assertEquals($postcountsit * 3, $participation->postscount);
        // The number of student posts for period which should be 'returned'
        $this->assertCount($postcountsit * 2, $participation->posts);
        // Test count of all students comments 'discovered' for test period.
        $this->assertEquals($postcountsit * 6, $participation->commentscount);
        // The number of comments which should be 'returned'.
        $this->assertCount($postcountsit * 2, $participation->comments);
        foreach ($participation->posts as $post) {
            // Do some basic checks - does it match our test post created above.
            $this->assertInstanceOf('stdClass', $post);
            $this->assertEquals("testpost" . $titlecheck, $post->title);
            $this->assertEquals(fullname($post), $post->firstname ." ". $post->lastname);
        }
        // Test comments object.
        foreach ($participation->comments as $comment) {
            // Same basic checks - does it match our test comment created above.
            $this->assertInstanceOf('stdClass', $comment);
            $this->assertEquals(OUBLOG_VISIBILITY_COURSEUSER, $comment->visibility);
        }
        // Test using extended daily time period with limited return of posts and comments,
        // using saved last entries for beforeyesterday assertions.
        $curgroup = 0;
        $curindividual = 0;
        $limitnum = $postcountsit * 2;// Any Number, above the $postcountsit
        $start = $beforeyesterday - 1;
        $end = $beforeyesterday + 1;
        $page = 0;
        $limitfrom = $postcountsit -2;// Any Number, less that $postcountsit.
        $getposts = true;
        $getcomments = true;
        $participation = oublog_get_participation_details($oublog, $curgroup, $curindividual,
                $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum);
        // Test count of posts to be 'discovered' for period.
        $this->assertEquals($postcountsit, $participation->postscount);
        // The number of student posts for period which should be 'returned'
        $this->assertCount($postcountsit - 2, $participation->posts);
        // Test count of all students comments for test period.
        $this->assertEquals($limitnum, $participation->commentscount);
        // The number of comments should be 'returned'.
        $this->assertCount($limitnum - $limitfrom, $participation->comments);
        // Test posts object.
        foreach ($participation->posts as $post) {
            // Do some basic checks - does it match our test post created above.
            $this->assertInstanceOf('stdClass', $post);
            $this->assertEquals($student2->id, $post->userid);
            $postername = fullname($post);
            $this->assertEquals($postername, $post->firstname ." ". $post->lastname);
            // Recognise last returned post correctly, but without id in title.
            if ($post->title == $posttitlebeforeyesterday) {
                $this->assertTrue(true);
            }
        }

        foreach ($participation->comments as $comment) {
            // Same basic checks - does it match our test comment created above.
            $this->assertInstanceOf('stdClass', $comment);
            $this->assertLessThanOrEqual(OUBLOG_VISIBILITY_COURSEUSER, $comment->visibility);
            // Recognise last returned comments correctly, but with out ids in titles.
            if ($comment->title == $comment1titlebeforeyesterday ||
                    $comment->title == $comment2titlebeforeyesterday) {
                $this->assertTrue(true);
            }
        }
    }


    /* test_oublog_get_posts_pagination */
    public function test_oublog_get_posts_pagination() {
        global $SITE, $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->get_new_course();
        // Test posts using standard course blog.
        $oublog = $this->get_new_oublog($course->id);
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        // Number of posts for test, more than posts per page
        $postcount = $oublog->postperpage + (int)($oublog->postperpage / 2);
        $titlecheck = 'test_oublog_get_posts_pagination';

        // First make sure we have some posts to use.
        $posthashes = array();
        for ($i = 1; $i <= $postcount; $i++) {
            $posthashes[$i] = $this->get_post_stub($oublog->id);
            $posthashes[$i]->title = $titlecheck . '_' . $i;
        }

        // Create the posts - assumes oublog_add_post is working.
        $postids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cm, $oublog, $course);
        }

        $context = context_module::instance($cm->id);
        // Build paging parameters for the first page .
        $page = 0;
        $offset = $page * $oublog->postperpage;
        // Get a list of the pages posts.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, 0);
        // Same number of records discovered that were created?
        $this->assertEquals($postcount, $recordcount);
        // Is the number of posts returned that were expected?.
        $this->assertEquals($oublog->postperpage, count($posts));

        // Build paging parameters for the second page.
        $page = 1;
        $offset = $page * $oublog->postperpage;
        // Get the list of the second pages posts.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, 0);
        // Number of posts returned that were expected?.
        $this->assertEquals($postcount - $offset, count($posts));
    }

    public function test_oublog_tags() {
        global $USER, $DB, $SITE;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->get_new_course();
        $stud1 = $this->get_new_user('student', $course->id);
        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);
        $this->get_new_group_member($group1->id, $stud1->id);

        // Whole course blog.
        $oublog = $this->get_new_oublog($course->id);
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        $post = $this->get_post_stub($oublog->id);
        $post->tags = array('1', 'new', 'new', 'new2', 'a space');
        $postid = oublog_add_post($post, $cm, $oublog, $course);

        $this->assertEquals(21, strlen(oublog_get_tags_csv($postid)));

        $tags = oublog_get_tags($oublog, 0, $cm, null, -1);
        $this->assertCount(4, $tags);

        foreach ($tags as $tag) {
            $this->assertEquals(1, $tag->count);
            $this->assertContains($tag->tag, $post->tags);
        }

        // Individual blog.
        $oublog = $this->get_new_oublog($course->id, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        $post = $this->get_post_stub($oublog->id);
        $post->tags = array('1', 'new', 'new', 'new2', 'a space');
        $postid = oublog_add_post($post, $cm, $oublog, $course);

        $tags = oublog_get_tags($oublog, 0, $cm, null, $USER->id);
        $this->assertCount(4, $tags);

        $tags = oublog_get_tags($oublog, 0, $cm, null, $stud1->id);
        $this->assertEmpty($tags);

        // Group blog.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => VISIBLEGROUPS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        $post = $this->get_post_stub($oublog->id);
        $post->groupid = $group1->id;
        $post->tags = array('1', 'new', 'new', 'new2', 'a space');
        $postid = oublog_add_post($post, $cm, $oublog, $course);

        $tags = oublog_get_tags($oublog, $group1->id, $cm);
        $this->assertCount(4, $tags);

        $tags = oublog_get_tags($oublog, $group2->id, $cm);
        $this->assertEmpty($tags);

        // Personal blog.
        if (!$oublog = $DB->get_record('oublog', array('global' => 1))) {
            $oublog = $this->get_new_oublog($SITE->id, array('global' => 1, 'maxvisibility' => OUBLOG_VISIBILITY_PUBLIC));
        }
        $cm = get_coursemodule_from_instance('oublog', $oublog->id);

        list($oublog, $bloginstance) = oublog_get_personal_blog($stud1->id);

        $post1stub = $this->get_post_stub($oublog->id);
        $post1stub->userid = $stud1->id;
        $post1stub->visibility = OUBLOG_VISIBILITY_COURSEUSER;// Private.
        $post1stub->tags = array('private');
        oublog_add_post($post1stub, $cm, $oublog, $SITE);
        $post2stub = $this->get_post_stub($oublog->id);
        $post2stub->userid = $stud1->id;
        $post2stub->visibility = OUBLOG_VISIBILITY_LOGGEDINUSER;// User must be logged in.
        $post2stub->tags = array('loggedin');
        oublog_add_post($post2stub, $cm, $oublog, $SITE);
        $post3stub = $this->get_post_stub($oublog->id);
        $post3stub->userid = $stud1->id;
        $post3stub->visibility = OUBLOG_VISIBILITY_PUBLIC;// Any user.
        $post3stub->tags = array('public');
        oublog_add_post($post3stub, $cm, $oublog, $SITE);

        $tags = oublog_get_tags($oublog, 0, $cm, $bloginstance->id);
        $this->assertCount(2, $tags);

        $this->setUser($stud1);
        $tags = oublog_get_tags($oublog, 0, $cm, $bloginstance->id);
        $this->assertCount(3, $tags);

        $this->setGuestUser();
        $tags = oublog_get_tags($oublog, 0, $cm, $bloginstance->id);
        $this->assertCount(1, $tags);

        $this->setUser($stud1);
        $post4stub = $this->get_post_stub($oublog->id);
        $post4stub->userid = $stud1->id;
        $post4stub->visibility = OUBLOG_VISIBILITY_PUBLIC;// Any user.
        // Add unordered alphabetic tags.
        $post4stub->tags = array('toad', 'newt', 'private', 'crock', 'loggedin', 'frog', 'public', 'dino');
        $postid = oublog_add_post($post4stub, $cm, $oublog, $course);
        $this->assertEquals(56, strlen(oublog_get_tags_csv($postid)));

        $post5stub = $this->get_post_stub($oublog->id);
        $post5stub->userid = $stud1->id;
        $post5stub->visibility = OUBLOG_VISIBILITY_PUBLIC;// Any user.
        // Add unordered alphabetic tags.
        $post5stub->tags = array('toad', 'private', 'crock', 'loggedin', 'frog', 'public', 'dino');
        $postid = oublog_add_post($post5stub, $cm, $oublog, $course);
        $this->assertEquals(50, strlen(oublog_get_tags_csv($postid)));

        // Recover tags with default ordering ie Alphabetic.
        $tags = oublog_get_tags($oublog, 0, $cm, null, -1);
        $this->assertCount(8, $tags);
        foreach ($tags as $tag) {
            $this->assertContains($tag->tag, $post4stub->tags);
        }
        $this->assertEquals('toad', $tag->tag);// Last in default, Alphabetical order.

        // Recover tags in Use order.
        $tags = oublog_get_tags($oublog, 0, $cm, null, -1, 'use');
        $this->assertCount(8, $tags);
        $lasttag = end($tags);
        $this->assertEquals('newt', $lasttag->tag);// Last when Use order specified.

        // Recover tags in Alphabetical order.
        $tags = oublog_get_tags($oublog, 0, $cm, null, -1, 'alpha');
        $this->assertCount(8, $tags);
        $lasttag = end($tags);
        $this->assertEquals('toad', $lasttag->tag);// Last when Alphabetic order specified.

        // Testing the create update of a blog instance with predefined tags.
        // set and also testing oublog_get_tag_list().
        // Whole course blog created with predefined tag set.
        $oublog = $this->get_new_oublog($course->id, array('tagslist' => 'blogtag01'));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        // Check that the predefined tag is inserted to the oublog_tags table.
        $blogtags = oublog_clarify_tags($oublog->tagslist);
        foreach ($blogtags as $tag) {
            $predefinedtags[] = $DB->get_record('oublog_tags', array('tag' => $tag));
        }
        // Confirm finding one predefined tag in the oublog_tags table.
        $this->assertCount(1, $predefinedtags);
        // Change the predefined tags on the blog.
        $oublog->tagslist = 'blogtag01, blogtag02';
        $oublog->instance = $oublog->id;
        oublog_update_instance($oublog);
        // Check that the changed tags are inserted to the oublog_tags table.
        $blogtags = oublog_clarify_tags($oublog->tagslist);
        foreach ($blogtags as $tag) {
            $changedtags[] = $DB->get_record('oublog_tags', array('tag' => $tag));
        }
        // Confirm finding predefined tags in the oublog_tags table.
        $this->assertCount(2, $changedtags);
        // Create post with 1 pre-defined and 1 user tag.
        $post = $this->get_post_stub($oublog->id);
        $post->tags = array('antelope', 'blogtag01');
        $postid = oublog_add_post($post, $cm, $oublog, $course);
        $this->assertEquals(19, strlen(oublog_get_tags_csv($postid)));
        // Recover post tags in default order.
        $tags = oublog_get_tags($oublog, 0, $cm, null, -1);
        foreach ($tags as $tag) {
            $this->assertEquals(1, $tag->count);
            $this->assertContains($tag->tag, $post->tags);
        }
        $this->assertNotEquals('antelope', $tag->tag);
        $this->assertEquals('blogtag01', $tag->tag);// Last in Alphabetical order.

        // Testing 'Set' tag only restrictions.
        // No restriction, recover full list of blog 'Set' and post tags.
        $oublog->restricttags = 0;
        $taglist = oublog_get_tag_list($oublog, 0, $cm, null, -1);
        $this->assertCount(3, $taglist);
        foreach ($taglist as $tag) {
            $fulltaglist[] = $tag->tag;
            if (isset($tag->label)) {
                // It should be an 'Official' ie. 'Set' predefined blog tag.
                $this->assertContains($tag->tag, $oublog->tagslist);
                $this->assertNotEmpty($tag->label);
                $this->assertGreaterThanOrEqual(0, $tag->count);
            }
            if (!isset($tag->label)) {
                // It should be the user post tag.
                $this->assertContains($tag->tag, $post->tags);
                $this->assertEquals('antelope', $tag->tag);
                $this->assertEquals(1, $tag->count);
            }
        }
        $this->assertContains('antelope', $fulltaglist);
        $this->assertContains('blogtag01', $fulltaglist);
        $this->assertContains('blogtag02', end($fulltaglist));// Last in full list.

        // Restriction applied, get restricted list of blog 'Set' tags.
        $oublog->restricttags = 1;
        $taglist = oublog_get_tag_list($oublog, 0, $cm, null, -1);
        $this->assertCount(2, $taglist);
        foreach ($taglist as $tag) {
            $restrictedtaglist[] = $tag->tag;
            if (isset($tag->label)) {
                // It should be an 'Official' ie. 'Set' predefined blog tag.
                $this->assertContains($tag->tag, $oublog->tagslist);
                $this->assertNotEmpty($tag->label);
                $this->assertGreaterThanOrEqual(0, $tag->count);
            }
            if (!isset($tag->label)) {
                $this->assertEmpty($tag->id);
                $this->assertEmpty($tag->weight);
                $this->assertEquals(0, $tag->count);
            }
        }
        $this->assertNotContains('antelope', $restrictedtaglist);
        $this->assertContains('blogtag01', $restrictedtaglist);
        $this->assertContains('blogtag02', end($restrictedtaglist));// Last in restricted list.
    }

    public function test_oublog_time_limits() {
        $this->resetAfterTest();
        $course = $this->get_new_course();
        $stud1 = $this->get_new_user('student', $course->id);

        // Future posts + comments start blog.
        $oublog = $this->get_new_oublog($course->id, array('postfrom' => 2524611600, 'commentfrom' => 2524611600));
        // Past posts + comments start blog.
        $oublog1 = $this->get_new_oublog($course->id, array('postfrom' => 1262307600, 'commentfrom' => 1262307600));
        // Future posts + comments end blog.
        $oublog2 = $this->get_new_oublog($course->id, array('postuntil' => 2524611600, 'commentuntil' => 2524611600));
        // Past posts + comments end blog.
        $oublog3 = $this->get_new_oublog($course->id, array('postuntil' => 1262307600, 'commentuntil' => 1262307600));

        $this->setAdminUser();
        $this->assertTrue(oublog_can_post_now($oublog, context_module::instance($oublog->cm->id)));
        $this->assertTrue(oublog_can_post_now($oublog1, context_module::instance($oublog1->cm->id)));
        $this->assertTrue(oublog_can_post_now($oublog2, context_module::instance($oublog2->cm->id)));
        $this->assertTrue(oublog_can_post_now($oublog3, context_module::instance($oublog3->cm->id)));
        $post = (object) array('allowcomments' => true, 'visibility' => OUBLOG_VISIBILITY_COURSEUSER);
        $this->assertTrue(oublog_can_comment($oublog->cm, $oublog, $post));
        $this->assertTrue(oublog_can_comment($oublog1->cm, $oublog1, $post));
        $this->assertTrue(oublog_can_comment($oublog2->cm, $oublog2, $post));
        $this->assertTrue(oublog_can_comment($oublog3->cm, $oublog3, $post));

        $this->setUser($stud1);
        $this->assertFalse(oublog_can_post_now($oublog, context_module::instance($oublog->cm->id)));
        $this->assertTrue(oublog_can_post_now($oublog1, context_module::instance($oublog1->cm->id)));
        $this->assertTrue(oublog_can_post_now($oublog2, context_module::instance($oublog2->cm->id)));
        $this->assertFalse(oublog_can_post_now($oublog3, context_module::instance($oublog3->cm->id)));
        $post = (object) array('allowcomments' => true, 'visibility' => OUBLOG_VISIBILITY_COURSEUSER);
        $this->assertFalse(oublog_can_comment($oublog->cm, $oublog, $post));
        $this->assertTrue(oublog_can_comment($oublog1->cm, $oublog1, $post));
        $this->assertTrue(oublog_can_comment($oublog2->cm, $oublog2, $post));
        $this->assertFalse(oublog_can_comment($oublog3->cm, $oublog3, $post));
    }

    /**
     * Tests the can_view_post access restriction function.
     */
    public function test_can_view_post() {
        global $USER;

        $this->resetAfterTest();
        $course = $this->get_new_course();

        // Set up users an groups:
        // Group 1*: student 1 only.
        // Group 2*: student 1, student 2.
        // Group 3: student 1, student 3.
        // Group 4*: student 2, student 3, student 4.
        // * = belongs to grouping.
        $stud1 = $this->get_new_user('student', $course->id);
        $stud2 = $this->get_new_user('student', $course->id);
        $stud3 = $this->get_new_user('student', $course->id);
        $stud4 = $this->get_new_user('student', $course->id);

        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);
        $group3 = $this->get_new_group($course->id);
        $group4 = $this->get_new_group($course->id);
        $grouping = $this->get_new_grouping($course->id);
        groups_assign_grouping($grouping->id, $group1->id);
        groups_assign_grouping($grouping->id, $group2->id);
        groups_assign_grouping($grouping->id, $group4->id);
        groups_add_member($group1, $stud1);
        groups_add_member($group2, $stud1);
        groups_add_member($group3, $stud1);
        groups_add_member($group2, $stud2);
        groups_add_member($group3, $stud3);
        groups_add_member($group4, $stud2);
        groups_add_member($group4, $stud3);
        groups_add_member($group4, $stud4);

        // Create roles for special staff.
        $generator = $this->getDataGenerator();
        $systemcontext = \context_system::instance();
        $aagroleid = $generator->create_role(['shortname' => 'aag']);
        assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $aagroleid, $systemcontext);
        $viroleid = $generator->create_role(['shortname' => 'vi']);
        assign_capability('mod/oublog:viewindividual', CAP_ALLOW, $viroleid, $systemcontext);

        // Give them student for general access, but add the special roles.
        $coursecontext = \context_course::instance($course->id);
        $staffaag = $this->get_new_user('student', $course->id);
        role_assign($aagroleid, $staffaag->id, $coursecontext);
        $staffvi = $this->get_new_user('student', $course->id);
        role_assign($viroleid, $staffvi->id, $coursecontext);
        $staffvigroups = $this->get_new_user('student', $course->id);
        role_assign($viroleid, $staffvigroups->id, $coursecontext);
        groups_add_member($group1, $staffvigroups);
        groups_add_member($group3, $staffvigroups);
        $staffaagvi = $this->get_new_user('student', $course->id);
        role_assign($aagroleid, $staffaagvi->id, $coursecontext);
        role_assign($viroleid, $staffaagvi->id, $coursecontext);

        // Normal blog.
        $blogs = [];
        $blogs['normal'] = $this->get_new_oublog($course->id);
        // Visible individual, no groups
        $blogs['vi'] = $this->get_new_oublog($course->id,
                ['individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS]);
        // Separate individual, no groups
        $blogs['si'] = $this->get_new_oublog($course->id,
                ['individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS]);
        // Visible groups.
        $blogs['vg'] = $this->get_new_oublog($course->id,
                ['groupmode' => VISIBLEGROUPS]);
        // Separate groups.
        $blogs['sg'] = $this->get_new_oublog($course->id,
                ['groupmode' => SEPARATEGROUPS]);
        // Separate groups with a grouping.
        $blogs['sgg'] = $this->get_new_oublog($course->id,
                ['groupmode' => SEPARATEGROUPS, 'groupingid' => $grouping->id]);
        // Visible individual, separate groups.
        $blogs['visg'] = $this->get_new_oublog($course->id,
                ['individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS, 'groupmode' => SEPARATEGROUPS]);
        // Visible individual, separate groups with a grouping.
        $blogs['visgg'] = $this->get_new_oublog($course->id,
                ['individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS, 'groupmode' => SEPARATEGROUPS,
                'groupingid' => $grouping->id]);
        // Separate individual, separate groups.
        $blogs['sisg'] = $this->get_new_oublog($course->id,
                ['individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS, 'groupmode' => SEPARATEGROUPS]);
        // Separate individual, separate groups with a grouping.
        $blogs['sisgg'] = $this->get_new_oublog($course->id,
                ['individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS, 'groupmode' => SEPARATEGROUPS,
                'groupingid' => $grouping->id]);

        // Normal and individuals blogs - posts from each individual.
        $blogposts = [];
        foreach ([$stud1, $stud2, $stud3, $stud4] as $user) {
            $this->setUser($user);
            foreach (['normal', 'vi', 'si', 'visg', 'visgg', 'sisg', 'sisgg'] as $key) {
                if (!array_key_exists($key, $blogposts)) {
                    $blogposts[$key] = [];
                }
                $blogposts[$key][] = $this->get_new_post($blogs[$key]);
            }
        }

        // Group blogs - posts in each group (all by user 4).
        $this->setUser($stud4);
        foreach ([$group1, $group2, $group3, $group4] as $group) {
            foreach (['vg', 'sg', 'sgg'] as $key) {
                // Skip the wrong-grouping one.
                if ($key === 'sgg' && $group === $group3) {
                    continue;
                }
                if (!array_key_exists($key, $blogposts)) {
                    $blogposts[$key] = [];
                }
                $blogposts[$key][] = $this->get_new_post($blogs[$key],
                        (object)['groupid' => $group->id]);
            }
        }

        $results = [];
        foreach ([$stud1, $staffaag, $staffvi, $staffaagvi, $staffvigroups] as $user) {
            $this->setUser($user);
            $result = '';

            // Check access to each post.
            foreach ($blogposts as $key => $postids) {
                $oublog = $blogs[$key];
                list ($course, $cm) = get_course_and_cm_from_instance($oublog, 'oublog');
                $context = context_module::instance($cm->id);
                $result .= $key . ':';
                foreach ($postids as $index => $postid) {
                    $post = oublog_get_post($postid);
                    $allow = oublog_can_view_post($post, $USER, $context, $cm, $oublog);
                    $result .= $allow ? 't' : 'f';
                }
                $result .= ',';
            }

            $results[$user->id] = $result;
        }

        // Test for the student account and for the staff accounts with special permissions.
        $this->assertEquals('normal:tttt,vi:tttt,si:tfff,visg:tttf,visgg:ttff,sisg:tfff,sisgg:tfff,vg:tttt,sg:tttf,sgg:ttf,',
                $results[$stud1->id]);
        $this->assertEquals('normal:tttt,vi:tttt,si:ffff,visg:tttt,visgg:tttt,sisg:ffff,sisgg:ffff,vg:tttt,sg:tttt,sgg:ttt,',
                $results[$staffaag->id]);
        $this->assertEquals('normal:tttt,vi:tttt,si:tttt,visg:ffff,visgg:ffff,sisg:ffff,sisgg:ffff,vg:tttt,sg:ffff,sgg:fff,',
                $results[$staffvi->id]);
        $this->assertEquals('normal:tttt,vi:tttt,si:tttt,visg:tttt,visgg:tttt,sisg:tttt,sisgg:tttt,vg:tttt,sg:tttt,sgg:ttt,',
                $results[$staffaagvi->id]);
        $this->assertEquals('normal:tttt,vi:tttt,si:tttt,visg:tftf,visgg:tfff,sisg:tftf,sisgg:tfff,vg:tttt,sg:tftf,sgg:tff,',
                $results[$staffvigroups->id]);
    }
}
