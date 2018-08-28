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
 * Privacy subsystem implementation.
 *
 * @package mod_oublog
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_oublog\privacy;

use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\helper;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\transform;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy subsystem implementation.
 *
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin\provider interface.
    \core_privacy\local\request\plugin\provider,

    // This plugin has some sitewide user preferences to export.
    \core_privacy\local\request\user_preference_provider {

    /** @var int Number of characters of post to include in the path of its file folder */
    const TITLE_LENGTH_IN_PATH = 30;

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $items The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items) : collection {
        // The 'OU blog' table does not store any specific user data.

        // The 'oublog_instances' table stores the metadata about each OU blog occurence.
        $items->add_database_table(
                'oublog_instances', [
                    'userid' => 'privacy:metadata:oublog_instances:userid',
                    'name' => 'privacy:metadata:oublog_instances:name',
                    'summary' => 'privacy:metadata:oublog_instances:summary',
                ], 'privacy:metadata:oublog_instances');

        // The 'oublog_comments' table stores details of comments made and some additional information if moderated.
        $items->add_database_table(
                'oublog_comments', [
                    'title' => 'privacy:metadata:oublog_comments:title',
                    'userid' => 'privacy:metadata:oublog_comments:userid',
                    'message' => 'privacy:metadata:oublog_comments:message',
                    'authorname' => 'privacy:metadata:oublog_comments:authorname',
                    'authorip' => 'privacy:metadata:oublog_comments:authorip',
                ], 'privacy:metadata:oublog_comments');

        // The 'oublog_posts' table stores information about blog posts.
        $items->add_database_table(
                'oublog_posts', [
                    'message' => 'privacy:metadata:oublog_posts:message',
                    'title' => 'privacy:metadata:oublog_posts:title',
                    'lasteditedby' => 'privacy:metadata:oublog_posts:lasteditedby',
                    'deletedby' => 'privacy:metadata:oublog_posts:deletedby',
                ], 'privacy:metadata:oublog_posts');

        // The 'oublog_edits' table stores information about superceded posts.
        $items->add_database_table(
                'oublog_edits', [
                    'userid' => 'privacy:metadata:oublog_edits:userid',
                    'oldtitle' => 'privacy:metadata:oublog_edits:oldtitle',
                    'oldmessage' => 'privacy:metadata:oublog_edits:oldmessage',
                ], 'privacy:metadata:oublog_edits');

        // The 'oublog_tags' table stores data about any tags used by a blog post.
        $items->add_database_table(
                'oublog_tags', [
                    'tag' => 'privacy:metadata:oublog_tags:tag',
                ], 'privacy:metadata:oublog_tags');

        // The 'oublog_taginstances' table stores information about where and which actual tags have been used.
        $items->add_database_table(
                'oublog_taginstances', [
                    'oubloginstancesid' => 'privacy:metadata:oublog_taginstances:oubloginstancesid',
                    'postid' => 'privacy:metadata:oublog_taginstances:postid',
                    'tagid' => 'privacy:metadata:oublog_taginstances:tagid',
                ], 'privacy:metadata:oublog_taginstances');

        // The 'oublog_comments_moderated' table stores information of as yet unapproved post comments.
        $items->add_database_table(
                'oublog_comments_moderated', [
                        'postid' => 'privacy:metadata:oublog_comments_moderated:postid',
                        'title' => 'privacy:metadata:oublog_comments_moderated:title',
                        'message' => 'privacy:metadata:oublog_comments_moderated:message',
                        'authorname' => 'privacy:metadata:oublog_comments_moderated:authorname',
                        'authorip' => 'privacy:metadata:oublog_comments_moderated:authorip',
                ], 'privacy:metadata:oublog_comments_moderated');

        // Blog posts can be rated. They can also be tagged but we don't use the core tagging
        // system so there is no need to link that system.
        $items->add_subsystem_link('core_rating', [], 'privacy:metadata:core_rating');

        // Lots of user preferences.
        $items->add_user_preference('oublog_tagorder',
                'privacy:metadata:preference:oublog_tagorder');
        $items->add_user_preference('mod_oublog_postformfilter',
                'privacy:metadata:preference:mod_oublog_postformfilter');
        $items->add_user_preference('mod_oublog_visitformfilter',
                'privacy:metadata:preference:mod_oublog_visitformfilter');
        $items->add_user_preference('mod_oublog_commentformfilter',
                'privacy:metadata:preference:mod_oublog_commentformfilter');
        $items->add_user_preference('mod_oublog_commentpostformfilter',
                'privacy:metadata:preference:mod_oublog_commentpostformfilter');
        $items->add_user_preference('mod_oublog_hidestatsform_post',
                'privacy:metadata:preference:mod_oublog_hidestatsform_post');
        $items->add_user_preference('mod_oublog_hidestatsform_commentpost',
                'privacy:metadata:preference:mod_oublog_hidestatsform_commentpost');
        $items->add_user_preference('mod_oublog_hidestatsform_comment',
                'privacy:metadata:preference:mod_oublog_hidestatsform_comment');
        $items->add_user_preference('mod_oublog_hidestatsform_visit',
                'privacy:metadata:preference:mod_oublog_hidestatsform_visit');
        $items->add_user_preference('oublog_accordion_view_open',
                'privacy:metadata:preference:oublog_accordion_view_open');
        $items->add_user_preference('oublog_accordion_allposts_open',
                'privacy:metadata:preference:oublog_accordion_allposts_open');

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int $userid The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $ratingsql = \core_rating\privacy\provider::get_sql_join('rat', 'mod_oublog', 'post', 'bp.id', $userid);

        $sql = "SELECT DISTINCT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {oublog} b ON b.id = cm.instance
                  JOIN {oublog_instances} bi ON bi.oublogid = b.id
             LEFT JOIN {oublog_posts} bp ON bp.oubloginstancesid = bi.id
             LEFT JOIN {oublog_edits} be ON be.postid = bp.id AND be.userid = :edituserid
             LEFT JOIN {oublog_comments} bc ON bc.postid = bp.id AND bc.userid = :commentuserid
    {$ratingsql->join}
                 WHERE (
                       bi.userid = :instanceuserid OR bp.lasteditedby = :lastedituserid
                       OR bp.deletedby = :deleteuserid OR be.id IS NOT NULL
                       OR bc.id IS NOT NULL OR {$ratingsql->userwhere}
                       )";
        $params = [
            'modname' => 'oublog',
            'contextlevel' => CONTEXT_MODULE,
            'instanceuserid' => $userid,
            'lastedituserid' => $userid,
            'deleteuserid' => $userid,
            'edituserid' => $userid,
            'commentuserid' => $userid,
        ];
        $params += $ratingsql->params;

        $contextlist = new \core_privacy\local\request\contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $ratingsql = \core_rating\privacy\provider::get_sql_join(
                'rat', 'mod_oublog', 'post', 'bp.id', $user->id);

        foreach ($contextlist as $context) {
            // Fetch the generic module data.
            $contextdata = helper::get_context_data($context, $user);

            // Store instance data if there is any (this should only happen for personal blog).
            $sql = "
                    SELECT bi.name, bi.summary
                      FROM {course_modules} cm
                      JOIN {oublog} b ON b.id = cm.instance
                      JOIN {oublog_instances} bi ON bi.oublogid = b.id
                     WHERE cm.id = :contextinstanceid AND bi.userid = :instanceuserid";
            $instanceparams = [
                'contextinstanceid' => $context->instanceid,
                'instanceuserid' => $user->id
            ];
            $instance = $DB->get_record_sql($sql, $instanceparams, IGNORE_MISSING);
            if ($instance) {

                if (!is_null($instance->name)) {
                    $contextdata->blogname = $instance->name;
                }
                if (!is_null($instance->summary)) {
                    $contextdata->blogsummary = $instance->summary;
                }
            }

            // Write generic module intro files.
            helper::export_context_files($context, $user);

            // Join to get through to posts.
            $postjoins = "
                      FROM {course_modules} cm
                      JOIN {oublog} b ON b.id = cm.instance
                      JOIN {oublog_instances} bi ON bi.oublogid = b.id
                      JOIN {oublog_posts} bp ON bp.oubloginstancesid = bi.id
        {$ratingsql->join}
                           ";
            $postwhere = "
                     WHERE (bp.lasteditedby = :edituserid OR bp.deletedby = :deleteuserid OR
                           bi.userid = :instanceuserid OR {$ratingsql->userwhere})
                           AND cm.id = :contextinstanceid";
            $postparams = array_merge([
                        'contextinstanceid' => $context->instanceid,
                        'instanceuserid' => $user->id,
                        'edituserid' => $user->id,
                        'deleteuserid' => $user->id
                    ], $ratingsql->params);

            // Select all the posts you made or edited or deleted or rated.
            // This is all the data from oublog_posts table.
            $sql = "SELECT bp.id as postid,
                           bi.userid,
                           bp.groupid,
                           bp.title,
                           bp.message,
                           bp.timeposted,
                           bp.allowcomments,
                           bp.timeupdated,
                           bp.deletedby,
                           bp.timedeleted,
                           bp.visibility,
                           bp.lasteditedby
                $postjoins
                $postwhere
                  ORDER BY bp.timeposted";
            $rs = $DB->get_recordset_sql($sql, $postparams);
            $index = 0;
            $postindexes = [];
            foreach ($rs as $rec) {
                $path = self::get_posts_path($rec);

                // Replace any embedded file references in the message.
                $message = format_text(writer::with_context($context)->rewrite_pluginfile_urls($path, 'mod_oublog',
                        'message', $rec->postid, $rec->message), FORMAT_HTML);

                // Export embedded files if you edited the post or it's your post (but not if deleting).
                if ($rec->userid == $user->id || $rec->lasteditedby == $user->id) {
                    // Note: We end up exporting files that aren't really owned by the user (e.g.
                    // if they just edited somebody else's post) but because we can't always tell
                    // (in the case of the 'message' ones where it uses the same file area for both
                    // current and edited files) we just export everything.
                    writer::with_context($context)->export_area_files($path, 'mod_oublog', 'message', $rec->postid);
                    writer::with_context($context)->export_area_files($path, 'mod_oublog', 'attachment', $rec->postid);
                }

                // Export associated ratings.
                \core_rating\privacy\provider::export_area_ratings($user->id, $context, $path,
                        'mod_oublog', 'post', $rec->postid, $rec->userid != $user->id);

                // Basic post data (available for all posts). We will fill in tags later.
                $postdata = [
                    'postid' => $rec->postid,
                    'title' => $rec->title,
                    'message' => $message,
                    'groupid' => $rec->groupid,
                    'timeposted' => transform::datetime($rec->timeposted),
                    'allowcomments' => $rec->allowcomments,
                    'visibility' => $rec->visibility,
                    'tags' => ''
                ];

                // Other post data.
                $postdata['author'] = self::you_or_somebody_else($rec->userid, $user);

                if ($rec->lasteditedby) {
                    $postdata['lasteditedby'] = self::you_or_somebody_else($rec->lasteditedby, $user);
                    $postdata['timeupdated'] = transform::datetime($rec->timeupdated);
                }

                if ($rec->deletedby) {
                    $postdata['deletedby'] = self::you_or_somebody_else($rec->deletedby, $user);
                    $postdata['timedeleted'] = transform::datetime($rec->timedeleted);
                }

                if (empty($contextdata->posts)) {
                    $contextdata->posts = [];
                }
                $contextdata->posts[$index] = (object)$postdata;
                $postindexes[$rec->postid] = $index;
                $index++;
            }
            $rs->close();

            // Get all the tags for all those posts. Just to fill in the info from the oublog_tags,
            // oublog_taginstances table. (Note: There is a standard way to do Moodle core tags,
            // but blog uses its own tags.)
            $sql = "SELECT bp.id as postid,
                           t.tag
                $postjoins
                      JOIN {oublog_taginstances} ti ON ti.postid = bp.id
                      JOIN {oublog_tags} t ON t.id = ti.tagid
                $postwhere
                  ORDER BY bp.id, t.tag";
            $rs = $DB->get_recordset_sql($sql, $postparams);
            foreach ($rs as $rec) {
                $post = $contextdata->posts[$postindexes[$rec->postid]];
                if ($post->tags === '') {
                    $post->tags = $rec->tag;
                } else {
                    $post->tags .= ', ' . $rec->tag;
                }
            }
            $rs->close();

            // Select all the posts you edited (can be an original post or a future edit).
            // This is all the data from oublog_edits table.
            $sql = "SELECT be.id AS editid,
                           bp.id AS postid,
                           bp.title,
                           bp.message,
                           be.userid,
                           be.oldtitle,
                           be.oldmessage,
                           be.timeupdated
                      FROM {course_modules} cm
                      JOIN {oublog} b ON b.id = cm.instance
                      JOIN {oublog_instances} bi ON bi.oublogid = b.id
                      JOIN {oublog_posts} bp ON bp.oubloginstancesid = bi.id
                      JOIN {oublog_edits} be ON be.postid = bp.id
                     WHERE cm.id = :contextinstanceid AND be.userid = :edituserid
                  ORDER BY be.timeupdated";
            $params = [
                'contextinstanceid' => $context->instanceid,
                'edituserid' => $user->id
            ];
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $rec) {
                $path = array_merge(self::get_posts_path($rec),
                        [get_string('privacy_editnumber', 'mod_oublog', $rec->editid)]);

                // Replace any embedded file references in the message.
                $oldmessage = format_text(writer::with_context($context)->rewrite_pluginfile_urls($path, 'mod_oublog',
                        'message', $rec->postid, $rec->oldmessage), FORMAT_HTML);

                // Export attachments only not embedded files, because those are stored in the
                // same post file area as for the oublog_posts table which was already covered.
                writer::with_context($context)->export_area_files($path, 'mod_oublog', 'edit', $rec->editid);

                // Basic edit data (available for all posts).
                $editdata = [
                    'postid' => $rec->postid,
                    'editid' => $rec->editid,
                    'author' => self::you_or_somebody_else($rec->userid, $user),
                    'title' => $rec->oldtitle,
                    'message' => $oldmessage,
                    'timeupdated' => transform::datetime($rec->timeupdated)
                ];

                if (empty($contextdata->edits)) {
                    $contextdata->edits = [];
                }
                $contextdata->edits[] = (object)$editdata;
            }
            $rs->close();

            // Select all the comments you made or deleted.
            // This is all the data from oublog_comments table.
            $sql = "SELECT bc.id AS commentid,
                           bp.id AS postid,
                           bp.title,
                           bp.message,
                           bc.userid,
                           bc.title AS commenttitle,
                           bc.message AS commentmessage,
                           bc.timeposted,
                           bc.timedeleted,
                           bc.deletedby
                      FROM {course_modules} cm
                      JOIN {oublog} b ON b.id = cm.instance
                      JOIN {oublog_instances} bi ON bi.oublogid = b.id
                      JOIN {oublog_posts} bp ON bp.oubloginstancesid = bi.id
                      JOIN {oublog_comments} bc ON bc.postid = bp.id
                     WHERE cm.id = :contextinstanceid AND bc.userid = :commentuserid
                  ORDER BY bc.timeposted";
            $params = [
                'contextinstanceid' => $context->instanceid,
                'commentuserid' => $user->id
            ];
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $rec) {
                $path = array_merge(self::get_posts_path($rec),
                        [get_string('privacy_commentnumber', 'mod_oublog', $rec->commentid)]);

                // Replace any embedded file references in the message.
                $commentmessage = format_text(writer::with_context($context)->rewrite_pluginfile_urls($path, 'mod_oublog',
                        'messagecomment', $rec->commentid, $rec->commentmessage), FORMAT_HTML);

                // Export attachments for comment.
                writer::with_context($context)->export_area_files($path, 'mod_oublog', 'messagecomment', $rec->commentid);

                // Basic data (available for all comments).
                $commentdata = [
                    'postid' => $rec->postid,
                    'commentid' => $rec->commentid,
                    'author' => self::you_or_somebody_else($rec->userid, $user),
                    'title' => $rec->commenttitle,
                    'message' => $commentmessage,
                    'timeposted' => transform::datetime($rec->timeposted)
                ];

                // Deleted info.
                if ($rec->deletedby) {
                    $postdata['deletedby'] = self::you_or_somebody_else($rec->deletedby, $user);
                    $postdata['timedeleted'] = transform::datetime($rec->timedeleted);
                }

                if (empty($contextdata->comments)) {
                    $contextdata->comments = [];
                }
                $contextdata->comments[] = (object)$commentdata;
            }
            $rs->close();

            // Select all the links (we just assume they are yours if they are in your blog).
            // This is all the data from oublog_links table.
            $sql = "SELECT bl.id AS linkid,
                           bl.title,
                           bl.url
                      FROM {course_modules} cm
                      JOIN {oublog} b ON b.id = cm.instance
                      JOIN {oublog_instances} bi ON bi.oublogid = b.id
                      JOIN {oublog_links} bl ON bl.oubloginstancesid = bi.id
                     WHERE cm.id = :contextinstanceid AND bi.userid = :instanceuserid
                  ORDER BY bl.sortorder";
            $params = [
                'contextinstanceid' => $context->instanceid,
                'instanceuserid' => $user->id
            ];
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $rec) {
                $linkdata = [
                    'title' => $rec->title,
                    'url' => $rec->url
                ];
                if (empty($contextdata->links)) {
                    $contextdata->links = [];
                }
                $contextdata->links[] = (object)$linkdata;
            }
            $rs->close();

            // Write out context data.
            writer::with_context($context)->export_data([], $contextdata);
        }
    }

    /**
     * Removes personally-identifiable data from a user id for export.
     *
     * @param int $userid User id of a person
     * @param \stdClass $user Object representing current user being considered
     * @return string 'You' if the two users match, 'Somebody else' otherwise
     */
    protected static function you_or_somebody_else($userid, $user) {
        if ($userid == $user->id) {
            return get_string('privacy_you', 'oublog');
        } else {
            return get_string('privacy_somebodyelse', 'oublog');
        }
    }

    /**
     * Given a blog post record, returns a shortened version of the title for use in the subcontext
     * path, with the post id appended.
     *
     * @param \stdClass $rec Record with message, title, and postid fields.
     * @return string[] Posts path
     */
    protected static function get_posts_path($rec) : array {
        if (is_null($rec->title)) {
            $title = shorten_text(strip_tags($rec->message), self::TITLE_LENGTH_IN_PATH);
        } else {
            $title = shorten_text(format_string($rec->title), self::TITLE_LENGTH_IN_PATH);
        }
        return [get_string('posts', 'mod_oublog'), $title . ' (' . $rec->postid . ')'];
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        if (!$cm = get_coursemodule_from_id('oublog', $context->instanceid)) {
            return;
        }

        // Delete moderated comments (they aren't exported as we don't have user id, but they
        // might as well be deleted here), comments, and edits.
        $postswhere = "postid IN (
                SELECT bp.id
                  FROM {oublog_instances} bi
                  JOIN {oublog_posts} bp ON bp.oubloginstancesid = bi.id
                 WHERE bi.oublogid = ?)";
        $params = [$cm->instance];
        $DB->delete_records_select('oublog_comments_moderated', $postswhere, $params);
        $DB->delete_records_select('oublog_edits', $postswhere, $params);
        $DB->delete_records_select('oublog_comments', $postswhere, $params);

        // Delete all posts, tags, and links on these blogs.
        $instancewhere = "oubloginstancesid IN (
                SELECT bi.id
                  FROM {oublog_instances} bi
                 WHERE bi.oublogid = ?)";
        $DB->delete_records_select('oublog_taginstances', $instancewhere, $params);
        $DB->delete_records_select('oublog_links', $instancewhere, $params);
        $DB->delete_records_select('oublog_posts', $instancewhere, $params);

        // Delete all the instances.
        $DB->delete_records('oublog_instances', ['oublogid' => $cm->instance]);

        // Delete entry and attachment files.
        get_file_storage()->delete_area_files($context->id, 'mod_oublog', 'message');
        get_file_storage()->delete_area_files($context->id, 'mod_oublog', 'messagecomment');
        get_file_storage()->delete_area_files($context->id, 'mod_oublog', 'attachment');
        get_file_storage()->delete_area_files($context->id, 'mod_oublog', 'edit');

        // Delete related ratings.
        \core_rating\privacy\provider::delete_ratings($context, 'mod_oublog', 'post');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        // This is a little complex regarding data that can safely be deleted or not. The
        // decision here is:
        //
        // Delete all posts owned by the user (even if they aren't the last to edit). Including:
        // * The actual oublog_posts entry.
        // * All tag instances.
        // * All files (attachments, message).
        // * All comments and edits (even by other people) including files.
        // Delete blog instance owned by the user.
        // Delete all old versions of other people's posts by this user:
        // * The oublog_edits entry.
        // * All files.
        // For posts by other users where this user is the editedby id, just change that id
        // Anonymise all comments on other people's posts by this user:
        // * Replace the text with placeholder.
        // * Replace user id with admin.
        // * Delete files.
        // Delete all links added by the user (nothing else depends on these).

        $fs = get_file_storage();
        foreach ($contextlist as $context) {

            // Query for all posts owned by the user in this blog.
            $postsql = "
                    SELECT bp.id AS postid
                      FROM {course_modules} cm
                      JOIN {oublog} b ON b.id = cm.instance
                      JOIN {oublog_instances} bi ON bi.oublogid = b.id
                      JOIN {oublog_posts} bp ON bp.oubloginstancesid = bi.id
                     WHERE cm.id = :blogcmid AND bi.userid = :userid";
            $params = [
                'blogcmid' => $context->instanceid,
                'userid' => $contextlist->get_user()->id
            ];

            // Delete files from these posts.
            $fs->delete_area_files_select($context->id, 'mod_oublog', 'message',
                    "IN ($postsql)", $params);
            $fs->delete_area_files_select($context->id, 'mod_oublog', 'attachment',
                    "IN ($postsql)", $params);

            // Delete ratings from these posts.
            \core_rating\privacy\provider::delete_ratings_select($context, 'mod_oublog', 'post',
                    "IN ($postsql)", $params);

            // Delete all comments (including files) on posts owned by the user.
            $commentsql = "
                    SELECT bc.id AS commentid
                      FROM {oublog_comments} bc
                     WHERE bc.postid IN ($postsql)";
            $fs->delete_area_files_select($context->id, 'mod_oublog', 'messagecomment',
                    "IN ($commentsql)", $params);
            $DB->delete_records_select('oublog_comments', "id IN ($commentsql)", $params);

            // Delete all edits (including files) on posts owned by the user.
            $editsql = "
                    SELECT be.id AS editid
                      FROM {oublog_edits} be
                     WHERE be.postid IN ($postsql)";
            $fs->delete_area_files_select($context->id, 'mod_oublog', 'edit',
                    "IN ($editsql)", $params);
            $DB->delete_records_select('oublog_edits', "id IN ($editsql)", $params);

            // Delete tag instances from all these posts.
            $DB->delete_records_select('oublog_taginstances', "postid IN ($postsql)", $params);

            // Delete the actual posts.
            $DB->delete_records_select('oublog_posts', "id IN ($postsql)", $params);

            $instancesql = "
                    SELECT bi.id AS instanceid
                      FROM {course_modules} cm
                      JOIN {oublog} b ON b.id = cm.instance
                      JOIN {oublog_instances} bi ON bi.oublogid = b.id
                     WHERE cm.id = :blogcmid AND bi.userid = :userid";

            // Delete all links added by the user.
            $DB->delete_records_select('oublog_links', "oubloginstancesid IN ($instancesql)", $params);

            // Delete the blog instance owned by the user.
            $DB->delete_records_select('oublog_instances', "id IN ($instancesql)", $params);

            // Delete edits (including files) on other people's posts by this user.
            $editsql = "
                    SELECT be.id AS editid
                      FROM {course_modules} cm
                      JOIN {oublog} b ON b.id = cm.instance
                      JOIN {oublog_instances} bi ON bi.oublogid = b.id
                      JOIN {oublog_posts} bp ON bp.oubloginstancesid = bi.id
                      JOIN {oublog_edits} be ON be.postid = bp.id
                     WHERE cm.id = :blogcmid AND be.userid = :userid";
            $fs->delete_area_files_select($context->id, 'mod_oublog', 'edit',
                    "IN ($editsql)", $params);
            $DB->delete_records_select('oublog_edits', "id IN ($editsql)", $params);

            // Fix up the editedby, deletedby on posts where it's this user.
            $admin = get_admin();
            $allpostsql = "
                    SELECT bp.id AS postid
                      FROM {course_modules} cm
                      JOIN {oublog} b ON b.id = cm.instance
                      JOIN {oublog_instances} bi ON bi.oublogid = b.id
                      JOIN {oublog_posts} bp ON bp.oubloginstancesid = bi.id
                     WHERE cm.id = :blogcmid";
            $DB->set_field_select('oublog_posts', 'lasteditedby', $admin->id,
                    "id IN ($allpostsql AND bp.lasteditedby = :userid)", $params);
            $DB->set_field_select('oublog_posts', 'deletedby', $admin->id,
                    "id IN ($allpostsql AND bp.deletedby = :userid)", $params);

            // Find comments for other people's posts.
            $commentsql = "
                    SELECT bc.id AS commentid
                      FROM {course_modules} cm
                      JOIN {oublog} b ON b.id = cm.instance
                      JOIN {oublog_instances} bi ON bi.oublogid = b.id
                      JOIN {oublog_posts} bp ON bp.oubloginstancesid = bi.id
                      JOIN {oublog_comments} bc ON bc.postid = bp.id
                     WHERE cm.id = :blogcmid AND bc.userid = :userid";

            // Delete the files.
            $fs->delete_area_files_select($context->id, 'mod_oublog', 'messagecomment',
                    "IN ($commentsql)", $params);

            // Replace the text and user id by admin user.
            $placeholder = get_string('privacy_commentplaceholder', 'oublog');
            $DB->execute('UPDATE {oublog_comments} SET userid = :adminid, title = :title, ' .
                    'message = :message WHERE id IN (' . $commentsql . ')', array_merge($params,
                    ['adminid' => $admin->id, 'title' => '', 'message' => $placeholder]));
        }
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param   int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/oublog/locallib.php');

        $value = get_user_preferences('oublog_tagorder', null, $userid);
        if ($value !== null) {
            switch ($value) {
                case 'alpha':
                    $description = get_string('alpha', 'oublog');
                    break;
                case 'use':
                    $description = get_string('use', 'oublog');
                    break;
            }
            writer::export_user_preference('mod_oublog', 'oublog_tagorder', $value, $description);
        }

        foreach (['post', 'visit', 'comment', 'commentpost'] as $thing) {
            $field = 'mod_oublog_' . $thing . 'formfilter';
            $value = get_user_preferences($field, null, $userid);
            $description = '';
            if ($value !== null) {
                switch ($value) {
                    case OUBLOG_STATS_TIMEFILTER_ALL:
                        $description = get_string('timefilter_alltime', 'oublog');
                        break;
                    case OUBLOG_STATS_TIMEFILTER_MONTH:
                        $description = get_string('timefilter_thismonth', 'oublog');
                        break;
                    case OUBLOG_STATS_TIMEFILTER_YEAR:
                        $description = get_string('timefilter_thisyear', 'oublog');
                        break;
                }
                writer::export_user_preference('mod_oublog', $field, $value, $description);
            }

            $field = 'mod_oublog_hidestatsform_' . $thing . 'stats';
            $value = get_user_preferences($field, null, $userid);
            if ($value !== null) {
                if ($value) {
                    $description = get_string('hide');
                } else {
                    $description = get_string('show');
                }
                writer::export_user_preference('mod_oublog', $field, $value, $description);
            }
        }

        foreach (['view', 'allposts'] as $thing) {
            $field = 'oublog_accordion_' . $thing . '_open';
            $value = get_user_preferences($field, null, $userid);
            if ($value !== null) {
                if ($value) {
                    $description = get_string('accordion_open', 'oublog');
                } else {
                    $description = get_string('accordion_closed', 'oublog');
                }
                writer::export_user_preference('mod_oublog', $field, $value, $description);
            }
        }
    }
}
