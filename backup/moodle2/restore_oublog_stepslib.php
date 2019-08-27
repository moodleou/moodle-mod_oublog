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

/**
 * Define all the restore steps that will be used by the restore_oublog_activity_task
 */

/**
 * Structure step to restore one oublog activity
 */
class restore_oublog_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('oublog', '/activity/oublog');

        if ($userinfo) {
            $paths[] = new restore_path_element('oublog_instance', '/activity/oublog/instances/instance');
            $paths[] = new restore_path_element('oublog_link', '/activity/oublog/links/link');
            $paths[] = new restore_path_element('oublog_post', '/activity/oublog/instances/instance/posts/post');
            $paths[] = new restore_path_element('oublog_rating', '/activity/oublog/instances/instance/posts/post/ratings/rating');
            $paths[] = new restore_path_element('oublog_comment', '/activity/oublog/instances/instance/posts/post/comments/comment');
            $paths[] = new restore_path_element('oublog_edit', '/activity/oublog/instances/instance/posts/post/edits/edit');
            $paths[] = new restore_path_element('oublog_tag', '/activity/oublog/instances/instance/posts/post/tags/tag');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_oublog($data) {
        global $DB;

        $data = (object)$data;
        if (!isset($data->timemodified)) {
            $data->timemodified = time();
        }
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        if (!isset($data->intro) && isset($data->summary)) {
            $data->intro = $data->summary;
            $data->introformat = FORMAT_HTML;
        }

        // if it's the global blog and we already have one then assume we can't restore this module since it already exits
        if (!empty($data->global) && $DB->record_exists('oublog', array('global'=> 1))) {
            $this->set_mapping('oublog', $oldid, $oldid, true);
            return(true);
        }

        $userinfo = $this->get_setting_value('userinfo');
        if (!$userinfo) {
            $data->views = 0;
        }

        // insert the oublog record
        $newitemid = $DB->insert_record('oublog', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_oublog_instance($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->oublogid = $this->get_new_parentid('oublog');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('oublog_instances', $data);
        $this->set_mapping('oublog_instance', $oldid, $newitemid, true);
    }

    protected function process_oublog_link($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->oublogid = $this->get_new_parentid('oublog');
        $data->oubloginstancesid =  $this->get_mappingid('oublog_instance', $data->oubloginstancesid);

        $newitemid = $DB->insert_record('oublog_links', $data);
        $this->set_mapping('oublog_link', $oldid, $newitemid);
    }

    protected function process_oublog_post($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->oubloginstancesid = $this->get_new_parentid('oublog_instance');
        $data->groupid = $this->get_mappingid('group', $data->groupid);

        // Following comment copied from old 1.9 restore code.
        // Currently OUBlog has no "start time" or "deadline" fields
        // that make sense to offset at restore time. Edit and delete times
        // must remain stable even through restores with startdateoffsets.

        $newitemid = $DB->insert_record('oublog_posts', $data);
        $this->set_mapping('oublog_post', $oldid, $newitemid, true);
    }

    protected function process_oublog_comment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->postid = $this->get_new_parentid('oublog_post');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('oublog_comments', $data);
        $this->set_mapping('oublog_comment', $oldid, $newitemid, true);
    }

    protected function process_oublog_edit($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->postid = $this->get_new_parentid('oublog_post');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('oublog_edits', $data);
        $this->set_mapping('oublog_edit', $oldid, $newitemid, true);
    }

    protected function process_oublog_tag($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // First check to see if tag exists:
        $existingtag = $DB->get_record('oublog_tags', array('tag'=>$data->tag));
        if (empty($existingtag->id)) {
            $tag = new stdclass();
            $tag->tag = $data->tag;
            $tagid = $DB->insert_record('oublog_tags', $tag);
        } else {
            $tagid = $existingtag->id;
        }
        // Now insert taginstance record.
        $taginstance = new stdclass();
        $taginstance->oubloginstancesid = $this->get_new_parentid('oublog_instance');
        $taginstance->postid = $this->get_new_parentid('oublog_post');
        $taginstance->tagid = $tagid;
        $newitemid = $DB->insert_record('oublog_taginstances', $taginstance);

        $this->set_mapping('oublog_tag', $oldid, $newitemid);
    }

    protected function process_oublog_rating($data) {
        global $DB;

        $data = (object)$data;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created).
        $data->contextid = $this->task->get_contextid();
        $data->itemid = $this->get_new_parentid('oublog_post');
        if ($data->scaleid < 0) { // Scale found, get mapping.
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->rating = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Make sure that we have both component and ratingarea set.
        if (empty($data->component)) {
            $data->component = 'mod_oublog';
        }
        if (empty($data->ratingarea)) {
            $data->ratingarea = 'post';
        }

        $newitemid = $DB->insert_record('rating', $data);
    }

    protected function after_execute() {

        // Add oublog related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_oublog', 'intro', null);
        $this->add_related_files('mod_oublog', 'summary', 'oublog_instance');
        // Add post related files
        $this->add_related_files('mod_oublog', 'attachment', 'oublog_post');
        $this->add_related_files('mod_oublog', 'message', 'oublog_post');
        $this->add_related_files('mod_oublog', 'messagecomment', 'oublog_comment');
        $this->add_related_files('mod_oublog', 'edit', 'oublog_edit');
    }
}
