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
 * Unit tests testing oublog generator.
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

class oublog_generator_test extends oublog_test_lib {

    public function test_create_instance() {
        $this->resetAfterTest(true);
        // Test calling direct.
        $options = new stdClass();
        $options->course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        $oublog = $generator->create_instance($options);
        $this->assertNotEmpty($oublog);
        $cm = get_coursemodule_from_instance('oublog', $oublog->id);
        $this->assertNotEmpty($cm);
        $this->assertEquals($oublog->id, $cm->instance);
        $this->assertEquals($oublog->cmid, $cm->id);

        // Test calling using oublog_test_lib.
        $courseid = $this->get_new_course();
        $blog = $this->get_new_oublog($courseid);
        $this->assertNotEmpty($blog);
        $this->assertNotEmpty($blog->cm);
        $this->assertEquals($blog->id, $blog->cm->instance);

        // Try setting some options and ensuring they get saved correctly.
        $options->maxvisibility = OUBLOG_VISIBILITY_PUBLIC;
        $options->allowcomments = 1;
        $options->grade = 100;
        $options->individual = OUBLOG_VISIBLE_INDIVIDUAL_BLOGS;
        $options->displayname = 'LOG';
        $options->statblockon = 1;
        $options->allowimport = 1;
        $options->groupmode = VISIBLEGROUPS;
        $oublog = $generator->create_instance($options);
        $this->assertNotEmpty($oublog);
        $this->assertEquals($options->individual, $oublog->individual);
        $this->assertEquals($options->displayname, $oublog->displayname);
        $this->assertEquals($options->statblockon, $oublog->statblockon);
        $this->assertEquals($options->allowimport, $oublog->allowimport);
        $this->assertEquals($options->grade, $oublog->grade);
        $cm = get_coursemodule_from_instance('oublog', $oublog->id);
        $this->assertNotEmpty($cm);
        $this->assertEquals($oublog->id, $cm->instance);
        $this->assertEquals($options->groupmode, $cm->groupmode);
    }

    public function test_create_content() {
        global $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        $options = new stdClass();
        $options->course = $this->getDataGenerator()->create_course();
        $oublog = $generator->create_instance($options);
        $postid = $generator->create_content($oublog);

        $post = oublog_get_post($postid);
        $this->assertInstanceOf('stdClass', $post);
        $this->assertEquals($USER->id, $post->userid);
        // Try making post with basic options.
        $postid = $generator->create_content($oublog, array('post' => (object) array('message' => 'testing')));

        $post = $DB->get_record('oublog_posts', array('id' => $postid));
        $this->assertInstanceOf('stdClass', $post);
        $this->assertEquals('testing', $post->message);

        // Test calling using oublog_test_lib inc attachments.
        $postid = $this->get_new_post($oublog);
        $post = $DB->get_record('oublog_posts', array('id' => $postid));
        $this->assertInstanceOf('stdClass', $post);

        $postid = $this->get_new_post($oublog, (object) array('attachments' => array('tst.txt' => 'abcd')));
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($DB->get_field('files', 'id', array('component' => 'mod_oublog', 'filename' => 'tst.txt')));
        $this->assertInstanceOf('stored_file', $file);
        $this->assertEquals('tst.txt', $file->get_filename());

        // Add an invalid comment and test exception.
        try {
            $commentid = $generator->create_content($oublog, array('comment' => (object) array()));
            $this->fail('Exception expected as no post id sent when adding comment');
        } catch (coding_exception $e) {
            $this->assertEquals('Must pass postid when creating comment', $e->a);
        }
        // Add comment.
        $options = (object) array('postid' => $postid, 'message' => 'test');
        $commentid = $generator->create_content($oublog, array('comment' => $options));
        $comment = $DB->get_record('oublog_comments', array('id' => $commentid));
        $this->assertInstanceOf('stdClass', $comment);
        $this->assertEquals('test', $comment->message);
    }

}
