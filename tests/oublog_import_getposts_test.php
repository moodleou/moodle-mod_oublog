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

global $CFG;
require_once($CFG->dirroot . '/mod/oublog/tests/oublog_test_lib.php');
require_once($CFG->dirroot . '/mod/oublog/locallib.php');

class oublog_import_getposts_test extends oublog_test_lib
{
    protected $course1;
    protected $course2;
    protected $course3;

    private function create_content($instance, $record = array()) {
        global $USER, $DB, $CFG;
        require_once($CFG->dirroot . '/mod/oublog/locallib.php');

        $cm = get_coursemodule_from_instance('oublog', $instance->id);
        $context = context_module::instance($cm->id);
        $course = get_course($instance->course);

        if (isset($record['comment'])) {
            if (empty($record['comment']->postid)) {
                throw new coding_exception('Must pass postid when creating comment');
            }
            if (empty($record['comment']->userid)) {
                $record['comment']->userid = $USER->id;
            }
            if (empty($record['comment']->messagecomment)) {
                if (empty($record['comment']->message)) {
                    $record['comment']->messagecomment = array('text' => 'Test comment');
                } else {
                    // Support message being string to insert in db not form style.
                    $record['comment']->messagecomment = array('text' => $record['comment']->message);
                }
            } else {
                if (is_string($record['comment']->messagecomment)) {
                    // Support message being string to insert in db not form style.
                    $record['comment']->messagecomment = array('text' => $record['comment']->messagecomment);
                }
            }
            return oublog_add_comment($course, $cm, $instance, $record['comment']);
        }
        return null;
    }

    protected function setUp() {
        $this->resetAfterTest(true);

        $this->course1 = $this->getDataGenerator()->create_course(array(
            'fullname' => 'Course1',
            'shortname' => 'C1'
        ));
        $this->course2 = $this->getDataGenerator()->create_course(array(
            'fullname' => 'Course2',
            'shortname' => 'C2'
        ));
        $this->course3 = $this->getDataGenerator()->create_course(array(
            'fullname' => 'Course3',
            'shortname' => 'C3'
        ));

        $user = $this->get_new_user();
        $this->setUser($user);
    }


