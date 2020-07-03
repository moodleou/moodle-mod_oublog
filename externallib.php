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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/oublog/locallib.php");

class mod_oublog_external extends external_api {
    /**
     * Some of these functions work differently in the Open University install.
     *
     * @return bool True if OU
     */
    public static function is_ou(): bool {
        return class_exists('local_oudataload\users');
    }

    /**
     * Gets the identifier parameter - we have a standard definition at the OU, otherwise it's
     * just a username.
     *
     * @return external_single_structure Identifier structure
     */
    protected static function get_identifier_parameter(): external_single_structure {
        if (self::is_ou()) {
            return \local_oudataload\users::get_webservice_identifier_parameter();
        } else {
            return new external_single_structure([
                'username' => new \external_value(PARAM_ALPHANUM, 'Moodle username'),
            ]);
        }
    }

    /**
     * Gets a user based on the webservice identifier parameter.
     *
     * @param array|\stdClass $identifier Identifier param
     * @return \stdClass|null User object or null if not found
     */
    protected static function get_user_for_webservice($identifier) {
        global $DB;
        if (self::is_ou()) {
            return \local_oudataload\users::get_user_for_webservice($identifier, true);
        } else {
            $identifier = (array)$identifier;
            $user = $DB->get_record('user', ['username' => $identifier['username']]);
            return $user ? $user : null;
        }
    }

    /**
     * Converts from a username (legacy service call) to identifier array.
     *
     * @param string $username Username
     * @return string[] Identifier array
     */
    protected static function convert_username(string $username): array {
        if (self::is_ou()) {
            return ['oucu' => $username];
        } else {
            return ['username' => $username];
        }
    }

    public static function get_user_blogs_parameters() {
        return new external_function_parameters(array(
                'username' => new external_value(PARAM_USERNAME, 'User username')
                ));
    }

    /**
     * Gets parameters for webservice call.
     *
     * @return external_function_parameters Parameters
     */
    public static function get_user_blogs2_parameters(): external_function_parameters {
        return new external_function_parameters([
            'identifier' => self::get_identifier_parameter()
        ]);
    }

    /**
     * Return all blogs on the system for the user
     *
     * @param string $username
     */
    public static function get_user_blogs(string $username) {
        return self::get_user_blogs2(self::convert_username($username));
    }

