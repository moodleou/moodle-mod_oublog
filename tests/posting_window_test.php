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
 * Posting-window form validation tests for the oublog new-post path (#1013450).
 *
 * Reference C adds a friendly submit-time validation message on the new-post
 * path on top of the authoritative save-point exceptions (editpost.php before
 * oublog_add_post; import.php step 2). These tests cover the validation (UX)
 * layer by driving mod_oublog_post_form::validation() with the same customdata
 * editpost.php supplies, so a broken/missing customdata wire would be caught.
 *
 * @package    mod_oublog
 * @copyright  2026 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/oublog/tests/oublog_test_lib.php');
require_once($CFG->dirroot . '/mod/oublog/locallib.php');
require_once($CFG->dirroot . '/mod/oublog/post_form.php');

/**
 * Tests the posting-window form validation layer.
 */
class posting_window_test extends oublog_test_lib {
    /**
     * New post after postuntil (window has ended) gives the afterendpost message.
     */
    public function test_new_post_after_end() {
        $this->resetAfterTest();
        $course = $this->get_new_course();
        $student = $this->get_new_user('student', $course->id);
        $this->setUser($student);

        $blog = $this->get_new_oublog($course->id, ['postuntil' => time() - DAYSECS]);

        $form = $this->get_oublog_post_form($blog);
        $errors = $form->validation([], []);

        $when = userdate($blog->postuntil, get_string('strftimedatetimeshort', 'langconfig'));
        $expected = get_string('afterendpost', 'oublog', $when);
        $this->assertArrayHasKey('message', $errors);
        $this->assertEquals($expected, $errors['message']);
    }

    /**
     * New post before postfrom (window not started) gives the beforestartpost message.
     */
    public function test_new_post_before_start() {
        $this->resetAfterTest();
        $course = $this->get_new_course();
        $student = $this->get_new_user('student', $course->id);
        $this->setUser($student);

        $blog = $this->get_new_oublog($course->id, ['postfrom' => time() + DAYSECS]);

        $form = $this->get_oublog_post_form($blog);
        $errors = $form->validation([], []);

        $when = userdate($blog->postfrom, get_string('strftimedatetimeshort', 'langconfig'));
        $expected = get_string('beforestartpost', 'oublog', $when);
        $this->assertArrayHasKey('message', $errors);
        $this->assertEquals($expected, $errors['message']);
    }

    /**
     * New post within the posting window produces no window error.
     */
    public function test_new_post_within_window() {
        $this->resetAfterTest();
        $course = $this->get_new_course();
        $student = $this->get_new_user('student', $course->id);
        $this->setUser($student);

        // Window open: started in the past, ends in the future.
        $blog = $this->get_new_oublog(
            $course->id,
            ['postfrom' => time() - DAYSECS, 'postuntil' => time() + DAYSECS]
        );

        $form = $this->get_oublog_post_form($blog);
        $errors = $form->validation([], []);

        $this->assertArrayNotHasKey('message', $errors);
    }

    /**
     * New post out of window but with mod/oublog:ignorepostperiod granted: no error.
     */
    public function test_new_post_override_capability() {
        global $DB;
        $this->resetAfterTest();
        $course = $this->get_new_course();
        $student = $this->get_new_user('student', $course->id);

        $blog = $this->get_new_oublog($course->id, ['postuntil' => time() - DAYSECS]);
        $context = context_module::instance($blog->cm->id);

        // Grant the override capability to the student role in this module context.
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        assign_capability('mod/oublog:ignorepostperiod', CAP_ALLOW, $studentrole->id, $context->id);
        accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($student);
        $form = $this->get_oublog_post_form($blog, true);
        $errors = $form->validation([], []);

        $this->assertArrayNotHasKey('message', $errors);
    }

    /**
     * Builds a mod_oublog_post_form with editpost.php's customdata shape.
     *
     * @param object $oublog Blog (with ->cm) from get_new_oublog()
     * @return mod_oublog_post_form
     */
    protected function get_oublog_post_form($oublog) {
        $context = context_module::instance($oublog->cm->id);
        return new mod_oublog_post_form('editpost.php', [
            'individual' => 0,
            'maxvisibility' => OUBLOG_VISIBILITY_COURSEUSER,
            'allowcomments' => 1,
            'edit' => false,
            'personal' => false,
            'maxbytes' => 0,
            'maxattachments' => 0,
            'restricttags' => 0,
            'availtags' => [],
            'referurl' => '',
            'cmid' => $oublog->cm->id,
            'tagslist' => '',
            'oublog' => $oublog,
            'context' => $context,
        ]);
    }
}
