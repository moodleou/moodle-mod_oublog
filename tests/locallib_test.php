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

    // TODO: Unit tests do NOT cover:
     * Personal blog auto creation on install has worked
     * Access permissions (oublog_check_view_permissions + oublog_can_view_post, oublog_can_post + oublog_can_comment)
     * Tags
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
        $this->assertEquals($titlecheck . '_' . $postcount, $posts[$postcount]->title);
        // Check fullname details returned for post author.
        $this->assertEquals(fullname($USER), fullname($posts[$postcount]));

        // Check deleted posts not shown.
        $deleteinfo = new stdClass();
        $deleteinfo->deletedby = $USER->id;
        $deleteinfo->id = $posts[$postcount]->id;
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
        $this->assertEquals($titlecheck . '_' . $postcount, $posts[$postcount]->title);

        // Specific (last) group.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, $groups[count($groups)]);
        $this->assertEquals(1, $recordcount);
        $this->assertEquals($titlecheck . '_' . $postcount, $posts[$postcount]->title);
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
            $oublog = $this->get_new_oublog($SITE->id, array('global' => 1, 'maxvisibility' => OUBLOG_VISIBILITY_PUBLIC));
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
        $postcount = OUBLOG_POSTS_PER_PAGE;
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
        $offset = $page * OUBLOG_POSTS_PER_PAGE;
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, 0, -1, null, '', true, true);
        $this->assertEquals(OUBLOG_POSTS_PER_PAGE * 2 + 2, $recordcount);// Includes count of lesser visibility posts.
        $this->assertCount(OUBLOG_POSTS_PER_PAGE, $posts);// Includes only paged visibile posts.
        // Setup pagination offset count for page 2.
        $page = 1;
        $offset = $page * OUBLOG_POSTS_PER_PAGE;
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, 0, -1, null, '', true, true);
        $this->assertCount(OUBLOG_POSTS_PER_PAGE, $posts);// Includes only paged visibile posts.
        // Setup pagination offset count for page 3.
        $page = 2;
        $offset = $page * OUBLOG_POSTS_PER_PAGE;
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

        $post = $this->get_post_stub($oublog->id);
        $postid = oublog_add_post($post, $cm, $oublog, $course);
        $timeposted = $DB->get_field('oublog_posts', 'timeposted', array('id' => $postid));
        $lastmodified = oublog_get_last_modified($cm, $course, $USER->id);
        $this->assertTrue(is_numeric($lastmodified));
        $this->assertEquals($timeposted, $lastmodified);
        // TODO: More comprehensive checking with separate group/individual blogs.
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
        $postcount = OUBLOG_POSTS_PER_PAGE + (OUBLOG_POSTS_PER_PAGE / 2);
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
        $offset = $page * OUBLOG_POSTS_PER_PAGE;
        // Get a list of the pages posts.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, 0);
        // Same number of records discovered that were created?
        $this->assertEquals($postcount, $recordcount);
        // Is the number of posts returned that were expected?.
        $this->assertEquals(OUBLOG_POSTS_PER_PAGE, count($posts));

        // Build paging parameters for the second page.
        $page = 1;
        $offset = $page * OUBLOG_POSTS_PER_PAGE;
        // Get the list of the second pages posts.
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, 0);
        // Number of posts returned that were expected?.
        $this->assertEquals($postcount - $offset, count($posts));
    }
}