    /**
     * Return all blogs on the system for the user
     *
     * @param array|\stdClass $identifier Standard identifier
     */
    public static function get_user_blogs2($identifier) {
        global $remoteuserid;
        ['identifier' => $identifier] = self::validate_parameters(self::get_user_blogs2_parameters(),
                ['identifier' => $identifier]);
        $userobj = self::get_user_for_webservice($identifier);
        if (!$userobj) {
            return array();
        }
        $user = $userobj->id;

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

    /**
     * Webservice return type.
     *
     * @return external_multiple_structure Return type
     */
    public static function get_user_blogs2_returns(): external_multiple_structure {
        return self::get_user_blogs_returns();
    }

    public static function get_blog_info_parameters() {
        return new external_function_parameters(
                array(
                        'cmid' => new external_value(PARAM_INT, 'Blog cm id'),
                        'username' => new external_value(PARAM_USERNAME, 'User username'),
                        'sharedblogcmid' => new external_value(PARAM_INT, 'Shared Blog cm id'),
                )
        );
    }

    /**
     * Gets parameters for webservice.
     *
     * @return external_function_parameters Parameters
     */
    public static function get_blog_info2_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Blog cm id'),
            'identifier' => self::get_identifier_parameter(),
            'sharedblogcmid' => new external_value(PARAM_INT, 'Shared Blog cm id'),
        ]);
    }

    public static function get_blog_info($cmid, $username, $sharedblogcmid = null) {
        return self::get_blog_info2($cmid, self::convert_username($username), $sharedblogcmid);
    }

    /**
     * Gets info about a blog.
     *
     * @param int $cmid Course-module id
     * @param array|\stdClass $identifier Standard array of user identifiers
     * @param null|cmid $sharedblogcmid Shared blog cmid
     * @return array Blog info
     */
    public static function get_blog_info2($cmid, $identifier, $sharedblogcmid = null) {
        global $remoteuserid;
        ['cmid' => $cmid, 'identifier' => $identifier, 'sharedblogcmid' => $sharedblogcmid] =
                self::validate_parameters(self::get_blog_info2_parameters(),
                    ['cmid' => $cmid, 'identifier' => $identifier, 'sharedblogcmid' => $sharedblogcmid]);
        $userobj = self::get_user_for_webservice($identifier);
        if (!$userobj) {
            return array();
        }
        $user = $userobj->id;

        $remoteuserid = $user;
        $result = oublog_import_getbloginfo($cmid, $user, $sharedblogcmid);
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

    /**
     * Gets return type for webservice.
     *
     * @return external_single_structure Return type
     */
    public static function get_blog_info2_returns() {
        return self::get_blog_info_returns();
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
     * Gets parameters for webservice.
     *
     * @return external_function_parameters Parameters
     */
    public static function get_blog_allposts2_parameters(): external_function_parameters {
        return new external_function_parameters([
            'blogid' => new external_value(PARAM_INT, 'Blog id'),
            'sort' => new external_value(PARAM_TEXT, 'sort sql'),
            'identifier' => self::get_identifier_parameter(),
            'page' => new external_value(PARAM_INT, 'results page', VALUE_DEFAULT, 0),
            'tags' => new external_value(PARAM_SEQUENCE, 'tags to filter by', VALUE_DEFAULT, null),
        ]);
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
        return self::get_blog_allposts2($blogid, $sort, self::convert_username($username), $page, $tags);
    }

    /**
     * Gets all user posts from blog, filtered by page and tags
     *
     * @param int $blogid Blog id
     * @param string $sort Sort method
     * @param \stdClass|array $identifier User identifier
     * @param int $page Page (0 = first)
     * @param string $tags comma separated sequence of selected tag ids to filter by
     * @return array Webservice results
     */
    public static function get_blog_allposts2($blogid, $sort, $identifier, $page = 0, $tags = null) {
        ['blogid' => $blogid, 'sort' => $sort, 'identifier' => $identifier, 'page' => $page, 'tags' => $tags] =
                self::validate_parameters(self::get_blog_allposts2_parameters(),
                    ['blogid' => $blogid, 'sort' => $sort, 'identifier' => $identifier, 'page' => $page, 'tags' => $tags]);
        $userobj = self::get_user_for_webservice($identifier);
        if (!$userobj) {
            return array();
        }
        $user = $userobj->id;
        $result = oublog_import_getallposts($blogid, $sort, $user, $page, $tags);
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

    /**
     * Gets webservice return type.
     *
     * @return external_single_structure Return type
     */
    public static function get_blog_allposts2_returns() {
        return self::get_blog_allposts_returns();
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
     * Gets webservice parameters.
     *
     * @return external_function_parameters Parameters
     */
    public static function get_blog_posts2_parameters(): external_function_parameters {
        return new external_function_parameters([
            'blogid' => new external_value(PARAM_INT, 'Blog id'),
            'bcontextid' => new external_value(PARAM_INT, 'Blog module context id'),
            'selected' => new external_value(PARAM_SEQUENCE, 'post ids'),
            'inccomments' => new external_value(PARAM_BOOL, 'include comments',
                    VALUE_DEFAULT, false),
            'identifier' => self::get_identifier_parameter(),
        ]);
    }

    /**
     * Get selected blog posts from blog
     *
     * @param int $blogid
     * @param string $selected comma separated sequence of selected post ids to filter by
     * @param bool $inccomments - blog uses comments or not
     * @param string $username - used to ensure user posts only
     * @return array of posts
     */
    public static function get_blog_posts($blogid, $bcontextid, $selected, $inccomments = false, $username) {
        return self::get_blog_posts2($blogid, $bcontextid, $selected, $inccomments, self::convert_username($username));
    }

    /**
     * Get selected blog posts from blog
     * @param int $blogid
     * @param string $selected comma separated sequence of selected post ids to filter by
     * @param bool $inccomments - blog uses comments or not
     * @param stdClass|array $identifier User identifier
     * @return array of posts
     */
    public static function get_blog_posts2($blogid, $bcontextid, $selected, $inccomments, $identifier) {
        ['blogid' => $blogid, 'bcontextid' => $bcontextid, 'selected' => $selected,
                'inccomments' => $inccomments, 'identifier' => $identifier] = self::validate_parameters(
                self::get_blog_posts2_parameters(), ['blogid' => $blogid, 'bcontextid' => $bcontextid,
                'selected' => $selected, 'inccomments' => $inccomments, 'identifier' => $identifier]);
        $userobj = self::get_user_for_webservice($identifier);
        if (!$userobj) {
            return array();
        }
        $user = $userobj->id;
        $selected = explode(',', $selected);
        if ($selected[0] == 0) {
            $return = oublog_import_getposts($blogid, $bcontextid, $selected, $inccomments, $user, true);
        } else {
            $return = oublog_import_getposts($blogid, $bcontextid, $selected, $inccomments, $user);
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

    /**
     * Gets return type for webservice.
     *
     * @return external_multiple_structure Return type
     */
    public static function get_blog_posts2_returns(): external_multiple_structure {
        return self::get_blog_posts_returns();
    }
}
