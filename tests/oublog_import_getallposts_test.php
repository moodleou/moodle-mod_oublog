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

class oublog_import_getallposts_test extends oublog_test_lib {

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
     * Test the case get all post in blog.
     */
    public function test_oublog_import_getallposts() {
        // Create blog and post for course1.
        $blog = $this->get_new_oublog($this->course1);
        $this->get_new_post($blog);
        $this->get_new_post($blog);
        $this->get_new_post($blog);

        $result = oublog_import_getallposts($blog->id, 'timeposted ASC');

        $this->assertNotNull($result);

        // Expect 3 data return.
        $this->assertEquals(count($result[0]), 3);

        // Expect 3 data return.
        $this->assertEquals($result[1], 3);
    }

    /**
     * Test the case get all post in 1 blog but create 2 blog.
     */
    public function test_oublog_import_getallposts_in1blog() {
        // Create blog and post for course1.
        $blog1 = $this->get_new_oublog($this->course1);
        $this->get_new_post($blog1);
        $this->get_new_post($blog1);
        $this->get_new_post($blog1);

        $blog2 = $this->get_new_oublog($this->course1);
        $this->get_new_post($blog2);
        $this->get_new_post($blog2);
        $this->get_new_post($blog2);
        $this->get_new_post($blog2);

        $result = oublog_import_getallposts($blog1->id, 'timeposted ASC');

        $this->assertNotNull($result);

        // Expect 3 data return.
        $this->assertEquals(count($result[0]), 3);

        // Expect 3 total data return.
        $this->assertEquals($result[1], 3);
    }

    /**
     * Test the case get all post in blog with the number of post not enough for limit from.
     */
    public function test_oublog_import_getallposts_not_enough_post() {

        global $USER;
        // Create blog and post for course1.
        $blog = $this->get_new_oublog($this->course2);

        // Create 100 post in blog.
        for ($x = 0; $x < 100; $x++) {
             $this->get_new_post($blog);
        }

        // Set limit from 100 (limit from = page * perpage).
        $page = 1;
        $result = oublog_import_getallposts($blog->id, 'timeposted DESC', $USER->id, $page);

        $this->assertNotNull($result);

        // Expect 0 data return.
        $this->assertEquals(count($result[0]), 0);

        // Expect 0 total data return.
        $this->assertEquals($result[1], 0);
    }

    /**
     * Test the case get all post in blog with the number of post enough for limit from.
     */
    public function test_oublog_import_getallposts_enough_post() {

        global $USER;
        // Create blog and post for course1.
        $blog = $this->get_new_oublog($this->course1);

        // Create 150 post in blog.
        for ($x = 0; $x < 150; $x++) {
             $this->get_new_post($blog);
        }
        // Set limit from 100 (limit from = page * perpage).
        $page = 1;
        $result = oublog_import_getallposts($blog->id, 'timeposted DESC', $USER->id, 1);

        $this->assertNotNull($result);

        // Expect 50 data return.
        $this->assertEquals(count($result[0]), 50);

        // Expect 150 total data return.
        $this->assertEquals($result[1], 150);
    }

    /**
     * Test the case get all post in blog with 1 post is deleted.
     */
    public function test_oublog_import_getallposts_with1post_delete() {
        global $USER;
        // Create blog and post for course1.
        $blog = $this->get_new_oublog($this->course1);
        $post1 = $this->get_post_stub($blog->id);
        $post2 = $this->get_post_stub($blog->id);

        // Delete post 1.
        $post1->deletedby = $USER->id;

        // Set post 1 & 2 to blog.
        $post1id = $this->get_new_post($blog, $post1);
        $post2id = $this->get_new_post($blog, $post2);

        $result = oublog_import_getallposts($blog->id, 'timeposted DESC', $USER->id);
        $this->assertNotNull($result);

        // Expect 1 data return.
        $this->assertEquals(count($result[0]), 1);

        // Expect 1 total data return.
        $this->assertEquals($result[1], 1);
    }

}
