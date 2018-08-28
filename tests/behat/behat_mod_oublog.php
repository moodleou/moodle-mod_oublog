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
 * Steps definitions related with the oublog activity.
 *
 * @package mod_oublog
 * @category test
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * oublog-related steps definitions.
 *
 * @package    mod_oublog
 * @category   test
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_oublog extends behat_base {

    /**
     * Simulates a user adding a personal oublog username to a URL entered into their browser address bar.
     *
     * @Given /^I visit the personal blog for "(?P<user_string>(?:[^"]|\\")*)"$/
     * @param string $user the user name
     */
    public function i_visit_the_personal_blog_for($user) {
        global $CFG;
        $this->getSession()->visit($CFG->wwwroot .'/mod/oublog/view.php?u='. $user);
    }

    /**
     * Create sample posts.
     *
     * @Given /^I create "(?P<number>[^"]+)" sample posts for blog with id "(?P<idnumber_string>(?:[^"]|\\")*)"$/
     *
     * @param int number
     * @param string idnumber
     */
    public function i_create_n_posts_with_form_data($number, $idnumber) {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/mod/oublog/locallib.php');
        $oublog = $this->get_oublog_by_idnumber($idnumber);
        $cm = get_coursemodule_from_instance('oublog', $oublog->id);
        $course = get_course($oublog->course);
        for ($i = 0; $i < $number; $i++) {
            $post = new stdClass();
            $post->oublogid = $oublog->id;
            $post->userid = $USER->id;
            $post->groupid = 0;
            $post->title = 'Test post ' . $i;
            $post->message = array();
            $post->message['itemid'] = 1;
            $post->message['text'] = '<p>Test post ' . $i . ' content</p>';
            $post->allowcomments = 1;
            $post->visibility = 100;
            $post->attachments = '';
            oublog_add_post($post, $cm, $oublog, $course);
        }
    }

    private function get_oublog_by_idnumber($idnumber) {
        global $DB;
        $query = "SELECT blog.*, cm.id as cmid
                    FROM {oublog} blog
                    JOIN {course_modules} cm ON blog.id = cm.instance
                         AND blog.course = cm.course
                    JOIN {modules} m ON cm.module = m.id
                   WHERE m.name = 'oublog'
                         AND cm.idnumber = :idnumber";
        $oublog = $DB->get_record_sql($query, array('idnumber' => $idnumber));
        if (!$oublog) {
            throw new Exception('There is no oublog instance with idnumber ' . $idnumber);
        }
        return $oublog;
    }
}
