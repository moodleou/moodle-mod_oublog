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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/oublog/backup/moodle2/restore_oublog_stepslib.php'); // Because it exists (must)

/**
 * oublog restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_oublog_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // oublog only has one structure step
        $this->add_step(new restore_oublog_activity_structure_step('oublog_structure', 'oublog.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('oublog', array('intro'), 'oublog');
        $contents[] = new restore_decode_content('oublog_instances', array('summary'), 'oublog_instances');
        $contents[] = new restore_decode_content('oublog_posts', array('message'), 'oublog_posts');
        $contents[] = new restore_decode_content('oublog_comments', array('message'), 'oublog_comments');
        $contents[] = new restore_decode_content('oublog_edits', array('oldmessage'), 'oublog_edits');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('OUBLOGINDEX', '/mod/oublog/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('OUBLOGVIEW', '/mod/oublog/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('OUBLOGVIEWUSER', '/mod/oublog/view.php?user=$1', 'user');
        $rules[] = new restore_decode_rule('OUBLOGVIEWPOST', '/mod/oublog/viewpost.php?post=$1', 'oublog_post');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * oublog logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('oublog', 'add', 'view.php?id={course_module}', '{oublog}');
        $rules[] = new restore_log_rule('oublog', 'update', 'view.php?id={course_module}', '{oublog}');
        $rules[] = new restore_log_rule('oublog', 'view', 'view.php?id={course_module}', '{oublog}');
        $rules[] = new restore_log_rule('oublog', 'add entry', 'view.php?id={course_module}', '{oublog}');
        $rules[] = new restore_log_rule('oublog', 'update entry', 'view.php?id={course_module}', '{oublog}');
        $rules[] = new restore_log_rule('oublog', 'view responses', 'report.php?id={course_module}', '{oublog}');
        $rules[] = new restore_log_rule('oublog', 'update feedback', 'report.php?id={course_module}', '{oublog}');
        $rules[] = new restore_log_rule('oublog', 'view all', 'index.php?id={course}', '{oublog}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        // Fix old wrong uses (missing extension)
        $rules[] = new restore_log_rule('oublog', 'view all', 'index?id={course}', null,
                                        null, null, 'index.php?id={course}');
        $rules[] = new restore_log_rule('oublog', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
