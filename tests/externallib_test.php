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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/oublog/tests/oublog_test_lib.php');
require_once($CFG->dirroot . '/mod/oublog/externallib.php');

class externallib_test extends oublog_test_lib
{
    /** @var string Example username in CDC format. */
    const TEST_CDC_ID = '0123456789abcdef0123456789abcdef';

    protected $course1;
    protected $blog;

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

        $userdetails = ['email' => 'user@example.com', 'username' => 'abc123'];
        if (mod_oublog_external::is_ou()) {
            $userdetails['auth'] = 'sams';
        }
        $user = $this->getDataGenerator()->create_user($userdetails);
        $this->setUser($user);
    }

    // Test get_user_blogs.

    /**
     * Empty username.
     */
    public function test_get_user_blogs_nousername() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_user_blogs('');

        // Expect return empty because no user name was given.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     *  Username with special character.
     */
    public function test_get_user_blogs_username_with_specialcharacter() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect invalid_parameter_exception because the passed username contain special character.
        $this->expectException('invalid_parameter_exception');

        mod_oublog_external::get_user_blogs('username1****');
    }

    /**
     *  Valid username but not existed.
     */
    public function test_get_user_blogs_username_valid_notexist() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_user_blogs('username2');

        // Expect return nothing because the user name is not exist.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     *  Valid username and existed.
     */
    public function test_get_user_blogs_username_valid_exist() {
        global $USER;

        $blog1 = $this->get_new_oublog($this->course1->id,
            array('name' => 'Blog1', 'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $blog2 = $this->get_new_oublog($this->course1->id,
            array('name' => 'Blog2', 'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog1);
        $this->get_new_post($blog1);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_user_blogs($USER->username);

        // Expect return 2 blog and the number of comment of blog1 is 2, blog2 is 0..
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 2);
        $this->assertEquals($result[0]->cmid, $blog1->cm->id);
        $this->assertEquals($result[0]->remote, 1);
        $this->assertEquals($result[0]->numposts, 2);
        $this->assertEquals($result[1]->cmid, $blog2->cm->id);
        $this->assertEquals($result[1]->remote, 1);
        $this->assertEquals($result[1]->numposts, 0);
    }

    /**
     * Tests get_user_blogs(2) using OU identifiers.
     */
    public function test_get_user_blogs_ou_identifiers() {
        global $USER;
        $this->skip_outside_ou();

        // Create two blogs for user's course.
        $this->get_new_oublog($this->course1->id,
                ['name' => 'Blog1', 'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS]);
        $this->get_new_oublog($this->course1->id,
                ['name' => 'Blog2', 'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS]);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Check both functions work with OUCU before changeover.
        $result = mod_oublog_external::get_user_blogs('abc123');
        $this->assertCount(2, $result);
        $result = mod_oublog_external::get_user_blogs2(['oucu' => 'abc123']);
        $this->assertCount(2, $result);

        // Do user changeover.
        $this->change_user_to_cdc();

        // Check both functions work with OUCU.
        $result = mod_oublog_external::get_user_blogs('abc123');
        $this->assertCount(2, $result);
        $result = mod_oublog_external::get_user_blogs2(['oucu' => 'abc123']);
        $this->assertCount(2, $result);

        // Check the second function works with CDC id too.
        $result = mod_oublog_external::get_user_blogs2(['cdcid' => self::TEST_CDC_ID]);
        $this->assertCount(2, $result);
    }

    // Test get_blog_posts.

    /**
     *  Pass empty blogid other field valid and exist.
     */
    public function test_get_blog_posts_blogid_empty() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_posts(null, $blog->cm->id, $post1id, true, $USER->username);

        // Expect return nothing because blogid as empty.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     *  Pass blogid as string other field valid and exist.
     */
    public function test_get_blog_posts_blogid_as_string() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception because blogid expect integer pass a string.
        $this->expectException('invalid_parameter_exception');

        mod_oublog_external::get_blog_posts('a', $blog->cm->id, $post1id, true, $USER->username);
    }

    /**
     *  Pass empty $bcontextid other field valid and exist.
     */
    public function test_get_blog_posts_bcontextid_empty() {
        global $USER;
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_posts($blog->id, null, $post1id, true, $USER->username);

        // Expect return 1 post.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[$post1id]->id, $post1id);
    }

    /**
     *  Pass $bcontextid as string other field valid and exist.
     */
    public function test_get_blog_posts_bcontextid_as_string() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception because bcontextid expect integer pass a string.
        $this->expectException('invalid_parameter_exception');

        mod_oublog_external::get_blog_posts($blog->id, 'a', $post1id, true, $USER->username);
    }

    /**
     *  Pass empty selected other field valid and exist.
     */
    public function test_get_blog_posts_selected_empty() {
        global $USER;
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_posts($blog->id, $blog->cm->id, null, true, $USER->username);

        // Expect return all (2 posts).
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 2);
    }

    /**
     *  Pass selected contain character other than number and comma other field valid and exist.
     */
    public function test_get_blog_posts_selected_special_character() {
        global $USER;
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception.
        $this->expectException('invalid_parameter_exception');

        mod_oublog_external::get_blog_posts($blog->id, $blog->cm->id, '1,2;3', true, $USER->username);
    }

    /**
     *  Pass not existed postid other field valid and exist.
     */
    public function test_get_blog_posts_selected_not_exist() {
        global $USER;
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_posts($blog->id, $blog->cm->id, '1,2,3', true, $USER->username);

        // Expect no data return.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     *  Pass empty inccomments, other field valid and exist
     */
    public function test_get_blog_posts_selected_inccomments_empty() {
        global $USER;
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $comment1 = $this->get_comment_stub($post1id, $USER->id);
        $comment2 = $this->get_comment_stub($post1id, $USER->id);
        $this->create_content($blog, array('comment' => (object)clone $comment1));
        $this->create_content($blog, array('comment' => (object)clone $comment2));
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_posts($blog->id, $blog->cm->id, $post1id, null, $USER->username);

        // Expect return 1 post with no comment.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[$post1id]->id, $post1id);
        $this->assertEquals(count($result[$post1id]->comments), 0);
    }

    /**
     *  Pass inccomments = false, other field valid and exist
     */
    public function test_get_blog_posts_selected_inccomments_false() {
        global $USER;
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $comment1 = $this->get_comment_stub($post1id, $USER->id);
        $comment2 = $this->get_comment_stub($post1id, $USER->id);
        $this->create_content($blog, array('comment' => (object)clone $comment1));
        $this->create_content($blog, array('comment' => (object)clone $comment2));
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_posts($blog->id, $blog->cm->id, $post1id, false, $USER->username);

        // Expect return 1 post with no comment.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[$post1id]->id, $post1id);
        $this->assertEquals(count($result[$post1id]->comments), 0);
    }

    /**
     *  Pass inccomments = true, other field valid and exist
     */
    public function test_get_blog_posts_selected_inccomments_true() {
        global $USER;
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $comment1 = $this->get_comment_stub($post1id, $USER->id);
        $comment2 = $this->get_comment_stub($post1id, $USER->id);
        $this->create_content($blog, array('comment' => (object)clone $comment1));
        $this->create_content($blog, array('comment' => (object)clone $comment2));
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_posts($blog->id, $blog->cm->id, $post1id, true, $USER->username);

        // Expect return 1 post with 2 comments.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[$post1id]->id, $post1id);
        $this->assertEquals(count($result[$post1id]->comments), 2);
    }

    /**
     *  Pass empty username, other field valid and exist
     */
    public function test_get_blog_posts_username_empty() {
        global $USER;
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_posts($blog->id, $blog->cm->id, $post1id, true, '');

        // Expect return nothing.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     *  Pass not existed username, other field valid and exist
     */
    public function test_get_blog_posts_username_not_exist() {
        global $USER;
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_posts($blog->id, $blog->cm->id, $post1id, true, 'username2');

        // Expect return nothing.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     *  Pass username contain special character, other field valid and exist
     */
    public function test_get_blog_posts_username_with_special_characters() {
        global $USER;
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception.
        $this->expectException('invalid_parameter_exception');

        mod_oublog_external::get_blog_posts($blog->id, $blog->cm->id, $post1id, true, 'username1&');
    }

    /**
     * Tests get_blog_posts(2) using OU identifiers.
     */
    public function test_get_blog_posts_ou_identifiers() {
        global $USER;
        $this->skip_outside_ou();

        // Create blog with one post.
        $blog = $this->get_new_oublog($this->course1->id);
        $post1id = $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Check both functions work with OUCU before changeover.
        $result = mod_oublog_external::get_blog_posts($blog->id, null, $post1id, true,
                'abc123');
        $this->assertCount(1, $result);
        $result = mod_oublog_external::get_blog_posts2($blog->id, null, $post1id, true,
                ['oucu' => 'abc123']);
        $this->assertCount(1, $result);

        // Do user changeover.
        $this->change_user_to_cdc();

        // Check both functions work with OUCU.
        $result = mod_oublog_external::get_blog_posts($blog->id, null, $post1id, true,
                'abc123');
        $this->assertCount(1, $result);
        $result = mod_oublog_external::get_blog_posts2($blog->id, null, $post1id, true,
                ['oucu' => 'abc123']);
        $this->assertCount(1, $result);

        // Check the second function works with CDC id too.
        $result = mod_oublog_external::get_blog_posts2($blog->id, null, $post1id, true,
                ['cdcid' => self::TEST_CDC_ID]);
        $this->assertCount(1, $result);
    }

    // Test get_blog_allposts.

    /**
     *  Pass empty blogid, other field valid and exist.
     */
    public function test_get_blog_allposts_empty_blogid() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_allposts(null, 'timeposted DESC', $USER->username);
        // Expect return no post because blogid is null.
        $this->assertNotNull($result);
        $this->assertEquals(count($result['posts']), 0);
    }

    /**
     *  Pass blogid as string, other field valid and exist.
     */
    public function test_get_blog_allposts_blogid_as_string() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception because blogid expect int but pass string.
        $this->expectException('invalid_parameter_exception');
        mod_oublog_external::get_blog_allposts('string', 'timeposted DESC', $USER->username);
    }

    /**
     *  Pass blogid valid but not exist, other field valid and exist.
     */
    public function test_get_blog_allposts_blogid_not_exist() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_allposts(-1, 'timeposted DESC', $USER->username);
        // Expect return no post because blogid not exist.
        $this->assertNotNull($result);
        $this->assertEquals(count($result['posts']), 0);
    }

    /**
     *  Pass empty sort, other field valid and exist.
     */
    public function test_get_blog_allposts_empty_sort() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception because sort expect not null but pass empty.
        $this->expectException('dml_read_exception');
        mod_oublog_external::get_blog_allposts($blog->id, null, $USER->username);
    }

    /**
     *  Pass sort as int, other field valid and exist.
     */
    public function test_get_blog_allposts_sort_as_int() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception because sort expect text but pass int.
        $this->expectException('dml_read_exception');
        mod_oublog_external::get_blog_allposts($blog->id, 1, $USER->username);
    }

    /**
     *  Pass empty username, other field valid and exist.
     */
    public function test_get_blog_allposts_empty_username() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect no data return because username is empty.
        $result = mod_oublog_external::get_blog_allposts($blog->id, 'timeposted DESC', '');
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     *  Pass username with special character, other field valid and exist.
     */
    public function test_get_blog_allposts_username_with_special_character() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception because username have special character.
        $this->expectException('invalid_parameter_exception');
        mod_oublog_external::get_blog_allposts($blog->id, 'timeposted DESC', 'user$');
    }

    /**
     *  Pass username valid but not exist, other field valid and exist.
     */
    public function test_get_blog_allposts_username_not_exist() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect no data return because username not exist.
        $result = mod_oublog_external::get_blog_allposts($blog->id, 'timeposted DESC', 'user1');
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     *  Pass page as string, other field valid and exist.
     */
    public function test_get_blog_allposts_page_as_string() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception because page expect int but pass string.
        $this->expectException('invalid_parameter_exception');
        mod_oublog_external::get_blog_allposts($blog->id, 'timeposted DESC', $USER->username, 'string');
    }

    /**
     *  Pass tags not in format sequence type, other field valid and exist.
     */
    public function test_get_blog_allposts_tags_not_sequence_type() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception because tags not in format sequence type.
        $this->expectException('invalid_parameter_exception');
        mod_oublog_external::get_blog_allposts($blog->id, 'timeposted DESC', $USER->username, 0, '1.2.3');
    }

    /**
     *  Pass all field valid and exist.
     */
    public function test_get_blog_allposts_all_field_valid_and_exist() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect return 1 post.
        $result = mod_oublog_external::get_blog_allposts($blog->id, 'timeposted DESC', $USER->username);
        $this->assertNotNull($result);
        $this->assertEquals(count($result['posts']), 1);
        $this->assertEquals($result['total'], 1);
    }

    /**
     * Tests get_blog_allposts(2) using OU identifiers.
     */
    public function test_get_blog_allposts_ou_identifiers() {
        global $USER;
        $this->skip_outside_ou();

        // Create blog with one post.
        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Check both functions work with OUCU before changeover.
        $result = mod_oublog_external::get_blog_allposts($blog->id, 'timeposted DESC',
                'abc123');
        $this->assertCount(1, $result['posts']);
        $result = mod_oublog_external::get_blog_allposts2($blog->id, 'timeposted DESC',
                ['oucu' => 'abc123']);
        $this->assertCount(1, $result['posts']);

        // Do user changeover.
        $this->change_user_to_cdc();

        // Check both functions work with OUCU.
        $result = mod_oublog_external::get_blog_allposts($blog->id, 'timeposted DESC',
                'abc123');
        $this->assertCount(1, $result['posts']);
        $result = mod_oublog_external::get_blog_allposts2($blog->id, 'timeposted DESC',
                ['oucu' => 'abc123']);
        $this->assertCount(1, $result['posts']);

        // Check the second function works with CDC id too.
        $result = mod_oublog_external::get_blog_allposts2($blog->id, 'timeposted DESC',
                ['cdcid' => self::TEST_CDC_ID]);
        $this->assertCount(1, $result['posts']);
    }

    // Test get_blog_info.

    /**
     *  Pass empty cmid, other field valid and exist.
     */
    public function test_get_blog_info_nocmid() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_info(null, $USER->id);
        // Expect return nothing because cmid is null.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     *  Pass cmid as string, other field valid and exist.
     */
    public function test_get_blog_info_cmid_as_string() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception because cmid expect int but pass string.
        $this->expectException('invalid_parameter_exception');

        mod_oublog_external::get_blog_info('some string', $USER->id);
    }

    /**
     *  Pass cmid valid but not exist, other field valid and exist.
     */
    public function test_get_blog_info_cmid_not_exist() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_info(-1, $USER->id);

        // Expect return nothing because cmid is not exist.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     * Pass empty username, other field valid and exist.
     */
    public function test_get_blog_info_empty_username() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_info($blog->cm->id, '');

        // Expect return nothing because cmid is not exist.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     * Pass username which contain special characters, other field valid and exist.
     */
    public function test_get_blog_info_username_with_special_characters() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Expect exception because username contain special characters.
        $this->expectException('invalid_parameter_exception');

        mod_oublog_external::get_blog_info($blog->cm->id, 'username1&');
    }

    /**
     *  Pass username valid but not exist, other field valid and exist.
     */
    public function test_get_blog_info_username_not_exist() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id);
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_info($blog->cm->id, 'username2');

        // Expect return nothing because username is not exist.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     *  Pass all field valid and exist.
     */
    public function test_get_blog_info_username_all_valid() {
        global $USER;

        $blog = $this->get_new_oublog($this->course1->id,
            array('name' => 'Blog2', 'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = mod_oublog_external::get_blog_info($blog->cm->id, $USER->username);
        // Expect return blog info.
        $this->assertNotNull($result);
        $this->assertEquals($result['bcmid'], $blog->cm->id);
        $this->assertEquals($result['boublogid'], $blog->id);
        $this->assertEquals($result['boublogname'],
        $this->course1->shortname . ' ' . $this->course1->fullname . ' : ' . $blog->name);
        $this->assertEquals($result['bcoursename'], $this->course1->shortname);
    }

    /**
     * Tests get_blog_info(2) using OU identifiers.
     */
    public function test_get_blog_info_ou_identifiers() {
        global $USER;
        $this->skip_outside_ou();

        // Create blog with one post.
        $blog = $this->get_new_oublog($this->course1->id,
                array('name' => 'Blog2', 'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog);
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Check both functions work with OUCU before changeover.
        $result = mod_oublog_external::get_blog_info($blog->cm->id, 'abc123');
        $this->assertEquals($blog->id, $result['boublogid']);
        $result = mod_oublog_external::get_blog_info2($blog->cm->id, ['oucu' => 'abc123']);
        $this->assertEquals($blog->id, $result['boublogid']);

        // Do user changeover.
        $this->change_user_to_cdc();

        // Check both functions work with OUCU.
        $result = mod_oublog_external::get_blog_info($blog->cm->id, 'abc123');
        $this->assertEquals($blog->id, $result['boublogid']);
        $result = mod_oublog_external::get_blog_info2($blog->cm->id, ['oucu' => 'abc123']);
        $this->assertEquals($blog->id, $result['boublogid']);

        // Check the second function works with CDC id too.
        $result = mod_oublog_external::get_blog_info2($blog->cm->id, ['cdcid' => self::TEST_CDC_ID]);
        $this->assertEquals($blog->id, $result['boublogid']);
    }

    /**
     * Some of these tests are only relevant in the OU installation, because we have a very weird
     * system of user identifiers.
     */
    protected function skip_outside_ou() {
        if (!mod_oublog_external::is_ou()) {
            $this->markTestSkipped('Test only relevant in Open University installation');
        }
    }

    /**
     * Changes the user account to a CDC format in the way that will happen after a CDC login.
     */
    protected function change_user_to_cdc() {
        global $DB, $USER;

        // Change main user table.
        $update = ['id' => $USER->id, 'username' => self::TEST_CDC_ID, 'auth' => 'saml2'];
        $DB->update_record('user', $update);

        // Set custom field for OUCU.
        \local_oudataload\users::set_custom_field($USER, \local_ousaml\attributes::USER_FIELD_OUCU, 'abc123');
    }
}
