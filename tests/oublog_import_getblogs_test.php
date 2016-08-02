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

class oublog_import_getblogs_test extends oublog_test_lib
{
    protected $course1;
    protected $course2;
    protected $course3;

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
     * Test the case user have not enrolled into the course (but the course have blog and post).
     */
    public function test_oublog_import_getblogs_noenroll() {
        // Create blog and post for course1.
        $blog = $this->get_new_oublog($this->course1);
        $this->get_new_post($blog);

        $result = oublog_import_getblogs();

        // Expect no data return.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     * Test the case user enrolled into the course, but the course don't have any blog and post.
     */
    public function test_oublog_import_getblogs_enrollnoblog() {
        global $USER;

        // Enroll current user into course 1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $result = oublog_import_getblogs();

        // Expect no data return.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     * Test the case user enrolled into the course, but the course don't have any post.
     */
    public function test_oublog_import_getblogs_enrollnopost() {
        global $USER;

        // Enroll current user into course 1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $this->get_new_oublog($this->course1);

        $result = oublog_import_getblogs();

        // Expect no data return.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     * Test the case user enrolled into the course, the course have 1 blog(visible) and 2 posts.
     */
    public function test_oublog_import_getblogs_enroll_1blog_2posts() {
        global $USER;

        // Enroll current user into course 1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog = $this->get_new_oublog($this->course1, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog);
        $this->get_new_post($blog);

        $result = oublog_import_getblogs();

        // Expect return one blog and the number of post equal to 2.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->numposts, 2);
    }

    /**
     * Test the case user enrolled into the course, the course have 2 blog(visible) and posts.
     */
    public function test_oublog_import_getblogs_enroll_2blog_posts() {
        global $USER;

        // Enroll current user into course 1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog1 = $this->get_new_oublog($this->course1, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog1);
        $this->get_new_post($blog1);
        $this->get_new_post($blog1);

        $blog2 = $this->get_new_oublog($this->course1, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog2);
        $this->get_new_post($blog2);

        $result = oublog_import_getblogs();

        // Expect return one blog and the number of post equal to 2.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 2);
        $this->assertEquals($result[0]->numposts, 3);
        $this->assertEquals($result[1]->numposts, 2);
    }

    /**
     * Test the case user enrolled into the course, has two course with blog and post but user only enroll in course1.
     */
    public function test_oublog_import_getblogs_enroll_2blog_posts_differentcourse() {
        global $USER;

        // Enroll current user into course 1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);
        $blog1 = $this->get_new_oublog($this->course1, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog1);
        $this->get_new_post($blog1);
        $this->get_new_post($blog1);

        $blog2 = $this->get_new_oublog($this->course2, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog2);
        $this->get_new_post($blog2);

        $result = oublog_import_getblogs();

        // Expect return one blog and the number of post equal to 3.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->numposts, 3);
    }

    /**
     * Test the case user enrolled into the course, don't display the blog that has individual = OUBLOG_NO_INDIVIDUAL_BLOGS.
     */
    public function test_oublog_import_getblogs_enroll_3blog_differentindividual() {
        global $USER;

        // Enroll current user into course 1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $blog1 = $this->get_new_oublog($this->course1,
            array('name' => 'Blog1', 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog1);
        $blog2 = $this->get_new_oublog($this->course1,
            array('name' => 'Blog2', 'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog2);
        $this->get_new_post($blog2);
        $blog3 = $this->get_new_oublog($this->course1,
            array('name' => 'Blog3', 'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog3);
        $this->get_new_post($blog3);
        $this->get_new_post($blog3);

        $result = oublog_import_getblogs();

        // Expect return blog2 and blog 3. because of the individual.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 2);
        $this->assertEquals($result[0]->name, $blog2->name);
        $this->assertEquals($result[0]->numposts, 2);
        $this->assertEquals($result[1]->name, $blog3->name);
        $this->assertEquals($result[1]->numposts, 3);
    }

    /**
     * Test the case user enrolled into the course, exclude the current blog.
     */
    public function test_oublog_import_getblogs_enroll_3blog_exclude_current_post() {
        global $USER;

        // Enroll current user into course 1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $blog1 = $this->get_new_oublog($this->course1,
            array('name' => 'Blog1', 'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog1);
        $blog2 = $this->get_new_oublog($this->course1,
            array('name' => 'Blog2', 'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog2);
        $this->get_new_post($blog2);
        $blog3 = $this->get_new_oublog($this->course1,
            array('name' => 'Blog3', 'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog3);
        $this->get_new_post($blog3);
        $this->get_new_post($blog3);

        $result = oublog_import_getblogs(0, $blog2->cm->id);

        // Expect return only blog2 and blog 3. because of the individual.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 2);
        $this->assertEquals($result[0]->name, $blog1->name);
        $this->assertEquals($result[0]->numposts, 1);
        $this->assertEquals($result[1]->name, $blog3->name);
        $this->assertEquals($result[1]->numposts, 3);
    }

    /**
     * Test the case user is guest.
     */
    public function test_oublog_import_getblogs_guest_account() {
        global $USER;
        $this->setGuestUser();

        $blog = $this->get_new_oublog($this->course1, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog);
        $this->get_new_post($blog);

        $result = oublog_import_getblogs();

        // Expect return nothing because the account is guest.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     * Test the case user is admin but not enroll into course.
     */
    public function test_oublog_import_getblogs_admin_unenrolled() {
        $this->setAdminUser();

        $blog = $this->get_new_oublog($this->course1, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog);
        $this->get_new_post($blog);

        $result = oublog_import_getblogs();

        // Expect return nothing because the account is admin but not enroll into course.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 0);
    }

    /**
     * Test the case user is admin and enroll into course.
     */
    public function test_oublog_import_getblogs_admin_enrolled() {
        global $USER;
        $this->setAdminUser();

        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        $blog = $this->get_new_oublog($this->course1, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_post($blog);
        $this->get_new_post($blog);

        $result = oublog_import_getblogs();

        // Expect return nothing 1 blog and 2 posts.
        $this->assertNotNull($result);
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]->name, $blog->name);
        $this->assertEquals($result[0]->numposts, 2);
    }
}