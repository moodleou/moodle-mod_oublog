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
 * This is a lib/helper class for oublog tests, containing useful setup functions
 * Include + Extend this class in your test rather than advance_testcase
 *
 * @package    mod_oublog
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

abstract class oublog_test_lib extends advanced_testcase {
    /*
     These functions require us to create database entries and/or grab objects to make it possible to test the
    many permuations required for OU Blogs.

    */

    /**
     * Creates a new user and enrols them on course with role specified (optional)
     * @param string $rolename role shortname if enrolment required
     * @param int $courseid course id to enrol on
     * @return stdClass user
     */
    public function get_new_user($rolename = null, $courseid = null) {
        global $DB;
        $user = $this->getDataGenerator()->create_user();

        // Assign role if required.
        if ($rolename && $courseid) {
            $role = $DB->get_record('role', array('shortname' => $rolename));
            $this->getDataGenerator()->enrol_user($user->id, $courseid, $role->id);
        }

        return $user;
    }

    public function get_new_course() {
        $course = new stdClass();
        $course->fullname = 'Anonymous test course';
        $course->shortname = 'ANON';
        return $this->getDataGenerator()->create_course($course);
    }

    public function get_new_group($courseid) {
        $group = new stdClass();
        $group->courseid = $courseid;
        $group->name = 'test group';
        return $this->getDataGenerator()->create_group($group);
    }

    public function get_new_group_member($groupid, $userid) {
        $member = new stdClass();
        $member->groupid = $groupid;
        $member->userid = $userid;
        return $this->getDataGenerator()->create_group_member($member);
    }

    /**
     * Create new oublog instance using generator, returns instance record + cm
     * @param int $courseid
     * @param array $options
     */
    public function get_new_oublog($courseid, $options = null) {
        if (is_null($options)) {
            $options = array();
        } else {
            $options = (array) $options;
        }
        $options['course'] = $courseid;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        $oublog = $generator->create_instance((object) $options);
        $this->assertNotEmpty($oublog);
        $cm = get_coursemodule_from_instance('oublog', $oublog->id);
        $this->assertNotEmpty($cm);
        $oublog->cm = $cm;
        return $oublog;
    }

    /**
     * Create a new grouping and add it into a course
     *
     * @param int $courseid
     * @return \stdClass
     */
    public function get_new_grouping($courseid) {
        $grouping = new stdClass();
        $grouping->courseid = $courseid;
        return $this->getDataGenerator()->create_grouping($grouping);
    }

    /**
     * Add a group to a grouping
     *
     * @param $groupingid
     * @param $groupid
     * @return bool
     */
    public function get_new_grouping_group($groupingid, $groupid) {
        $groupmember = new stdClass();
        $groupmember->groupid = $groupid;
        $groupmember->groupingid = $groupingid;
        return $this->getDataGenerator()->create_grouping_group($groupmember);
    }

    /**
     * Wrapper for generator create_content() to add a post
     * Supports creating post attachments ($post->attachments)
     * @param object $oublog
     * @param object $post
     */
    public function get_new_post($oublog, $post = null) {
        if (empty($post)) {
            $post = $this->get_post_stub($oublog->id);
        }
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        if (!empty($post->attachments)) {
            $attachments = $post->attachments;
            unset($post->attachments);
        }
        $postid = $generator->create_content($oublog, array('post' => (object) clone $post));
        if (!empty($attachments)) {
            // Adds attachments - send key = file name, value = contents e.g. 'a.txt' => 'test'.
            $fs = get_file_storage();
            $context = context_module::instance($oublog->cmid);
            $filerec = array(
                    'filepath' => '/',
                    'contextid' => $context->id,
                    'component' => 'mod_oublog',
                    'filearea' => 'attachments',
                    'itemid' => $postid
                    );
            foreach ($attachments as $filename => $content) {
                $filerec['filename'] = $filename;
                $fs->create_file_from_string($filerec, $content);
            }
        }
        return $postid;
    }

    /**
     * Returns a basic post record as if from a form.
     * @param int $oublogid
     * @return stdClass
     */
    public function get_post_stub($oublogid) {
        global $USER;
        $post = new stdClass();
        $post->oublogid = $oublogid;
        $post->userid = $USER->id;
        $post->groupid = 0;
        $post->title = 'testpost';
        $post->message = array();
        $post->message['itemid'] = 1;
        $post->message['text'] = '<p>newpost</p>';
        $post->allowcomments = 1;
        $post->visibility = 100;
        $post->attachments = '';
        return $post;
    }

    /**
     * Returns a basic comment record as if from a form.
     * @param int $postid
     * @param int $userid
     * @return stdClass
     */
    public function get_comment_stub($postid, $userid) {
        $comment = new stdClass();
        $comment->title = 'Test Comment';
        $comment->messagecomment = array();
        $comment->messagecomment['text'] = 'Message for test comment';
        $comment->postid = $postid;
        $comment->userid = $userid;
        return $comment;
    }
}
