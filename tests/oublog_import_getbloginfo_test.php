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

class oublog_import_getbloginfo_test extends oublog_test_lib
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
     * Pass the not existing blog.
     */
    public function test_oublog_import_getbloginfo_noblog() {
        // Expect exception because the blog and course not existed.
        $this->expectException('dml_missing_record_exception');

        oublog_import_getbloginfo(-1);
    }

    /**
     * Have course and blog, but user haven't enrolled.
     */
    public function test_oublog_import_getbloginfo_have_course_blog_unenrolled() {
        $blog = $this->get_new_oublog($this->course1, array('global' => 0, 'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));

        // Expect exception because the user haven't enroll into this course yet.
        $this->expectException('moodle_exception');

        oublog_import_getbloginfo($blog->cm->id);
    }

    /**
     * Have course and blog, but user haven't enrolled to this course (but another course).
     */
    public function test_oublog_import_getbloginfo_have_2course_blog_enrolldifferent() {
        global $USER;

        // Enroll this user into course2.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course2->id);

        // Create 1 blog for course1 and course2.
        $blog = $this->get_new_oublog($this->course1, array('global' => 0, 'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $this->get_new_oublog($this->course2, array('global' => 0, 'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));

        // Expect exception because the user haven't enroll into correct course.
        $this->expectException('moodle_exception');

        oublog_import_getbloginfo($blog->cm->id);
    }

    /**
     * Have course and blog (OUBLOG_NO_INDIVIDUAL_BLOGS), global = false, and user enrolled.
     */
    public function test_oublog_import_getbloginfo_have_2course_blog_no_individual() {
        global $USER;

        // Enroll this user into course1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Create 1 blog for course1.
        $blog = $this->get_new_oublog($this->course1, array('global' => 0, 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));

        // Expect exception because the individual is set to OUBLOG_NO_INDIVIDUAL_BLOGS.
        $this->expectException('moodle_exception');

        oublog_import_getbloginfo($blog->cm->id);
    }

    /**
     * Have course and blog (OUBLOG_SEPARATE_INDIVIDUAL_BLOGS), global = false, and user enrolled.
     */
    public function test_oublog_import_getbloginfo_have_2course_blog_separate_individual() {
        global $USER;

        // Enroll this user into course1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Create 1 blog for course1.
        $blog = $this->get_new_oublog($this->course1, array('global' => 0, 'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $result = oublog_import_getbloginfo($blog->cm->id);

        // Expect return blog info.
        $this->assertNotNull($result);
        $this->assertEquals($result[0], $blog->cm->id);
        $this->assertEquals($result[1], $blog->id);
        $this->assertEquals($result[3], $this->course1->shortname . ' ' . $this->course1->fullname . ' : ' . $blog->name);
        $this->assertEquals($result[4], $this->course1->shortname);
    }

    /**
     * Have course and blog (OUBLOG_VISIBLE_INDIVIDUAL_BLOGS), global = false, and user enrolled.
     */
    public function test_oublog_import_getbloginfo_have_2course_blog_visible_individual() {
        global $USER;

        // Enroll this user into course1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Create 1 blog for course1.
        $blog = $this->get_new_oublog($this->course1, array('global' => 0, 'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $result = oublog_import_getbloginfo($blog->cm->id);

        // Expect return blog info.
        $this->assertNotNull($result);
        $this->assertEquals($result[0], $blog->cm->id);
        $this->assertEquals($result[1], $blog->id);
        $this->assertEquals($result[3], $this->course1->shortname . ' ' . $this->course1->fullname . ' : ' . $blog->name);
        $this->assertEquals($result[4], $this->course1->shortname);
    }

    /**
     * Have course and blog (OUBLOG_NO_INDIVIDUAL_BLOGS), global = true, and user enrolled.
     */
    public function test_oublog_import_getbloginfo_have_2course_blog_no_individual_global() {
        global $USER;

        // Enroll this user into course1.
        $this->getDataGenerator()->enrol_user($USER->id, $this->course1->id);

        // Create 1 blog for course1.
        $blog = $this->get_new_oublog($this->course1, array('global' => 1, 'individual' => OUBLOG_NO_INDIVIDUAL_BLOGS));
        $result = oublog_import_getbloginfo($blog->cm->id);

        // Expect return blog info with course shortname is empty.
        $this->assertNotNull($result);
        $this->assertEquals($result[0], $blog->cm->id);
        $this->assertEquals($result[1], $blog->id);
        $this->assertEquals($result[3], '');
        $this->assertEquals($result[4], '');
    }
}