    /**
     * Blog don't have any post, select wrong post id.
     */
    public function test_oublog_import_getposts_test_nopost_selectwrongpost() {
        global $USER;

        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog = $this->get_new_oublog($this->course1, array('global' => 1, 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));

        $result = oublog_import_getposts($blog->id, $blog->cm->id, array(100000, 200000));

        // Expect return nothing.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     * Blog have 1 post, select wrong post id.
     */
    public function test_oublog_import_getposts_test_1post_selectwrong() {
        global $USER;

        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog = $this->get_new_oublog($this->course1, array('global' => 1, 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog);
        $result = oublog_import_getposts($blog->id, $blog->cm->id, array(123));

        // Expect return nothing.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     * Blog have 1 post, select correct id.
     */
    public function test_oublog_import_getposts_test_1post_selectcorrect() {
        global $USER;

        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog = $this->get_new_oublog($this->course1, array('global' => 1, 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));
        $post1id = $this->get_new_post($blog);
        $result = oublog_import_getposts($blog->id, $blog->cm->id, array($post1id));

        // Expect return one post.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[$post1id]->id, $post1id);
        $this->assertEquals($result[$post1id]->title, 'testpost');
        $this->assertEquals($result[$post1id]->message, '<p>newpost</p>');
    }

    /**
     * Blog have 2 posts, select correct id.
     */
    public function test_oublog_import_getposts_test_2post_selectcorrect() {
        global $USER;

        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog = $this->get_new_oublog($this->course1, array('global' => 1, 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));
        $post1id = $this->get_new_post($blog);
        $post2id = $this->get_new_post($blog);

        $result = oublog_import_getposts($blog->id, $blog->cm->id, array($post1id, $post2id));

        // Expect return 2 posts.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 2);
        $this->assertEquals($result[$post1id]->id, $post1id);
        $this->assertEquals($result[$post2id]->id, $post2id);
    }

    /**
     * Blog have 1 posts and have 2 comments, select correct id, include comment = false.
     */
    public function test_oublog_import_getposts_test_1post_2comment_notinclude() {
        global $USER;

        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog = $this->get_new_oublog($this->course1, array('global' => 1, 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));
        $post1 = $this->get_post_stub($blog->id);

        $post1id = $this->get_new_post($blog, $post1);

        $comment1 = $this->get_comment_stub($post1id, $USER->id);
        $comment2 = $this->get_comment_stub($post1id, $USER->id);
        $this->create_content($blog, array('comment' => (object)clone $comment1));
        $this->create_content($blog, array('comment' => (object)clone $comment2));

        $result = oublog_import_getposts($blog->id, $blog->cm->id, array($post1id), false);

        // Expect return 1 post with no comment.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[$post1id]->id, $post1id);
        $this->assertEquals(count($result[$post1id]->comments), 0);
    }

    /**
     * Blog have 1 posts and have 2 comments, select correct id, include comment = true.
     */
    public function test_oublog_import_getposts_test_1post_2comment_included() {
        global $USER;

        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog = $this->get_new_oublog($this->course1, array('global' => 1, 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));

        $post1 = $this->get_post_stub($blog->id);
        $post1id = $this->get_new_post($blog, $post1);

        $comment1 = $this->get_comment_stub($post1id, $USER->id);
        $comment1->title = 'Comment1';
        $comment1->messagecomment = 'Comment 1 message';
        $comment2 = $this->get_comment_stub($post1id, $USER->id);
        $comment2->title = 'Comment2';
        $comment2->messagecomment = 'Comment 2 message';

        $comment1id = $this->create_content($blog, array('comment' => (object)clone $comment1));
        $comment2id = $this->create_content($blog, array('comment' => (object)clone $comment2));

        $result = oublog_import_getposts($blog->id, $blog->cm->id, array($post1id), true);

        // Expect return 1 post with 2 comments.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[$post1id]->id, $post1id);
        $this->assertEquals(count($result[$post1id]->comments), 2);
        $this->assertEquals($result[$post1id]->comments[$comment1id]->id, $comment1id);
        $this->assertEquals($result[$post1id]->comments[$comment1id]->title, $comment1->title);
        $this->assertEquals($result[$post1id]->comments[$comment1id]->message, $comment1->messagecomment);
        $this->assertEquals($result[$post1id]->comments[$comment2id]->id, $comment2id);
        $this->assertEquals($result[$post1id]->comments[$comment2id]->title, $comment2->title);
        $this->assertEquals($result[$post1id]->comments[$comment2id]->message, $comment2->messagecomment);
    }

    /**
     * Blog have 2 posts, 1 post deleted, select correct id.
     */
    public function test_oublog_import_getposts_test_1post_2comment_1deleted() {
        global $USER;

        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog = $this->get_new_oublog($this->course1, array('global' => 1, 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));

        $post1 = $this->get_post_stub($blog->id);
        $post1id = $this->get_new_post($blog, $post1);

        $comment1 = $this->get_comment_stub($post1id, $USER->id);
        $comment1->deletedby = $USER->id;
        $comment2 = $this->get_comment_stub($post1id, $USER->id);

        $this->create_content($blog, array('comment' => (object)clone $comment1));
        $comment2id = $this->create_content($blog, array('comment' => (object)clone $comment2));

        $result = oublog_import_getposts($blog->id, $blog->cm->id, array($post1id), true);

        // Expect return 1 post with only comment2 because the comment1 has been deleted.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[$post1id]->id, $post1id);
        $this->assertEquals(count($result[$post1id]->comments), 1);
        $this->assertEquals($result[$post1id]->comments[$comment2id]->id, $comment2id);
    }

    /**
     * Blog have 2 posts, 1 post not belong to current user, select correct id.
     */
    public function test_oublog_import_getposts_test_1post_2comment_1notbelongtouser() {
        global $USER;

        $anotheruser = $this->get_new_user();

        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog = $this->get_new_oublog($this->course1, array('global' => 1, 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));

        $post1 = $this->get_post_stub($blog->id);
        $post1id = $this->get_new_post($blog, $post1);

        $comment1 = $this->get_comment_stub($post1id, $anotheruser->id);
        $comment2 = $this->get_comment_stub($post1id, $USER->id);

        $this->create_content($blog, array('comment' => (object)clone $comment1));
        $comment2id = $this->create_content($blog, array('comment' => (object)clone $comment2));

        $result = oublog_import_getposts($blog->id, $blog->cm->id, array($post1id), true);

        // Expect return 1 post with only with comment2 only because comment1 belong to other user.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[$post1id]->id, $post1id);
        $this->assertEquals(count($result[$post1id]->comments), 1);
        $this->assertEquals($result[$post1id]->comments[$comment2id]->id, $comment2id);
    }
}