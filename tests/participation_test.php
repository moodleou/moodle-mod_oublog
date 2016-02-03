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
 * Tests user participation functions.
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

class oublog_participation_test extends oublog_test_lib {
    // Test access to My Participation buttons.
    public function test_myparticipation_access() {
        global $SITE, $USER;
        $this->resetAfterTest(true);

        // Whole course.
        $course = $this->get_new_course();

        $student = $this->get_new_user('student', $course->id);
        $student2 = $this->get_new_user('student', $course->id);
        $grp1 = $this->get_new_group($course->id);
        $grp2 = $this->get_new_group($course->id);
        $this->get_new_group_member($grp1->id, $student->id);
        $this->get_new_group_member($grp2->id, $student2->id);

        $oublog = $this->get_new_oublog($course->id);
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->setUser($student);

        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));

        $this->setGuestUser();
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));

        // Separate groups.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => SEPARATEGROUPS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));

        $this->setUser($student);
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));

        $this->setUser($student2);
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));

        // Visible groups (user seems to get my participation on all groups).
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => VISIBLEGROUPS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->setGuestUser();
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));

        $this->setUser($student);
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));

        $this->setUser($student2);
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));

        // Separate groups, separate individuals.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => SEPARATEGROUPS,
                'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->setGuestUser();
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->setUser($student);
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));
        $this->setUser($student2);
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));
        // Separate groups, visible individuals.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => SEPARATEGROUPS,
                'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->setGuestUser();
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));
        $this->setUser($student);
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));
        $this->setUser($student2);
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));
        // Visble groups, visible individuals.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => VISIBLEGROUPS,
                'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->setGuestUser();
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));
        $this->setUser($student);
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));
        $this->setUser($student2);
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp2->id));

        // Visble individuals.
        $oublog = $this->get_new_oublog($course->id, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->setGuestUser();
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->setUser($student);
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->setUser($student2);
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        // Separate individuals.
        $oublog = $this->get_new_oublog($course->id, array('individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->setGuestUser();
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->setUser($student);
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->setUser($student2);
        $this->assertEquals(OUBLOG_MY_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));

        // Global blog (oublog_can_view_participation() always returns OUBLOG_NO_PARTICIPATION).
        $oublog = $this->get_new_oublog($SITE->id, array('global' => 1));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->setGuestUser();
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->setUser($student);
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
    }

    // Test access to User Participation buttons.
    public function test_userparticipation_access() {
        global $SITE, $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        // Whole course.
        $course = $this->get_new_course();

        $grp1 = $this->get_new_group($course->id);

        $oublog = $this->get_new_oublog($course->id);
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);

        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));

        // Separate groups.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => SEPARATEGROUPS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));

        // Visible groups.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => VISIBLEGROUPS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));

        // Separate groups, separate individuals.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => SEPARATEGROUPS,
                'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));

        // Separate groups, visible individuals.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => SEPARATEGROUPS,
                'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));

        // Visble groups, visible individuals.
        $oublog = $this->get_new_oublog($course->id, array('groupmode' => VISIBLEGROUPS,
                'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm, $grp1->id));

        // Visble individuals.
        $oublog = $this->get_new_oublog($course->id, array('individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));

        // Separate individuals.
        $oublog = $this->get_new_oublog($course->id, array('individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->assertEquals(OUBLOG_USER_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));

        // Global blog (oublog_can_view_participation() always returns OUBLOG_NO_PARTICIPATION).
        $oublog = $this->get_new_oublog($SITE->id, array('global' => 1));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $this->assertEquals(OUBLOG_NO_PARTICIPATION, oublog_can_view_participation($course, $oublog, $cm));
    }

    public function test_participation() {
        global $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        // Whole course.
        $course = $this->get_new_course();
        $student1 = $this->get_new_user('student', $course->id);
        $student2 = $this->get_new_user('student', $course->id);

        $oublog = $this->get_new_oublog($course->id);
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $context = context_module::instance($oublog->cmid);

        // Check empty participation array/object.
        $participation = oublog_get_participation($oublog, $context, 0, $cm, $course);
        $this->assertTrue(is_array($participation));
        $this->assertEquals(2, count($participation));
        $this->assertArrayHasKey($student1->id, $participation);
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, 0, $cm, $course);
        $this->assertInstanceOf('stdClass', $userparticipation);
        $this->assertEmpty($userparticipation->posts);
        $this->assertEmpty($userparticipation->comments);
        $this->assertEquals(fullname($student1), fullname($userparticipation->user));

        // 2 posts by user 1, 2 comments by user 1, 1 comment by user 2.
        $poststub = $this->get_post_stub($oublog->id);
        $poststub->userid = $student1->id;
        $post1 = $this->get_new_post($oublog, $poststub);
        $post2 = $this->get_new_post($oublog, $poststub);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        $generator->create_content($oublog, array('comment' => (object) array('postid' => $post1, 'userid' => $student1->id)));
        $generator->create_content($oublog, array('comment' => (object) array('postid' => $post2, 'userid' => $student1->id)));
        $comment = $generator->create_content($oublog,
                array('comment' => (object) array('postid' => $post2, 'userid' => $student2->id)));

        $participation = oublog_get_participation($oublog, $context, 0, $cm, $course);
        $this->assertEquals(2, $participation[$student1->id]->posts);
        $this->assertTrue(empty($participation[$student2->id]->posts));
        $this->assertEquals(2, $participation[$student1->id]->comments);
        $this->assertEquals(1, $participation[$student2->id]->comments);
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, 0, $cm, $course);
        $this->assertCount(2, $userparticipation->posts);
        $this->assertCount(2, $userparticipation->comments);
        $userparticipation = oublog_get_user_participation($oublog, $context, $student2->id, 0, $cm, $course);
        $this->assertCount(0, $userparticipation->posts);
        $this->assertCount(1, $userparticipation->comments);
        // Test time filtering.
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, 0, $cm, $course, time());
        $this->assertCount(0, $userparticipation->posts);
        $this->assertCount(0, $userparticipation->comments);
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, 0, $cm, $course,
                (time() - 3600), (time() + 3600));
        $this->assertCount(2, $userparticipation->posts);
        $this->assertCount(2, $userparticipation->comments);
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, 0, $cm, $course,
                null, (time() + 3600));
        $this->assertCount(2, $userparticipation->posts);
        $this->assertCount(2, $userparticipation->comments);
        // Test oublog_get_user_participation() filtering.
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, 0, $cm, $course,
                null, null, true, false, null, 1);
        $this->assertCount(1, $userparticipation->posts);
        $this->assertCount(0, $userparticipation->comments);
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, 0, $cm, $course,
                null, null, false, true);
        $this->assertCount(0, $userparticipation->posts);
        $this->assertCount(2, $userparticipation->comments);

        // Test deleted posts/comments don't show.
        $DB->update_record('oublog_posts', (object) array('id' => $post1, 'timedeleted' => time(), 'deletedby' => $USER->id));
        $DB->update_record('oublog_comments', (object) array('id' => $comment, 'timedeleted' => time(), 'deletedby' => $USER->id));
        $participation = oublog_get_participation($oublog, $context, 0, $cm, $course);
        $this->assertEquals(1, $participation[$student1->id]->posts);
        $this->assertEquals(1, $participation[$student1->id]->comments);
        $this->assertTrue(empty($participation[$student2->id]->comments));
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, 0, $cm, $course);
        $this->assertCount(1, $userparticipation->posts);
        $this->assertCount(1, $userparticipation->comments);
        $userparticipation = oublog_get_user_participation($oublog, $context, $student2->id, 0, $cm, $course);
        $this->assertCount(0, $userparticipation->comments);

        // Group blog.
        $grp1 = $this->get_new_group($course->id);
        $grp2 = $this->get_new_group($course->id);
        $this->get_new_group_member($grp1->id, $student1->id);
        $this->get_new_group_member($grp2->id, $student1->id);

        $oublog = $this->get_new_oublog($course->id, array('groupmode' => SEPARATEGROUPS));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $context = context_module::instance($oublog->cmid);

        // 2 posts by user 1 (1 in each group), 2 comments by user 1 (1 in each group).
        $poststub = $this->get_post_stub($oublog->id);
        $poststub->userid = $student1->id;
        $poststub->groupid = $grp1->id;
        $post1 = $this->get_new_post($oublog, $poststub);
        $poststub->groupid = $grp2->id;
        $post2 = $this->get_new_post($oublog, $poststub);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_oublog');
        $generator->create_content($oublog, array('comment' => (object) array('postid' => $post1, 'userid' => $student1->id)));
        $generator->create_content($oublog, array('comment' => (object) array('postid' => $post2, 'userid' => $student1->id)));
        $participation = oublog_get_participation($oublog, $context, 0, $cm, $course);
        $this->assertEquals(2, $participation[$student1->id]->posts);
        $this->assertTrue(empty($participation[$student2->id]->posts));
        $this->assertEquals(2, $participation[$student1->id]->comments);
        $this->assertTrue(empty($participation[$student2->id]->comments));
        $participation = oublog_get_participation($oublog, $context, $grp1->id, $cm, $course);
        $this->assertEquals(1, $participation[$student1->id]->posts);
        $this->assertEquals(1, $participation[$student1->id]->comments);
        $participation = oublog_get_participation($oublog, $context, $grp2->id, $cm, $course);
        $this->assertEquals(1, $participation[$student1->id]->posts);
        $this->assertEquals(1, $participation[$student1->id]->comments);
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, 0, $cm, $course);
        $this->assertCount(2, $userparticipation->posts);
        $this->assertCount(2, $userparticipation->comments);
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, $grp1->id, $cm, $course);
        $this->assertCount(1, $userparticipation->posts);
        $this->assertCount(1, $userparticipation->comments);
        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, $grp2->id, $cm, $course);
        $this->assertCount(1, $userparticipation->posts);
        $this->assertCount(1, $userparticipation->comments);
    }

    public function test_participation_grades() {
        global $USER, $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        // Whole course.
        $course = $this->get_new_course();
        $student1 = $this->get_new_user('student', $course->id);
        $student2 = $this->get_new_user('student', $course->id);

        $oublog = $this->get_new_oublog($course->id, array('grading' => 1, 'grade' => 100));
        $cm = get_coursemodule_from_id('oublog', $oublog->cmid);
        $context = context_module::instance($oublog->cmid);

        $participation = oublog_get_participation($oublog, $context, 0, $cm, $course);
        $this->assertArrayHasKey($student1->id, $participation);
        $this->assertTrue(isset($participation[$student1->id]->gradeobj));
        $this->assertNotEmpty($participation[$student1->id]->gradeobj);
        $this->assertEmpty($participation[$student1->id]->gradeobj->grade);
        oublog_update_manual_grades(array($student1->id => 55), $participation, $cm, $oublog, $course);
        $participation = oublog_get_participation($oublog, $context, 0, $cm, $course);
        $this->assertTrue(isset($participation[$student1->id]->gradeobj));
        $this->assertNotEmpty($participation[$student1->id]->gradeobj);
        $this->assertEquals(55, $participation[$student1->id]->gradeobj->grade);

        $userparticipation = oublog_get_user_participation($oublog, $context, $student1->id, 0, $cm, $course,
                null, null, true, true, null, null, true);
        $this->assertTrue(isset($userparticipation->gradeobj));
        $this->assertNotEmpty($userparticipation->gradeobj);
        $this->assertEquals(55, $userparticipation->gradeobj->grade);
    }
}
