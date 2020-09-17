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
 * A scheduled task for oublog cron.
 *
 * @package    mod_oublog
 * @copyright  2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_oublog\task;

class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('oublogcrontask', 'mod_oublog');
    }

    /**
     * Run oublog cron.
     * Function to be run periodically according to the moodle cron.
     * This function runs every 4 hours.
     */
    public function execute() {
        global $DB;

        // Delete outdated (> 30 days) moderated comments.
        $outofdate = time() - 30 * 24 * 3600;
        $DB->delete_records_select('oublog_comments_moderated', "timeposted < ?", array($outofdate));

        // Delete outdated (> 90 days) personal blog posts that has been deleted.
        $fs = get_file_storage();
        $timeframe = strtotime('-90 days');
        if ($personalblog = $DB->get_record('oublog', ['global' => 1], '*', IGNORE_MULTIPLE)) {
            $cm = get_coursemodule_from_instance('oublog', $personalblog->id);
            $context = \context_module::instance($cm->id);
            $instancesql = "
                            SELECT op.id
                              FROM {oublog_instances} bi
                        INNER JOIN {oublog_posts} op ON bi.id = op.oubloginstancesid
                             WHERE bi.oublogid = :blogid AND op.timedeleted < :timeframe ";
            $posts = $DB->get_recordset_sql($instancesql, ['blogid' => $personalblog->id,
                    'timeframe' => $timeframe]);
            foreach ($posts as $post) {
                $transaction = $DB->start_delegated_transaction();
                // Delete files from this post.
                $params = ['postid' => $post->id];
                $fs->delete_area_files_select($context->id, 'mod_oublog', 'message',
                    'IN (:postid)', $params);
                $fs->delete_area_files_select($context->id, 'mod_oublog', 'attachment',
                    'IN (:postid)', $params);

                $commentsql = '
                                SELECT bc.id AS commentid
                                  FROM {oublog_comments} bc
                                 WHERE bc.postid IN (:postid)';
                $fs->delete_area_files_select($context->id, 'mod_oublog', 'messagecomment',
                    "IN ($commentsql)", $params);
                $DB->delete_records_select('oublog_comments', "id IN ($commentsql)", $params);
                $DB->delete_records_select('oublog_comments_moderated', 'postid IN (:postid)', $params);

                // Delete all edits (including files) on posts owned by these users
                $editsql = '
                            SELECT be.id AS editid
                              FROM {oublog_edits} be
                             WHERE be.postid IN (:postid)';
                $fs->delete_area_files_select($context->id, 'mod_oublog', 'edit',
                    "IN ($editsql)", $params);
                $DB->delete_records_select('oublog_edits', "id IN ($editsql)", $params);

                // Delete tag instances from all these posts.
                $DB->delete_records_select('oublog_taginstances', 'postid IN (:postid)', $params);

                // Delete the actual posts.
                $DB->delete_records_select('oublog_posts', 'id IN (:postid)', $params);
                $transaction->allow_commit();
            }
            $posts->close();
        }
    }

}
