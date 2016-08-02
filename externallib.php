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
 * External oublog API.
 * Used for importing posts from another server.
 *
 * @package mod
 * @subpackage oublog
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/oublog/locallib.php");

class mod_oublog_external extends external_api {

    public static function get_user_blogs_parameters() {
        return new external_function_parameters(array(
                'username' => new external_value(PARAM_USERNAME, 'User username')
                ));
    }

    /**
     * Return all blogs on the system for the user
     * @param string $username
     */
    public static function get_user_blogs($username) {
        global $DB, $remoteuserid;
        $username = self::validate_parameters(self::get_user_blogs_parameters(),
                array('username' => $username));
        $user = $DB->get_field('user', 'id', array('username' => $username['username']), IGNORE_MISSING);
        if (!$user) {
            return array();
        }
        $remoteuserid = $user;
        $result = oublog_import_getblogs($user);
        // Add remote property to each blog to identify that it came from web service.
        foreach ($result as &$blog) {
            $blog->remote = true;
        }
        return $result;
    }

    public static function get_user_blogs_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                        array(
                                'cmid' => new external_value(PARAM_INT, 'blog course module id'),
                                'coursename' => new external_value(PARAM_TEXT, 'course name text'),
                                'numposts' => new external_value(PARAM_INT, 'number of posts in blog'),
                                'name' => new external_value(PARAM_TEXT, 'activity name text'),
                                'remote' => new external_value(PARAM_BOOL, 'identifies activity is remote'),
                        )
                    )
                );
    }

    public static function get_blog_info_parameters() {
        return new external_function_parameters(
                array(
                        'cmid' => new external_value(PARAM_INT, 'Blog cm id'),
                        'username' => new external_value(PARAM_USERNAME, 'User username'),
                )
        );
    }

    public static function get_blog_info($cmid, $username) {
        global $DB, $remoteuserid;
        $params = self::validate_parameters(self::get_blog_info_parameters(),
                array('cmid' => $cmid, 'username' => $username));
        $user = $DB->get_field('user', 'id', array('username' => $params['username']), IGNORE_MISSING);
        if (!$user) {
            return array();
        }
        $remoteuserid = $user;
        $result = oublog_import_getbloginfo($params['cmid'], $user);
        return array(
                'bcmid' => $result[0],
                'boublogid' => $result[1],
                'bcontextid' => $result[2],
                'boublogname' => $result[3],
                'bcoursename' => $result[4],
                );
    }

    public static function get_blog_info_returns() {
        return new external_single_structure(
                array(
                        'bcmid' => new external_value(PARAM_INT, 'Blog cm id'),
                        'boublogid' => new external_value(PARAM_INT, 'Blog id'),
                        'bcontextid' => new external_value(PARAM_INT, 'Blog context id'),
                        'boublogname' => new external_value(PARAM_TEXT, 'Blog name'),
                        'bcoursename' => new external_value(PARAM_TEXT, 'Course short name'),
                        ));
    }

    public static function get_blog_allposts_parameters() {
        return new external_function_parameters(
                array(
                        'blogid' => new external_value(PARAM_INT, 'Blog id'),
                        'sort' => new external_value(PARAM_TEXT, 'sort sql'),
                        'username' => new external_value(PARAM_USERNAME, 'User username'),
                        'page' => new external_value(PARAM_INT, 'results page', VALUE_DEFAULT, 0),
                        'tags' => new external_value(PARAM_SEQUENCE, 'tags to filter by', VALUE_DEFAULT, null),
                )
        );
    }

    /**
     * Gets all user posts from blog, filtered by page and tags
     * @param int $blogid
     * @param string $sort
     * @param string $username
     * @param int $page
     * @param string $tags comma separated sequence of selected tag ids to filter by
     * @return array
     */
    public static function get_blog_allposts($blogid, $sort, $username, $page = 0, $tags = null) {
        global $DB;
        $params = self::validate_parameters(self::get_blog_allposts_parameters(),
                array('blogid' => $blogid, 'sort' => $sort, 'username' => $username,
                        'page' => $page, 'tags' => $tags));
        $user = $DB->get_field('user', 'id', array('username' => $params['username']), IGNORE_MISSING);
        if (!$user) {
            return array();
        }
        $result = oublog_import_getallposts($params['blogid'], $params['sort'], $user,
                $params['page'], $params['tags']);
        if (!is_array($result[2])) {
            $result[2] = array();
        }
        foreach ($result[0] as &$post) {
            if (isset($post->tags)) {
                $tagupdate = array();
                // Update post tags into a format that Moodle WS can work with.
                foreach ($post->tags as $id => $tag) {
                    $tagupdate[] = (object) array('id' => $id, 'tag' => $tag);
                }
                $post->tags = $tagupdate;
            }
        }
        return array('posts' => $result[0], 'total' => $result[1], 'tagnames' => $result[2]);
    }

    public static function get_blog_allposts_returns() {
        return new external_single_structure(array(
                'posts' => new external_multiple_structure(new external_single_structure(array(
                        'id' => new external_value(PARAM_INT, 'post id'),
                        'title' => new external_value(PARAM_TEXT, 'title'),
                        'timeposted' => new external_value(PARAM_INT, 'created'),
                        'tags' => new external_multiple_structure(new external_single_structure(array(
                                'id' => new external_value(PARAM_INT, 'tag id'),
                                'tag' => new external_value(PARAM_TEXT, 'tag value'))
                                ), 'tags', VALUE_OPTIONAL),
                        ))),
                'total' => new external_value(PARAM_INT, 'total user posts in blog'),
                'tagnames' => new external_multiple_structure(
                        new external_single_structure(array(
                                'id' => new external_value(PARAM_INT, 'tag id'),
                                'tag' => new external_value(PARAM_TEXT, 'tag value'),
                                )), 'tags', VALUE_OPTIONAL)
                ));
    }

    public static function get_blog_posts_parameters() {
        return new external_function_parameters(
                array(
                        'blogid' => new external_value(PARAM_INT, 'Blog id'),
                        'bcontextid' => new external_value(PARAM_INT, 'Blog module context id'),
                        'selected' => new external_value(PARAM_SEQUENCE, 'post ids'),
                        'inccomments' => new external_value(PARAM_BOOL, 'include comments',
                                VALUE_DEFAULT, false),
                        'username' => new external_value(PARAM_USERNAME, 'User username'),
                )
        );
    }

    /**
     * Get selected blog posts from blog
     * @param int $blogid
     * @param string $selected comma separated sequence of selected post ids to filter by
     * @param bool $inccomments - blog uses comments or not
     * @param string $username - used to ensure user posts only
     * @return array of posts
     */
    public static function get_blog_posts($blogid, $bcontextid, $selected, $inccomments = false, $username) {
        global $DB;
        $params = self::validate_parameters(self::get_blog_posts_parameters(),
                array('blogid' => $blogid, 'bcontextid' => $bcontextid, 'selected' => $selected,
                        'inccomments' => $inccomments, 'username' => $username));
        $user = $DB->get_field('user', 'id', array('username' => $username), IGNORE_MISSING);
        if (!$user) {
            return array();
        }
        $selected = explode(',', $params['selected']);
        if ($selected[0] == 0) {
            $return = oublog_import_getposts($params['blogid'], $params['bcontextid'],
                $selected, $params['inccomments'], $user, true);
        } else {
            $return = oublog_import_getposts($params['blogid'], $params['bcontextid'],
                $selected, $params['inccomments'], $user);
        }
        // Convert file objects into a custom known object to send.
        foreach ($return as &$post) {
            foreach ($post->images as &$file) {
                $file = (object) array(
                        'contextid' => $file->get_contextid(),
                        'filearea' => $file->get_filearea(),
                        'filepath' => $file->get_filepath(),
                        'filename' => $file->get_filename(),
                        'itemid' => $file->get_itemid()
                        );
            }
            foreach ($post->attachments as &$file) {
                $file = (object) array(
                        'contextid' => $file->get_contextid(),
                        'filearea' => $file->get_filearea(),
                        'filepath' => $file->get_filepath(),
                        'filename' => $file->get_filename(),
                        'itemid' => $file->get_itemid()
                );
            }
            foreach ($post->comments as &$comment) {
                foreach ($comment->images as &$file) {
                    $file = (object) array(
                            'contextid' => $file->get_contextid(),
                            'filearea' => $file->get_filearea(),
                            'filepath' => $file->get_filepath(),
                            'filename' => $file->get_filename(),
                            'itemid' => $file->get_itemid()
                    );
                }
            }
        }
        return $return;
    }

    public static function get_blog_posts_returns() {
        return new external_multiple_structure(new external_single_structure(array(
                'id' => new external_value(PARAM_INT, 'post id'),
                'oubloginstancesid' => new external_value(PARAM_INT, 'instance id'),
                'groupid' => new external_value(PARAM_INT, 'group id'),
                'title' => new external_value(PARAM_TEXT, 'title'),
                'message' => new external_value(PARAM_RAW, 'message'),
                'timeposted' => new external_value(PARAM_INT, 'created'),
                'allowcomments' => new external_value(PARAM_INT, 'comments allowed'),
                'timeupdated' => new external_value(PARAM_INT, 'updated'),
                'deletedby' => new external_value(PARAM_INT, 'deleted by'),
                'timedeleted' => new external_value(PARAM_INT, 'deleted'),
                'visibility' => new external_value(PARAM_INT, 'visibility'),
                'lasteditedby' => new external_value(PARAM_INT, 'edited by'),
                'tags' => new external_multiple_structure(new external_single_structure(array(
                        'id' => new external_value(PARAM_INT, 'tag id'),
                        'tag' => new external_value(PARAM_TEXT, 'tag value'),
                        'postid' => new external_value(PARAM_INT, 'tag post id'),
                        )), 'tags', VALUE_OPTIONAL),
                'images' => new external_multiple_structure(new external_single_structure(array(
                        'contextid' => new external_value(PARAM_INT, 'context id'),
                        'filearea' => new external_value(PARAM_AREA, 'filearea'),
                        'filepath' => new external_value(PARAM_PATH, 'path'),
                        'filename' => new external_value(PARAM_FILE, 'filename'),
                        'itemid' => new external_value(PARAM_INT, 'item id'),
                        )), 'images', VALUE_OPTIONAL),
                'attachments' => new external_multiple_structure(new external_single_structure(array(
                        'contextid' => new external_value(PARAM_INT, 'context id'),
                        'filearea' => new external_value(PARAM_AREA, 'filearea'),
                        'filepath' => new external_value(PARAM_PATH, 'filepath'),
                        'filename' => new external_value(PARAM_FILE, 'filename'),
                        'itemid' => new external_value(PARAM_INT, 'item id'),
                )), 'attachments', VALUE_OPTIONAL),
                'comments' => new external_multiple_structure(
                        new external_single_structure(array(
                                'id' => new external_value(PARAM_INT, 'id'),
                                'postid' => new external_value(PARAM_INT, 'post id'),
                                'userid' => new external_value(PARAM_INT, 'user id'),
                                'title' => new external_value(PARAM_TEXT, 'title'),
                                'message' => new external_value(PARAM_RAW, 'message'),
                                'timeposted' => new external_value(PARAM_INT, 'posted'),
                                'deletedby' => new external_value(PARAM_INT, 'deleted by'),
                                'timedeleted' => new external_value(PARAM_INT, 'deleted'),
                                'authorname' => new external_value(PARAM_INT, 'ex author'),
                                'authorip' => new external_value(PARAM_INT, 'ex author ip'),
                                'timeapproved' => new external_value(PARAM_INT, 'approved'),
                                'images' => new external_multiple_structure(new external_single_structure(array(
                                        'contextid' => new external_value(PARAM_INT, 'context id'),
                                        'filearea' => new external_value(PARAM_AREA, 'filearea'),
                                        'filepath' => new external_value(PARAM_PATH, 'path'),
                                        'filename' => new external_value(PARAM_FILE, 'filename'),
                                        'itemid' => new external_value(PARAM_INT, 'item id'),
                                )), 'images', VALUE_OPTIONAL),
                                )), 'comments', VALUE_OPTIONAL)
                )));
    }
}
