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
 * Library of functions used by the oublog module.
 *
 * This contains functions that are called from within the oublog module only
 * Functions that are also called by core Moodle are in {@link lib.php}
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @author M Kassaei <m.kassaei@open.ac.uk>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package oublog
 */

// OU shared APIs which (for OU system) are present in local, elsewhere
// are incorporated in module
@include_once(dirname(__FILE__).'/../../local/transaction_wrapper.php');
if (!class_exists('transaction_wrapper')) {
    require_once(dirname(__FILE__).'/null_transaction_wrapper.php');
}
require_once($CFG->libdir . '/portfolio/caller.php');
require_once($CFG->libdir . '/gradelib.php');

/**#@+
 * Constants defining the visibility levels of blog posts
 */
define('OUBLOG_VISIBILITY_COURSEUSER',   100);
define('OUBLOG_VISIBILITY_LOGGEDINUSER', 200);
define('OUBLOG_VISIBILITY_PUBLIC',       300);
/**#@-*/

/**#@+
 * Constants defining the ability to post comments
 */
define('OUBLOG_COMMENTS_PREVENT', 0);
define('OUBLOG_COMMENTS_ALLOW',   1);
define('OUBLOG_COMMENTS_ALLOWPUBLIC', 2);
/**#@-*/

/**#@+
 * Constants for the 'approval' field in modreated comments
 */
define('OUBLOG_MODERATED_UNSET', 0);
define('OUBLOG_MODERATED_APPROVED', 1);
define('OUBLOG_MODERATED_REJECTED', 2);
/**#@-*/

/**#@+
 * Constant defining the number of posts to display per page
 */
define('OUBLOG_POSTS_PER_PAGE', 20);
/**#@-*/

/**#@+
 * Constant defining the max number of items in an RSS or Atom feed
 */
define('OUBLOG_MAX_FEED_ITEMS', 20);
/**#@-*/

/**#@+
 * Constants defining the visibility for participation pages
 */
define('OUBLOG_USER_PARTICIPATION', 2);
define('OUBLOG_MY_PARTICIPATION', 1);
define('OUBLOG_NO_PARTICIPATION', 0);
define('OUBLOG_PARTICIPATION_PERPAGE', 100);
/**#@-*/



/**
 * Get a blog from a user id
 *
 * @param int $userid
 * @return mixed Oublog object on success, false on failure
 */
function oublog_get_personal_blog($userid) {
    global $CFG, $DB;

    if (!$blog = $DB->get_record('oublog', array('global'=>1))) {
        print_error('globalblogmissing','oublog');
    }

    if (!$oubloginstance = $DB->get_record('oublog_instances', array('oublogid'=>$blog->id, 'userid'=>$userid))) {
        $user = $DB->get_record('user', array('id'=>$userid));
        oublog_add_bloginstance($blog->id, $userid, get_string('defaultpersonalblogname', 'oublog', fullname($user)));
        if (!$oubloginstance = $DB->get_record('oublog_instances', array('oublogid'=>$blog->id, 'userid'=>$user->id))) {
            print_error('invalidblog','oublog');
        }
    }

    return(array($blog, $oubloginstance));
}

/**
 * Obtains the oublog object based on a post ID.
 * @param int $postid Post ID
 * @return object Moodle data object for oublog row, or false if not found
 */
function oublog_get_blog_from_postid($postid) {
    global $DB;
    $postid = (int)$postid;
    return $DB->get_record_sql("
SELECT
    o.*
FROM
    {oublog_posts} p
    INNER JOIN {oublog_instances} i on i.id = p.oubloginstancesid
    INNER JOIN {oublog} o ON o.id = i.oublogid
WHERE
    p.id= ? ", array($postid));
}

/**
 * Checks if a user is allowed to view a blog. If not, will not return (calls
 * an error function and exits).
 * @param object $oublog
 * @param object $context
 * @param object $cm
 * @return void
 */
function oublog_check_view_permissions($oublog, $context, $cm=null) {
    global $COURSE, $PAGE, $DB;

    $capability=$oublog->global ? 'mod/oublog:viewpersonal' : 'mod/oublog:view';

    switch ($oublog->maxvisibility) {
        case OUBLOG_VISIBILITY_PUBLIC:
            if ($oublog->course == $COURSE->id or empty($oublog->course)) {
                $oublogcourse = $COURSE;
            } else {
                $oublogcourse = $DB->get_record('course', array('id'=>$oublog->course),
                        '*', MUST_EXIST);
            }
            $PAGE->set_course($oublogcourse);
            $PAGE->set_cm($cm, $oublogcourse);
            $PAGE->set_pagelayout('incourse');
            return;

        case OUBLOG_VISIBILITY_LOGGEDINUSER:
            require_login(SITEID, false);
            if ($oublog->course == $COURSE->id or empty($oublog->course)) {
                $oublogcourse = $COURSE;
            } else {
                $oublogcourse = $DB->get_record('course', array('id'=>$oublog->course),
                        '*', MUST_EXIST);
            }
            $PAGE->set_course($oublogcourse);
            $PAGE->set_cm($cm, $oublogcourse);
            $PAGE->set_pagelayout('incourse');
            // Check oublog:view cap
            if (!has_capability($capability, $context)) {
                print_error('accessdenied', 'oublog');
            }
            return;

        case OUBLOG_VISIBILITY_COURSEUSER:
            require_course_login($oublog->course, false, $cm);
            // Check oublog:view cap
            if (!has_capability($capability, $context)) {
                print_error('accessdenied', 'oublog');
            }
            return;

        default:
            print_error('invalidvisibility','oublog');
    }
}

/**
 * Determines whether the user can make a post to the given blog.
 * @param $oublog Blog object
 * @param $bloguserid Userid of person who owns blog (only needed for
 *   personal blog)
 * @param $cm Course-module (only needed if not personal blog)
 * @return bool True if user is allowed to make posts
 */
function oublog_can_post($oublog, $bloguserid=0, $cm=null) {
    global $USER;
    if($oublog->global) {
        if($bloguserid==0) {
            debugging('Calls to oublog_can_post for personal blogs must supply userid!',DEBUG_DEVELOPER);
        }
        // This needs to be your blog and you need the 'contributepersonal'
        // permission at system level
        return $bloguserid==$USER->id &&
            has_capability('mod/oublog:contributepersonal',
                get_context_instance(CONTEXT_SYSTEM));
    } else {
        // Need specific post permission in this blog
        return has_capability('mod/oublog:post',
            get_context_instance(CONTEXT_MODULE,$cm->id));
    }
}

/**
 * Determines whether the user can comment on the given blog post, presuming
 * that they are allowed to see it.
 * @param $cm Course-module (null if personal blog)
 * @param $oublog Blog object
 * @param $post Post object
 * @return bool True if user is allowed to make comments
 */
function oublog_can_comment($cm, $oublog, $post) {
    global $USER;
    if($oublog->global) {
        // Just need the 'contributepersonal' permission at system level, OR
        // if you are not logged in but the blog allows public comments.
        // Note that if you ARE logged in you must have the capability. This is
        // because logged-in comments do not go through moderation, so we want
        // to be able to prevent them by removing the capability. They will
        // still be able to make comments by logging out, but these will then
        // go through moderation.
        $blogok =
                (!isloggedin() && $oublog->allowcomments == OUBLOG_COMMENTS_ALLOWPUBLIC) ||
                has_capability('mod/oublog:contributepersonal',
                    get_context_instance(CONTEXT_SYSTEM));
    } else {
        $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);

        // Three ways you can comment to a course blog:
        $blogok =
                // 1. Blog allows public comments and you're not logged in.
                $oublog->allowcomments == (OUBLOG_COMMENTS_ALLOWPUBLIC && !isloggedin()) ||

                // 2. Post is visible to all logged-in users+, and you have the
                // contributepersonal capabilty normally used for personal blogs.
                ($post->visibility >= OUBLOG_VISIBILITY_LOGGEDINUSER
                    && $oublog->maxvisibility >= OUBLOG_VISIBILITY_LOGGEDINUSER
                    && has_capability('mod/oublog:contributepersonal',
                        $modcontext)) ||

                // 3. You have comment permission in the specific context
                // (= course member) and you are allowed to write to the blog
                // group i.e. it's your group.
                (has_capability('mod/oublog:comment', $modcontext) &&
                    oublog_is_writable_group($cm));

        // Note this logic is still a bit weird with regard to groups. If
        // there is a course blog with visible groups, users in other groups
        // can't comment; there is a CONTRIB bug request for us to make an
        // option for this. In that same situation, if a post is set to be
        // visible to logged-in users, now people not in groups can suddenly
        // comment. Hmmm. We might need another level of ->allowcomments to
        // make this make sense, or some other changes.
    }

    // If the blog allows comments, this post must allow comments and either
    // it allows public comments or you're logged in (and not guest)
    return $blogok && $post->allowcomments &&
            ($post->allowcomments >= OUBLOG_COMMENTS_ALLOWPUBLIC ||
                (isloggedin() && !isguestuser()));
}

/**
 * Wrapper around oublog_get_activity_group for increased performance.
 * @param object $cm Moodle course-module (possibly with extra cache fields)
 * @param boolean $update True to update from URL (must be first call on page)
 */
function oublog_get_activity_group($cm, $update=false) {
    if (!isset($cm->activitygroup) || $update) {
        if (isset($cm->activitygroup)) {
            debugging('Update parameter should be used only in first call to ' .
                    'oublog_get_activity_group; please fix this to improve ' .
                    'performance slightly', DEBUG_DEVELOPER);
        }
        $cm->activitygroup = groups_get_activity_group($cm, $update);
    }
    return $cm->activitygroup;
}

/**
 * Wrapper around oublog_get_activity_groupmode for increased performance.
 * @param object $cm Moodle course-module (possibly with extra cache fields)
 * @param object $course Optional course parameter; should be included in
 *   first call in page
 */
function oublog_get_activity_groupmode($cm, $course=null) {
    if (!isset($cm->activitygroupmode)) {
        if (!$course) {
            debugging('Course parameter should be provided in first call to ' .
                    'oublog_get_activity_groupmode; please fix this to improve ' .
                    'performance slightly', DEBUG_DEVELOPER);
        }
        $cm->activitygroupmode = groups_get_activity_groupmode($cm, $course);
    }
    return $cm->activitygroupmode;
}

/**
 * Checks whether a group is writable GIVEN THAT YOU CAN SEE THE BLOG
 * (i.e. this does not handle the separate-groups case, only visible-groups).
 * The information is static-cached so this function can be called multiple
 * times.
 * @param object $cm Moodle course-module
 */
function oublog_is_writable_group($cm) {
    $groupmode = oublog_get_activity_groupmode($cm);
    if ($groupmode != VISIBLEGROUPS) {
        // If no groups, then they must be allowed to access this;
        // if separate groups, then because this is defined to only work
        // for entries you can see, you must be allowed to access; so only
        // doubt is for visible groups.
        return true;
    }
    $groupid = oublog_get_activity_group($cm);
    if (isset($cm->writablegroups)) {
        $cm->writablegroups = array();
    }
    if (isset($cm->writablegroups[$groupid])) {
        return $cm->writablegroups[$groupid];
    }
    $cm->writablegroups[$groupid] = groups_is_member($groupid) ||
        has_capability('moodle/site:accessallgroups',
            get_context_instance(CONTEXT_MODULE, $cm->id));
    return $cm->writablegroups[$groupid];
}

/**
 * Determine if a user can view a post. Note that you must also call
 * oublog_check_view_permissions for the blog as a whole.
 *
 * @param object $post
 * @param object $user
 * @param object $context
 * @param bool $personalblog True if this is on a personal blog
 * @return bool
 */
function oublog_can_view_post($post, $user, $context, $personalblog) {

    // Public visibility means everyone
    if($post->visibility==OUBLOG_VISIBILITY_PUBLIC) {
        return true;
    }
    // Logged-in user visibility means everyone logged in, but no guests
    if($post->visibility==OUBLOG_VISIBILITY_LOGGEDINUSER &&
        (isloggedin() && !isguestuser())) {
        return true;
    } elseif ($post->visibility==OUBLOG_VISIBILITY_LOGGEDINUSER) {
        return false;
    }

    if($post->visibility!=OUBLOG_VISIBILITY_COURSEUSER) {
        print_error('invalidvisibilitylevel','oublog','', $post->visibility);
    }

    // Otherwise this is set to course visibility
    if($personalblog) {
        return $post->userid==$user->id;
    } else {
        // Check oublog:view capability at module level
        // This might not have been checked yet because if the blog is
        // set to public, you're allowed to view it, but maybe not this
        // post.
        return has_capability('mod/oublog:view',$context, $user->id);
    }
}



/**
 * Add a new blog post
 *
 * @param mixed $post An object containing all required post fields
 * @param object $cm Course-module for blog
 * @return mixed PostID on success or false
 */
function oublog_add_post($post,$cm,$oublog,$course) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/completionlib.php');
    $post->itemid = $post->message['itemid'];
    $post->message = $post->message['text'];
    $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (!isset($post->oubloginstancesid)) {
        if (!$post->oubloginstancesid = $DB->get_field('oublog_instances', 'id', array('oublogid'=>$post->oublogid, 'userid'=>$post->userid))) {
            if (!$post->oubloginstancesid = oublog_add_bloginstance($post->oublogid, $post->userid)) {
                return(false);
            }
        }
    }
    if (!isset($post->timeposted)) {
        $post->timeposted = time();
    }

    // Begin transaction
    $tw=new transaction_wrapper();

    if (!$postid = $DB->insert_record('oublog_posts', $post)) {
        $tw->rollback();
        return(false);
    }
    //now do filestuff
    if ($post->attachments) {
        file_save_draft_area_files($post->attachments, $modcontext->id, 'mod_oublog', 'attachment', $postid, array('subdirs' => 0));
    }

    $post->message = file_save_draft_area_files($post->itemid, $modcontext->id, 'mod_oublog', 'message', $postid, array('subdirs'=>0), $post->message);
    $DB->set_field('oublog_posts', 'message', $post->message, array('id'=>$postid));
    if (isset($post->tags)) {
        oublog_update_item_tags($post->oubloginstancesid, $postid, $post->tags,$post->visibility);
    }

    $post->id=$postid; // Needed by the below
    if(!oublog_search_update($post,$cm)) {
        $tw->rollback();
        return(false);
    }

    // Inform completion system, if available
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && ($oublog->completionposts)) {
        $completion->update_state($cm, COMPLETION_COMPLETE);
    }

    $tw->commit();

    return($postid);
}



/**
 * Update a blog post
 *
 * @param mixed $post An object containing all required post fields
 * @param object $cm Course-module for blog
 * @return bool
 */
function oublog_edit_post($post,$cm) {
    global $USER, $DB;
    $post->itemid = $post->message['itemid'];
    $post->message = $post->message['text'];
    $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
    if(!isset($post->id) || !$oldpost = $DB->get_record('oublog_posts', array('id'=>$post->id))) {
        return(false);
    }

    if (!$post->oubloginstancesid = $DB->get_field('oublog_instances', 'id', array('oublogid'=>$post->oublogid, 'userid'=>$post->userid))) {
        return(false);
    }

    // Begin transaction
    $tw=new transaction_wrapper();

    // insert edit history
    $edit = new stdClass();
    $edit->postid       = $post->id;
    $edit->userid       = $USER->id;
    $edit->timeupdated  = time();
    $edit->oldtitle     = $oldpost->title;
    $edit->oldmessage   = $oldpost->message;

    if (!$editid = $DB->insert_record('oublog_edits', $edit)) {
        $tw->rollback();
        return(false);
    }
    //get list of files attached to this post and attach them to the edit.
    $fs = get_file_storage();
    if ($files = $fs->get_area_files($modcontext->id, 'mod_oublog', 'attachment', $post->id, "timemodified", false)) {
        foreach ($files as $file) {
            //add this file to the edit record.
            $fs->create_file_from_storedfile(array(
                'filearea' => 'edit',
                'itemid' => $editid), $file);
        }
    }
    //save new files.
    $post->message = file_save_draft_area_files($post->itemid, $modcontext->id, 'mod_oublog', 'message', $post->id, array('subdirs'=>0), $post->message);
    file_save_draft_area_files($post->attachments, $modcontext->id, 'mod_oublog', 'attachment', $post->id, array('subdirs' => 0));

    // Update tags
    if (!oublog_update_item_tags($post->oubloginstancesid, $post->id, $post->tags,$post->visibility)) {
        $tw->rollback();
        return(false);
    }

    // Update the post
    $post->timeupdated = $edit->timeupdated;
    $post->lasteditedby = $USER->id;

    if (isset($post->groupid)) {
        unset($post->groupid); // Can't change group
    }

    if (!$DB->update_record('oublog_posts', $post)) {
        $tw->rollback();
        return(false);
    }

    if(!oublog_search_update($post,$cm)) {
        $tw->rollback();
        return(false);
    }

    $tw->commit();

    return(true);
}



/**
 * Get all data required to print a list of blog posts as efficiently as possible
 *
 *
 * @param object $oublog
 * @param int $offset
 * @param int $userid
 * @return mixed all data to print a list of blog posts
 */
function oublog_get_posts($oublog, $context, $offset=0, $cm, $groupid, $individualid=-1, $userid=null, $tag='', $canaudit=false) {
    global $CFG, $USER, $DB;
    $params = array();
    $sqlwhere = "bi.oublogid = ?";
    $params[] = $oublog->id;
    $sqljoin = '';

    if (isset($userid)) {
        $sqlwhere .= " AND bi.userid = ? ";
        $params[] = $userid;
    }

    //individual blog
    if ($individualid > -1) {
        $capable = oublog_individual_has_permissions($cm, $oublog, $groupid, $individualid);
        oublog_individual_add_to_sqlwhere($sqlwhere, $params, 'bi.userid', $oublog->id, $groupid, $individualid, $capable);
    }
    //no individual blog
    else {
        if (isset($groupid) && $groupid) {
            $sqlwhere .= " AND p.groupid =  ? ";
            $params[] = $groupid;
        }
    }
    if (!$canaudit) {
        $sqlwhere .= " AND p.deletedby IS NULL ";
    }
    if ($tag) {
        $sqlwhere .= " AND t.tag = ? ";
        $params[] = $tag;
        $sqljoin  .= " INNER JOIN {oublog_taginstances} ti ON p.id = ti.postid
                       INNER JOIN {oublog_tags} t ON ti.tagid = t.id ";
    }

    // Visibility checks.
    if (!isloggedin() || isguestuser()){
        $sqlwhere .= " AND p.visibility =" . OUBLOG_VISIBILITY_PUBLIC;
    } else {
        if ($oublog->global) {
            // Unless the current user has manageposts capability,
            // they cannot view 'private' posts except their own.
            if (!has_capability('mod/oublog:manageposts', context_system::instance())) {
                $sqlwhere .= " AND (p.visibility >" . OUBLOG_VISIBILITY_COURSEUSER .
                        " OR (p.visibility = " . OUBLOG_VISIBILITY_COURSEUSER . " AND u.id = ?))";
                $params[] = $USER->id;
            }
        } else {
            $context = context_module::instance($cm->id);
            if (has_capability('mod/oublog:view', $context)) {
                $sqlwhere .= " AND (p.visibility >= " . OUBLOG_VISIBILITY_COURSEUSER . " )";
            } else {
                $sqlwhere .= " AND p.visibility > " . OUBLOG_VISIBILITY_COURSEUSER;
            }
        }
    }

    // Get posts
    $fieldlist = "p.*, bi.oublogid, u.firstname, u.lastname, bi.userid, u.idnumber, u.picture, u.imagealt, u.email, u.username,
                ud.firstname AS delfirstname, ud.lastname AS dellastname,
                ue.firstname AS edfirstname, ue.lastname AS edlastname";
    $from = "FROM {oublog_posts} p
                INNER JOIN {oublog_instances} bi ON p.oubloginstancesid = bi.id
                INNER JOIN {user} u ON bi.userid = u.id
                LEFT JOIN {user} ud ON p.deletedby = ud.id
                LEFT JOIN {user} ue ON p.lasteditedby = ue.id
                $sqljoin";
    $sql = "SELECT $fieldlist
            $from
            WHERE  $sqlwhere
            ORDER BY p.timeposted DESC
            ";
    $countsql = "SELECT count(p.id) $from WHERE $sqlwhere";

    $rs = $DB->get_recordset_sql($sql, $params, $offset, OUBLOG_POSTS_PER_PAGE);
    if (!$rs->valid()) {
        return(false);
    }
    // Get paging info
    $recordcnt = $DB->count_records_sql($countsql, $params);//$rs->RecordCount();

    $cnt        = 0;
    $posts      = array();
    $postids    = array();

    foreach ($rs as $post) {
        if ($cnt > OUBLOG_POSTS_PER_PAGE) {
            break;
        }
        if (oublog_can_view_post($post, $USER, $context, $oublog->global)) {
            if ($oublog->maxvisibility < $post->visibility) {
                $post->visibility = $oublog->maxvisibility;
            }
            if ($oublog->allowcomments == OUBLOG_COMMENTS_PREVENT) {
                $post->allowcomments = OUBLOG_COMMENTS_PREVENT;
            }

            $posts[$post->id] = $post;
            $postids[] = (int)$post->id;
            $cnt++;
        }
    }
    $rs->close();

    if (empty($posts)) {
        return(true);
    }

    // Get tags for all posts on page
    $sql = "SELECT t.*, ti.postid
            FROM {oublog_taginstances} ti
            INNER JOIN {oublog_tags} t ON ti.tagid = t.id
            WHERE ti.postid IN (".implode(",", $postids).") ";

    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $tag) {
        $posts[$tag->postid]->tags[$tag->id] = $tag->tag;
    }
    $rs->close();

    // Get comments for post on the page
    $sql = "SELECT c.id, c.postid, c.timeposted, c.authorname, c.authorip, c.timeapproved, c.userid, u.firstname, u.lastname, u.picture, u.imagealt, u.email, u.idnumber
            FROM {oublog_comments} c
            LEFT JOIN {user} u ON c.userid = u.id
            WHERE c.postid IN (".implode(",", $postids).") AND c.deletedby IS NULL
            ORDER BY c.timeposted ASC ";

    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $comment) {
        $posts[$comment->postid]->comments[$comment->id] = $comment;
    }
    $rs->close();
    // Get count of comments waiting approval for posts on the page...
    if ($oublog->allowcomments >= OUBLOG_COMMENTS_ALLOWPUBLIC) {
        // Make list of all posts that allow public comments
        $publicallowed = array();
        foreach ($posts as $post) {
            if ($post->allowcomments >= OUBLOG_COMMENTS_ALLOWPUBLIC) {
                $publicallowed[] = (int)$post->id;
            }
        }
        // Only run a db query if there are some posts that allow public
        // comments (so, no performance degradation if feature is not used)
        if (count($publicallowed) > 0) {
            $sql = "SELECT cm.postid, COUNT(1) AS numpending
                    FROM {oublog_comments_moderated} cm
                    WHERE cm.postid IN (".implode(",", $publicallowed).")
                    AND cm.approval = 0
                    GROUP BY cm.postid";
            $rs = $DB->get_recordset_sql($sql);
            foreach ($rs as $postinfo) {
                $posts[$postinfo->postid]->pendingcomments = $postinfo->numpending;
            }
            $rs->close();
        }
    }

    return(array($posts, $recordcnt));
}




/**
 * Get all data required to print a single blog post as efficiently as possible
 *
 *
 * @param int $postid
 * @return mixed all data to print a list of blog posts
 */
function oublog_get_post($postid, $canaudit=false) {
    global $DB;

    // Get post
    $sql = "SELECT p.*, bi.oublogid, u.firstname, u.lastname, u.picture, u.imagealt, bi.userid, u.idnumber, u.email, u.username,
                    ud.firstname AS delfirstname, ud.lastname AS dellastname,
                    ue.firstname AS edfirstname, ue.lastname AS edlastname
            FROM {oublog_posts} p
                INNER JOIN {oublog_instances} bi ON p.oubloginstancesid = bi.id
                INNER JOIN {user} u ON bi.userid = u.id
                LEFT JOIN {user} ud ON p.deletedby = ud.id
                LEFT JOIN {user} ue ON p.lasteditedby = ue.id
            WHERE p.id = ?
            ORDER BY p.timeposted DESC
            ";

    if (!$post = $DB->get_record_sql($sql, array($postid))) {
        return(false);
    }

    // Get tags for all posts on page
    $sql = "SELECT t.*, ti.postid
            FROM {oublog_taginstances} ti
            INNER JOIN {oublog_tags} t ON ti.tagid = t.id
            WHERE ti.postid = ? ";

    $rs = $DB->get_recordset_sql($sql, array($postid));
    foreach ($rs as $tag) {
        $post->tags[$tag->id] = $tag->tag;
    }
    $rs->close();

    // Get comments for post on the page
    if ($post->allowcomments) {
        $sql = "SELECT c.*, u.firstname, u.lastname, u.picture, u.imagealt, u.email, u.idnumber,
                    ud.firstname AS delfirstname, ud.lastname AS dellastname
                FROM {oublog_comments} c
                LEFT JOIN {user} u ON c.userid = u.id
                LEFT JOIN {user} ud ON c.deletedby = ud.id
                WHERE c.postid = ? ";

        if (!$canaudit) {
            $sql .= "AND c.deletedby IS NULL ";
        }

        $sql .= "ORDER BY c.timeposted ASC ";

        $rs = $DB->get_recordset_sql($sql, array($postid));
        foreach ($rs as $comment) {
            $post->comments[$comment->id] = $comment;
        }
        $rs->close();
    }


    // Get edits for this post
    $sql = "SELECT e.id, e.timeupdated, e.oldtitle, e.userid, u.firstname, u.lastname, u.picture, u.imagealt, u.email, u.idnumber
            FROM {oublog_edits} e
            INNER JOIN {user} u ON e.userid = u.id
            WHERE e.postid = ?
            ORDER BY e.timeupdated DESC ";

    $rs = $DB->get_recordset_sql($sql, array($postid));
    foreach ($rs as $edit) {
        $post->edits[$edit->id] = $edit;
    }
    $rs->close();

    return($post);
}


/**
 * Add a blog_instance
 *
 * @param int $oublogid
 * @param int $userid
 * @param string $name
 * @param string $summary
 * @return mixed oubloginstancesid on success or false
 */
function oublog_add_bloginstance($oublogid, $userid, $name='', $summary=null) {
    global $DB;
    $oubloginstance = new stdClass;
    $oubloginstance->oublogid      = $oublogid;
    $oubloginstance->userid        = $userid;
    $oubloginstance->name          = $name;
    $oubloginstance->summary       = $summary;
    $oubloginstance->accesstoken   = md5(uniqid(rand(), true));

    return($DB->insert_record('oublog_instances', $oubloginstance));
}

/**
 * Clarifies a $tags value which may be a string or an array of values,
 * returning an array of strings.
 * @param mixed $tags
 * @return array Array of tag strings
 */
function oublog_clarify_tags($tags) {
    if (is_string($tags)) {
        if (!$tags = explode(',', $tags)) {
            return array();
        }
    } elseif (!is_array($tags)) {
        return array();
    }

    foreach($tags as $idx => $tag) {
        $tag = textlib::strtolower(trim($tag));
        if (empty($tag)) {
            unset($tags[$idx]);
            continue;
        }

        $tags[$idx] = $tag;
    }

    $tags = array_unique($tags);

    return $tags;
}

/**
 * Update a posts tags
 *
 * @param int $oubloginstanceid
 * @param int $postid
 * @param mixed $tags Comma separated string or an array
 * @uses $CFG
 */
function oublog_update_item_tags($oubloginstancesid, $postid, $tags, $postvisibility=OUBLOG_VISIBILITY_COURSEUSER) {
    global $CFG, $DB;

    $tagssql = array();
    $tagids = array();

    // Removed any existing
    $DB->delete_records('oublog_taginstances', array('postid'=>$postid));

    $tags=oublog_clarify_tags($tags);

    if (empty($tags)) {
        return(true);
    }

    // get the id's of the know tags
    list($tagsql, $tagparams) = $DB->get_in_or_equal($tags);
    $sql = "SELECT tag, id FROM {oublog_tags} WHERE tag $tagsql";
    $tagids = $DB->get_records_sql($sql, $tagparams);

    // insert the remainder
    foreach ($tags as $tag) {
        if (!isset($tagids[$tag])) {
            $tagobj = (object) array('tag' => $tag);
            $tagobj->id = $DB->insert_record('oublog_tags', $tagobj);
            $tagids[$tag] = $tagobj;
        }
        $taginstance = new stdClass();
        $taginstance->tagid = $tagids[$tag]->id;
        $taginstance->postid = $postid;
        $taginstance->oubloginstancesid = $oubloginstancesid;

        $DB->insert_record('oublog_taginstances', $taginstance);

    }

    return(true);
}



/**
 * Get post tags in a CSV format
 *
 * @param int $postid
 * @return string
 * @uses $CFG;
 */
function oublog_get_tags_csv($postid) {
    global $DB;

    $sql = "SELECT t.tag
            FROM {oublog_taginstances} ti
            INNER JOIN {oublog_tags} t ON ti.tagid = t.id
            WHERE ti.postid = ? ";

    if ($tags = $DB->get_fieldset_sql($sql, array($postid))) {
        return(implode(', ', $tags));
    } else {
        return('');
    }
}



/**
 * Get weighted tags for a given blog or blog instance
 *
 * @param int $oublogid
 * @param int $oubloginstanceid
 * @return array Tag data
 */
function oublog_get_tags($oublog, $groupid, $cm, $oubloginstanceid=null, $individualid=-1) {
    global $CFG, $DB, $USER;
    $tags = array();
    $params = array();
    $sqlwhere = "bi.oublogid = ? ";
    $params[] = $oublog->id;

    // If individual blog.
    if ($individualid > -1) {
        $capable = oublog_individual_has_permissions($cm, $oublog, $groupid, $individualid);
        oublog_individual_add_to_sqlwhere($sqlwhere, $params, 'bi.userid', $oublog->id, $groupid,
                $individualid, $capable);
    } else {
        // No individual blog.
        if (isset($oubloginstanceid)) {
            $sqlwhere .= "AND ti.oubloginstancesid = ? ";
            $params[] = $oubloginstanceid;
        }
        if (isset($groupid) && $groupid) {
            $sqlwhere .= " AND p.groupid = ? ";
            $params[] = $groupid;
        }
        if (!empty($cm->groupingid)) {
            if ($groups = $DB->get_records('groupings_groups',
                    array('groupingid' => $cm->groupingid), null, 'groupid')) {
                $sqlwhere .= " AND p.groupid IN (" . implode(',', array_keys($groups)) . ") ";
            }
        }
    }
    // Visibility check.
    if (!isloggedin() || isguestuser()) {
        $sqlwhere .= " AND p.visibility = " . OUBLOG_VISIBILITY_PUBLIC;
    } else {
        if ($oublog->global) {
            $sqlwhere .= " AND (p.visibility > " . OUBLOG_VISIBILITY_COURSEUSER .
                    " OR (p.visibility = " . OUBLOG_VISIBILITY_COURSEUSER . " AND u.id = ?))";
            $params[] = $USER->id;
        } else {
            $context = context_module::instance($cm->id);
            if (!has_capability('mod/oublog:view', $context, $USER->id)) {
                $sqlwhere .= " AND (p.visibility > " . OUBLOG_VISIBILITY_COURSEUSER . ")";
            }
        }
     }

    $sql = "SELECT t.id, t.tag, COUNT(ti.id) AS count
            FROM {oublog_instances} bi
                INNER JOIN {oublog_taginstances} ti ON ti.oubloginstancesid = bi.id
                INNER JOIN {oublog_tags} t ON ti.tagid = t.id
                INNER JOIN {oublog_posts} p ON ti.postid = p.id
                INNER JOIN {user} u ON u.id = bi.userid
            WHERE $sqlwhere
            GROUP BY t.id, t.tag
            ORDER BY count DESC";

    if ($tags = $DB->get_records_sql($sql, $params)) {
        $first = array_shift($tags);
        $max = $first->count;
        array_unshift($tags, $first);

        $last = array_pop($tags);
        $min = $last->count;
        array_push($tags, $last);

        $delta = $max-$min+0.00000001;

        foreach ($tags as $idx => $tag) {
            $tags[$idx]->weight = round(($tag->count-$min)/$delta*4);
        }
        sort($tags);
    }
    return($tags);
}



/**
 * Print a tag cloud for a given blog or blog instance
 *
 * @param string $baseurl
 * @param int $oublogid
 * @param int $groupid
 * @param object $cm
 * @param int $oubloginstanceid
 * @return string Tag cloud HTML
 */
function oublog_get_tag_cloud($baseurl, $oublog, $groupid, $cm, $oubloginstanceid=null, $individualid=-1) {
    $cloud = '';
    $urlparts= array();

    $baseurl = oublog_replace_url_param($baseurl, 'tag');

    if (!$tags = oublog_get_tags($oublog, $groupid, $cm, $oubloginstanceid, $individualid)) {
        return($cloud);
    }

    foreach($tags as $tag) {
        $cloud .= '<a href="'.$baseurl.'&amp;tag='.urlencode($tag->tag).'" class="oublog-tag-cloud-'.$tag->weight.'"><span class="oublog-tagname">'.strtr(($tag->tag), array(' '=>'&nbsp;')).'</span><span class="oublog-tagcount">('.$tag->count.')</span></a> ';
    }

    return($cloud);
}



/**
 * Translate a visibility number into a language string
 *
 * @param int $vislevel
 * @param bool $personal True if this is a personal blog
 * @return string
 */
function oublog_get_visibility_string($vislevel,$personal) {

    // Modify visibility string for optional shared activity blog
    global $CFG, $COURSE;
    $visibleusers = 'visiblecourseusers';
    $sharedactvfile = $CFG->dirroot.'/course/format/sharedactv/sharedactv.php';
    if (file_exists($sharedactvfile)) {
        include_once($sharedactvfile);
        if (function_exists('sharedactv_is_magic_course') && sharedactv_is_magic_course($COURSE)) {
            $visibleusers = 'visibleblogusers';
        }
    }

    switch ($vislevel) {
        case OUBLOG_VISIBILITY_COURSEUSER:
            return get_string($personal ? 'visibleyou' : $visibleusers, 'oublog');
        case OUBLOG_VISIBILITY_LOGGEDINUSER:
            return(get_string('visibleloggedinusers', 'oublog'));
        case OUBLOG_VISIBILITY_PUBLIC:
            return(get_string('visiblepublic', 'oublog'));
        default:
            print_error('invalidvisibility','oublog');
    }
}


/**
 * Add a blog comment
 *
 * @param object $comment
 * @return mixed commentid on success or false
 */
function oublog_add_comment($course,$cm,$oublog,$comment) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/completionlib.php');
    if (!isset($comment->timeposted)) {
        $comment->timeposted = time();
    }

    $id=$DB->insert_record('oublog_comments', $comment);
    if($id) {
        // Inform completion system, if available
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && ($oublog->completioncomments)) {
            $completion->update_state($cm, COMPLETION_COMPLETE);
        }
    }
    return $id;
}



/**
 * Update the hit count for a blog and return the current hits
 *
 * @param object $oublog
 * @param object $oubloginstance
 * @return int
 */
function oublog_update_views($oublog, $oubloginstance) {
    global $SESSION, $DB;

    if ($oublog->global && isset($oubloginstance)) {
        if (!isset($SESSION->bloginstanceview[$oubloginstance->id])) {
            $SESSION->bloginstanceview[$oubloginstance->id] = true;
            $oubloginstance->views++;
            $sql = "UPDATE {oublog_instances} SET views = views + 1 WHERE id = ?";
            $DB->execute($sql, array($oubloginstance->id));
        }
        return($oubloginstance->views);
    } else {
        if (!isset($SESSION->blogview[$oublog->id])) {
            $SESSION->blogview[$oublog->id] = true;
            $oublog->views++;
            $sql = "UPDATE {oublog} SET views = views + 1 WHERE id = ?";
            $DB->execute($sql, array($oublog->id));
        }
        return($oublog->views);
    }


}

/**
 * Checks for a permission which you have EITHER if you have the specific
 * permission OR if it's your own personal blog and you have post permission to
 * that blog.
 *
 * @param string $capability
 * @param object $oublog
 * @param object $oubloginstance (required for personal blog access)
 * @param object $context
 * @return bool True if you have permission
 */
function oublog_has_userblog_permission($capability,$oublog,$oubloginstance,$context) {
    // For personal blogs you can do these things EITHER if you have the capability
    // (ie for admins) OR if you are that user and you are allowed to post
    // to blog (not banned)
    global $USER;
    if($oublog->global && $oubloginstance && $USER->id == $oubloginstance->userid &&
        has_capability('mod/oublog:contributepersonal', $context)) {
        return true;
    }
    // Otherwise require the capability (note this also allows eg admin access
    // to personal blogs)
    return has_capability($capability, $context);
}

function oublog_require_userblog_permission($capability,$oublog,$oubloginstance,$context) {
    if(!oublog_has_userblog_permission($capability,$oublog,$oubloginstance,$context)) {
        require_capability($capability,$context);
    }
}

/**
 * Get the list of relevant links in HTML format
 *
 * @param object $oublog
 * @param object $oubloginstance
 * @param object $context
 * @return string HTML on success, false on failure
 */
function oublog_get_links($oublog, $oubloginstance, $context) {
    global $CFG, $DB, $OUTPUT;

    $strmoveup      = get_string('moveup');
    $strmovedown    = get_string('movedown');
    $stredit        = get_string('edit');
    $strdelete      = get_string('delete');

    $canmanagelinks = oublog_has_userblog_permission('mod/oublog:managelinks', $oublog,$oubloginstance,$context);

    if ($oublog->global) {
        $links = $DB->get_records('oublog_links', array('oubloginstancesid'=>$oubloginstance->id), 'sortorder');
    } else {
        $links = $DB->get_records('oublog_links', array('oublogid'=>$oublog->id), 'sortorder');
    }
    $html = '';

    if ($links) {

        $html .= '<ul class="unlist">';
        $numlinks = count($links);
        $i=0;
        foreach ($links as $link) {
            $i++;
            $html .= '<li>';
            $html .= '<a href="'.htmlentities($link->url).'">'.format_string($link->title).'</a> ';

            if ($canmanagelinks) {
                if ($i > 1) {
                    $html .= '<form action="movelink.php" method="post" style="display:inline" title="'.$strmoveup.'">';
                    $html .= '<input type="image" src="'.$OUTPUT->pix_url('t/up').'" alt="'.$strmoveup.'" />';
                    $html .= '<input type="hidden" name="down" value="0" />';
                    $html .= '<input type="hidden" name="link" value="'.$link->id.'" />';
                    $html .= '<input type="hidden" name="returnurl" value="'.$_SERVER['REQUEST_URI'].'" />';
                    $html .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
                    $html .= '</form>';
                }
                if ($i < $numlinks) {
                    $html .= '<form action="movelink.php" method="post" style="display:inline" title="'.$strmovedown.'">';
                    $html .= '<input type="image" src="'.$OUTPUT->pix_url('t/down').'" alt="'.$strmovedown.'" />';
                    $html .= '<input type="hidden" name="down" value="1" />';
                    $html .= '<input type="hidden" name="link" value="'.$link->id.'" />';
                    $html .= '<input type="hidden" name="returnurl" value="'.$_SERVER['REQUEST_URI'].'" />';
                    $html .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
                    $html .= '</form>';
                }
                $html .= '<a href="editlink.php?blog='.$oublog->id.'&amp;link='.$link->id.'" title="'.$stredit.'"><img src="'.$OUTPUT->pix_url('t/edit').'" alt="'.$stredit.'" class="iconsmall" /></a>';
                $html .= '<a href="deletelink.php?blog='.$oublog->id.'&amp;link='.$link->id.'" title="'.$strdelete.'"><img src="'.$OUTPUT->pix_url('t/delete').'" alt="'.$strdelete.'" class="iconsmall" /></a>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    if ($canmanagelinks) {
        $html .= '<br />';
        if ($oublog->global) {
            $html .= '<a href="editlink.php?blog='.$oublog->id.'&amp;bloginstance='.$oubloginstance->id.'" class="oublog-links">'.get_string('addlink', 'oublog').'</a>';
        } else {
            $html .= '<a href="editlink.php?blog='.$oublog->id.'"  class="oublog-links">'.get_string('addlink', 'oublog').'</a>';
        }
    }

    return($html);

}



/**
 * Insert a link into the DB
 *
 * @param object $link
 * @return bool true on success, false on faulure
 */
function oublog_add_link($link) {
    global $DB;

    // $link->oubloginstancesid is only set for personal blogs
    if (isset($link->oubloginstanceid)) {
        $sql = "SELECT MAX(sortorder) AS sortorder FROM {oublog_links} WHERE oubloginstancesid = ? ";
        $sortorder = $DB->get_field_sql($sql, array($link->oubloginstancesid));
        $sortorder++;
    } else {
        $sql = "SELECT MAX(sortorder) AS sortorder FROM {oublog_links} WHERE oublogid = ?";
        $sortorder = $DB->get_field_sql($sql, array($link->oublogid));
        $sortorder++;
    }

    $link->sortorder = $sortorder;
    if (!$DB->insert_record('oublog_links', $link)) {
        return(false);
    }

    return(true);
}



/**
 * Update a link in the DB
 *
 * @param object $link
 * @return bool true on success, false on faulure
 */
function oublog_edit_link($link) {
    global $DB;
    unset($link->sortorder);

    return($DB->update_record('oublog_links', $link));
}



/**
 * Delete a link from the DB
 *
 * @param object $oublog
 * @param object $link
 * @return bool true on success, false on faulure
 */
function oublog_delete_link($oublog, $link) {
    global $DB;
    $params = array();
    if ($oublog->global) {
        $where = "oubloginstancesid = ? ";
        $params[] = $link->oubloginstancesid;
    } else {
        $where = "oublogid = ? ";
        $params[] = $link->oublogid;
    }

    if (!$DB->delete_records('oublog_links', array('id'=>$link->id))) {
        return(false);
    }

    $sql = "UPDATE {oublog_links}
            SET sortorder = sortorder - 1
            WHERE $where AND sortorder > ?";
    $params[] = $link->sortorder;
    return($DB->execute($sql, $params));
}



/**
 * Return a timestamp of when a blog, or comment was last updated
 *
 * @param int $blogid
 * @param int $bloginstancesid
 * @param int $postid
 * @param bool $comments
 * @return int last modified timestamp
 */
function oublog_feed_last_changed($blogid, $bloginstancesid, $postid, $comments) {
    global $DB;
    $params = array();

    // Comments or posts?
    if ($comments) {
        $sql = "SELECT MAX(c.timeposted) AS timeposted
                FROM {oublog_comments} c ";
        if ($postid) {
            $sqljoin = '';
            $sqlwhere = "WHERE p.postid = ? ";
            $params[] = $postid;
        } elseif ($bloginstancesid) {
            $sqljoin  = "INNER JOIN {oublog_posts} p ON c.postid = p.id ";
            $sqlwhere = "WHERE p.oubloginstancesid = ? ";
            $params[] = $bloginstancesid;
        } else {
            $sqljoin  = "INNER JOIN {oublog_posts} p ON c.postid = p.id
                         INNER JOIN {oublog_instances} i ON p.oubloginstancesid = i.id ";
            $sqlwhere = "WHERE i.oublogid = ? ";
            $params[] = $blogid;
        }

    } else {
        $sql = "SELECT MAX(p.timeposted) AS timeposted
                FROM {oublog_posts} p ";

        if ($bloginstancesid) {
            $sqljoin  = '';
            $sqlwhere = "WHERE p.oubloginstancesid = ? ";
            $params[] = $bloginstancesid;
        } else {
            $sqljoin  = "INNER JOIN {oublog_instances} i ON p.oubloginstancesid = i.id ";
            $sqlwhere = "WHERE i.oublogid = ? ";
            $params[] = $blogid;
        }
    }

    return($DB->get_field_sql($sql.$sqljoin.$sqlwhere, $params));
}



/**
 * Get blog comments in a format compatable with RSS lib
 *
 * @param int $blogid
 * @param int $bloginstancesid
 * @param int $postid
 * @param object $user
 * @param int $allowedvisibility
 * @param int $groupid
 * @param object $cm
 * @return array
 */
function oublog_get_feed_comments($blogid, $bloginstancesid, $postid, $user, $allowedvisibility, $groupid, $cm) {
    global $CFG, $DB;
    $params = array();
    $items = array();

    if ($postid) {
        $sqlwhere = "AND p.id = ? ";
        $params[] = $postid;
    } elseif ($bloginstancesid) {
        $sqlwhere = "AND p.oubloginstancesid = ? ";
        $params[] = $bloginstancesid;
    } else {
        $sqlwhere = "AND i.oublogid = ? ";
        $params[] = $blogid;
    }

    if (isset($groupid) && $groupid) {
        $sqlwhere .= " AND p.groupid = ? ";
        $params[] = $groupid;
    }
    if (!empty($cm->groupingid)) {
        if ($groups = $DB->get_records('groupings_groups',
                array('groupingid'=>$cm->groupingid), null, 'groupid')) {
            $sqlwhere .= " AND p.groupid ";
            list ($grpssql, $grpsparams) = $DB->get_in_or_equal(array_keys($groups));
            $params = array_merge($params, $grpsparams);
            $sqlwhere .= $grpssql;
        }
    }

    $sql = "SELECT p.title AS posttitle, p.message AS postmessage, c.id, c.postid, c.title, c.message AS description, c.timeposted AS pubdate, c.authorname, c.authorip, c.timeapproved, i.userid, u.firstname, u.lastname, u.picture, u.imagealt, u.email, u.idnumber
            FROM {oublog_comments} c
            INNER JOIN {oublog_posts} p ON c.postid = p.id
            INNER JOIN {oublog_instances} i ON p.oubloginstancesid = i.id
            LEFT JOIN {user} u ON c.userid = u.id
            WHERE c.deletedby IS NULL AND p.deletedby IS NULL
            AND p.visibility >= $allowedvisibility $sqlwhere
            ORDER BY GREATEST(c.timeapproved, c.timeposted) DESC ";

    $rs = $DB->get_recordset_sql($sql, $params, 0, OUBLOG_MAX_FEED_ITEMS);

    foreach ($rs as $item) {
        $item->link = $CFG->wwwroot.'/mod/oublog/viewpost.php?post='.$item->postid;

        if ($item->title) {
            $item->description = "<h3>" . s($item->title) . "</h3>"
                    . $item->description;
        }

        //add post title if there, otherwise add shorten post message instead
        if ($item->posttitle) {
            $linktopost = get_string('re', 'oublog', $item->posttitle);
        } else {
            $linktopost = get_string('re', 'oublog', html_to_text(shorten_text($item->postmessage)));
        }
        $item->title = $linktopost;
        $item->author = fullname($item);

        // For moderated posts, use different data
        if ($item->authorname) {
            // Author name from name instead of user field
            $item->authorname = $item->author;

            // Keep posted time just in case
            $item->timeposted = $item->pubdate;

            // Publication date = approval time (should be consistent with
            // expected feed behaviour)
            $item->pubdate = $item->timeapproved;
        }

        $items[] = $item;
    }
    $rs->close();
    return($items);
}



/**
 * Get post in a format compatable with RSS lib
 *
 * @param int $blogid
 * @param int $bloginstancesid
 * @param object $user
 * @param bool $allowedvisibility
 * @param int $groupid
 * @param object $cm
 * @return array
 */
function oublog_get_feed_posts($blogid, $bloginstance, $user, $allowedvisibility, $groupid, $cm, $oublog, $individualid=-1) {
    global $CFG, $DB;
    $params = array();
    $items = array();

    if ($bloginstance) {
        $sqlwhere = "AND p.oubloginstancesid = ? ";
        $params[] = $bloginstance->id;
    } else {
        $sqlwhere = "AND i.oublogid = ? ";
        $params[] = $blogid;
    }
    //if individual blog
    if ($individualid > -1) {
        $capable = oublog_individual_has_permissions($cm, $oublog, $groupid, $individualid);
        oublog_individual_add_to_sqlwhere($sqlwhere, $params, 'i.userid', $oublog->id, $groupid, $individualid, $capable);
    }
    //no individual blog
    else {
        if ($groupid) {
            $sqlwhere .= " AND p.groupid = ? ";
            $params[] = $groupid;
        }
        if (!empty($cm->groupingid)) {
            if ($groups = $DB->get_records('groupings_groups', array('groupingid'=>$cm->groupingid), null, 'groupid')) {
                $sqlwhere .= "AND p.groupid IN (".implode(',', array_keys($groups)).") ";
            }
        }
    }
    // Scheme URL for tags
    $scheme = $CFG->wwwroot . '/mod/oublog/';
    if ($oublog->global) {
        if (!$bloginstance) {
            $scheme .= 'allposts.php?tag=';
        } else {
            $scheme .= 'view.php?user=' . $bloginstance->userid . '&tag=';
        }
    } else {
        $scheme .= 'view.php?id=' . $cm->id;
        if($groupid) {
            $scheme .= '&group=' . $groupid;
        }
        $scheme .= '&tag=';
    }

    // Get posts
    $sql = "SELECT p.id, p.title, p.message AS description, p.timeposted AS pubdate, i.userid, u.firstname, u.lastname, u.email, u.picture, u.imagealt, u.idnumber
            FROM {oublog_posts} p
            INNER JOIN {oublog_instances} i ON p.oubloginstancesid = i.id
            INNER JOIN {user} u ON i.userid = u.id
            WHERE p.deletedby IS NULL AND p.visibility >= $allowedvisibility $sqlwhere
            ORDER BY p.timeposted DESC ";

    $rs = $DB->get_recordset_sql($sql, $params, 0, OUBLOG_MAX_FEED_ITEMS);
    foreach($rs as $item) {
        $item->link = $CFG->wwwroot.'/mod/oublog/viewpost.php?post='.$item->id;
        $item->author = fullname($item);
        $item->tags = array();
        $item->tagscheme = $scheme;
        // Feeds do not allow blank titles
        if ((string)$item->title === '') {
            $item->title = html_to_text(shorten_text($item->description));
        }
        $items[$item->id] = $item;
    }
    $rs->close();

    // Get all tags related to these posts and fill them in
    $sql = "SELECT p.id AS postid, t.id AS tagid, t.tag
            FROM {oublog_posts} p
            INNER JOIN {oublog_instances} i ON p.oubloginstancesid = i.id
            INNER JOIN {oublog_taginstances} ti ON p.id = ti.postid
            INNER JOIN {oublog_tags} t ON ti.tagid = t.id
            WHERE p.deletedby IS NULL AND p.visibility >= $allowedvisibility $sqlwhere";

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $tag) {
        if(array_key_exists($tag->postid, $items)) {
            $items[$tag->postid]->tags[$tag->tagid] = $tag->tag;
        }
    }
    $rs->close();
    return($items);
}



/**
 * Get a url to a feed
 *
 * @param string $format atom or rss
 * @param object $oublog
 * @param object $bloginstance
 * @param int $groupid
 * @param bool $comments
 * @param int $postid
 * @param unknown_type $context
 * @return string
 * @uses $CFG
 * @uses $USER
 */
function oublog_get_feedurl($format, $oublog, $bloginstance, $groupid, $comments, $postid, $cm, $individualid=0) {
    global $CFG, $USER;
    $url  = $CFG->wwwroot.'/mod/oublog/feed.php';
    $url .= '?format='.$format;
    $url .= '&amp;blog='.$oublog->id;
    if ($oublog->global) {
        if ((is_null($bloginstance) || is_string($bloginstance) && $bloginstance=='all')) {
            $url .= '&amp;bloginstance=all';
            $accesstoken = $oublog->accesstoken;
        } else {
            $url .= '&amp;bloginstance='.$bloginstance->id;
            $accesstoken = $bloginstance->accesstoken;
        }
    } else {
        $accesstoken = $oublog->accesstoken;
    }

    if ($groupid) {
        $url .= '&amp;group='.$groupid;
    }
    //if individual blog
    if ($individualid > 0) {
        $url .= '&amp;individual='.$individualid;
    }

    $url .= '&amp;comments='.$comments;

    // Visibility level
    if (!isloggedin() || isguestuser()) {
        // pub
    } else {
        $url .= '&amp;viewer='.$USER->id;
        // Don't use the 'full' token in personal blogs. We don't need personal
        // blog feeds to include draft posts, even for the user (who's the only
        // one allowed to see them) and it generates potential confusion.
        if (!$oublog->global && oublog_can_post($oublog, 0, $cm)) {
            // Full token changed to v2 after a security issue
            $url .= '&amp;full='.md5($accesstoken.$USER->id.OUBLOG_VISIBILITY_COURSEUSER . 'v2');
        } else {
            $url .= '&amp;loggedin='.md5($accesstoken.$USER->id.OUBLOG_VISIBILITY_LOGGEDINUSER);
        }
    }

    return($url);
}



/**
 * Get a block containing links to the Atom and RSS feeds
 *
 * @param object $oublog
 * @param object $bloginstance
 * @param int $groupid
 * @param int $postid
 * @param object $context
 * @return string HTML of block
 * @uses $CFG
 */
function oublog_get_feedblock($oublog, $bloginstance, $groupid, $postid, $cm, $individualid=-1) {
    global $CFG, $OUTPUT;

    if (!$CFG->enablerssfeeds) {
        return(false);
    }

    $blogurlatom = oublog_get_feedurl('atom',  $oublog, $bloginstance, $groupid, false, false, $cm, $individualid);
    $blogurlrss = oublog_get_feedurl('rss',  $oublog, $bloginstance, $groupid, false, false, $cm, $individualid);

    if (!is_string($bloginstance)) {
        $commentsurlatom = oublog_get_feedurl('atom',  $oublog, $bloginstance, $groupid, true, $postid, $cm, $individualid);
        $commentsurlrss = oublog_get_feedurl('rss',  $oublog, $bloginstance, $groupid, true, $postid, $cm, $individualid);
    }

    $html  = get_string('subscribefeed', 'oublog');
    $html .= $OUTPUT->help_icon('feedhelp', 'oublog');
    $html .= '<br />';//<br /><img src="'.$OUTPUT->pix_url('i/rss').'" alt="'.get_string('blogfeed', 'oublog').'"  class="feedicon" />';
    $html .= get_string('blogfeed', 'oublog').': ';
    $html .= '<a href="'.$blogurlatom.'">'.get_string('atom', 'oublog').'</a> ';
    $html .= '<a href="'.$blogurlrss.'">'.get_string('rss', 'oublog').'</a>';

    if($oublog->allowcomments) {
        if (!is_string($bloginstance)) {
            $html .= '<div class="oublog-links">'.get_string('commentsfeed', 'oublog').': ';
            $html .= '<br/><a href="'.$commentsurlatom.'">'.get_string('comments','oublog').' '.get_string('atom', 'oublog').'</a> ';
            $html .= '<br/><a href="'.$commentsurlrss.'">'.get_string('comments','oublog').' '.get_string('rss', 'oublog').'</a>';
            $html .= '</div>';
        }
    }
    return ($html);
}



/**
 * Get extra meta tags that need to go into the page header
 *
 * @param object $oublog
 * @param object $bloginstance
 * @param int $groupid
 * @param object $context
 * @return string
 */
function oublog_get_meta_tags($oublog, $bloginstance, $groupid, $cm) {
    global $CFG;

    $meta = '';
    $blogurlatom = oublog_get_feedurl('atom',  $oublog, $bloginstance, $groupid, false, false, $cm);
    $blogurlrss = oublog_get_feedurl('rss',  $oublog, $bloginstance, $groupid, false, false, $cm);

    if ($CFG->enablerssfeeds) {
        $meta .= '<link rel="alternate" type="application/atom+xml" title="'.get_string('atomfeed', 'oublog').'" href="'.$blogurlatom .'" />';
        $meta .= '<link rel="alternate" type="application/atom+xml" title="'.get_string('rssfeed', 'oublog').'" href="'.$blogurlrss .'" />';
    }

    return ($meta);
}



/**
 * replace a variable withing a querystring
 *
 * @param string $url
 * @param string $replacekey
 * @param string $newvalue
 * @return string
 */
function oublog_replace_url_param($url, $replacekey, $newvalue=null) {

    $urlparts = parse_url(html_entity_decode($url));

    $queryparts = array();

    parse_str($urlparts['query'], $queryparts);

    unset($queryparts[$replacekey]);

    if ($newvalue) {
        $queryparts[$replacekey] = $newvalue;
    }

    foreach($queryparts as $key => $value) {
        $queryparts[$key] = "$key=$value";
    }
    $url = $urlparts['path'].'?'.implode('&amp;', $queryparts);

    return($url);
}

/** @return True if OU search extension is installed */
function oublog_search_installed() {
    return @include_once(dirname(__FILE__).'/../../local/ousearch/searchlib.php');
}

/**
 * Obtains a search document relating to a particular blog post.
 *
 * @param object $post Post object. Required fields: id (optionally also
 *   groupid, userid save a db query)
 * @param object $cm Course-module object. Required fields: id, course
 * @return ousearch_doument
 */
function oublog_get_search_document($post,$cm) {
    global $DB;
    // Set up 'search document' to refer to this post
    $doc=new local_ousearch_document();
    $doc->init_module_instance('oublog',$cm);
    if(!isset($post->userid) || !isset($post->groupid)) {
        $results=$DB->get_record_sql("
SELECT
    p.groupid,i.userid
FROM
{oublog_posts} p
    INNER JOIN {oublog_instances} i ON p.oubloginstancesid=i.id
WHERE
    p.id= ?", array($post->id));
        if(!$results) {
            print_error('invalidblogdetails','oublog');
        }
        $post->userid=$results->userid;
        $post->groupid=$results->groupid;
    }
    if($post->groupid) {
        $doc->set_group_id($post->groupid);
    }
    $doc->set_user_id($post->userid);
    $doc->set_int_refs($post->id);
    return $doc;
}

/**
 * Obtains tags for a $post object whether or not it currently has them
 * defined in some way. (If they're not defined, uses a database query.)
 *
 * @param object $post Post object, must contain ->id at least
 * @param bool $includespaces If true, replaces the _ with space again
 * @return array Array of tags (may be empty)
 */
function oublog_get_post_tags($post,$includespaces=false) {
    global $CFG, $DB;

    // Work out tags from existing data if possible (to save adding a query)
    if(isset($post->tags)) {
        $taglist=oublog_clarify_tags($post->tags);
    } else {
        // Tags aren't in post so use database query
        $rs=$DB->get_recordset_sql("
SELECT
    t.tag
FROM
    {oublog_taginstances} ti
    INNER JOIN {oublog_tags} t ON ti.tagid = t.id
WHERE
    ti.postid=?", array($post->id));
        $taglist=array();
        foreach($rs as $rec) {
            $taglist[]=$rec->tag;
        }
        $rs->close();
    }
    if($includespaces) {
        foreach($taglist as $ix=>$tag) {
            // Make the spaces in tags back into spaces so they're searchable (sigh)
            $taglist[$ix]=str_replace('_',' ',$tag);
        }
    }

    return $taglist;
}

/**
 * Updates the fulltext search information for a post which is being added or
 * updated.
 * @param object $post Post data, including slashes for database. Must have
 *   fields id,userid,groupid (if applicable), title, message
 * @param object $cm Course-module
 * @return True if search update was successful
 */
function oublog_search_update($post,$cm) {
    // Do nothing if OU search is not installed
    if (!oublog_search_installed()) {
        return true;
    }

    // Get search document
    $doc=oublog_get_search_document($post,$cm);

    // Sort out tags for use as extrastrings
    $taglist=oublog_get_post_tags($post,true);
    if(count($taglist)==0) {
        $taglist=null;
    }

    // Update information about this post (works ok for add or edit)
    $doc->update($post->title,$post->message,null,null,$taglist);
    return true;
}

function oublog_date($time,$insentence=false) {
    if(function_exists('specially_shrunken_date')) {
        return specially_shrunken_date($time,$insentence);
    } else {
        return userdate($time);
    }
}

/**
 * Creates navigation for a blog page header.
 * @param object $cm Moodle course-modules object
 * @param object $oublog Row object from 'oublog' table
 * @param object $oubloginstance Row object from 'oubloginstance' table
 * @param object $oubloguser Moodle user object
 * @param array $extranav Optional additional navigation entry; may be an array
 *   of nav entries, a single nav entry (array with
 *   'name', optional 'link', and 'type' fields), or null for none
 * @return object Navigation item object
 */
function oublog_build_navigation($oublog, $oubloginstance, $oubloguser) {
    global $PAGE;
    if ($oublog->global && !empty($oubloguser)) {
        $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id'=>$oubloguser->id)));
        $PAGE->navbar->add(format_string($oubloginstance->name), new moodle_url('/mod/oublog/view.php', array('user'=>$oubloguser->id)));
    }
}

///////////////////////////////////
//constants for individual blogs
define('OUBLOG_NO_INDIVIDUAL_BLOGS', 0);
define('OUBLOG_SEPARATE_INDIVIDUAL_BLOGS', 1);
define('OUBLOG_VISIBLE_INDIVIDUAL_BLOGS', 2);

/**
 * Get an object with details of individual activity
 * @param $cm
 * @param $urlroot
 * @param $oublog
 * @param $currentgroup
 * @param $context
 * @return an object
 */
function oublog_individual_get_activity_details($cm, $urlroot, $oublog, $currentgroup, $context) {
    global $CFG, $USER, $SESSION, $OUTPUT;
    if (strpos($urlroot, 'http') !== 0) { // Will also work for https
        debugging('oublog_print_individual_activity_menu requires absolute URL for ' .
            '$urlroot, not <tt>' . s($urlroot) . '</tt>. Example: ' .
            'oublog_print_individual_activity_menu($cm, $CFG->wwwroot . \'/mod/mymodule/view.php?id=13\');',
        DEBUG_DEVELOPER);
    }
    // get groupmode and individualmode
    $groupmode = oublog_get_activity_groupmode($cm);
    $individualmode = $oublog->individual;

    //no individual blogs
    if ($individualmode == OUBLOG_NO_INDIVIDUAL_BLOGS) {
        return '';
    }
    //if no groups or 'all groups' selection' ($currentgroup == 0)
    if ($groupmode == NOGROUPS || $currentgroup == 0) {
        //visible individual
        if ($individualmode == OUBLOG_VISIBLE_INDIVIDUAL_BLOGS) {
            $allowedindividuals = oublog_individual_get_all_users($cm->course, $cm->instance);
        }
        //separate individual (check capability of present user
        if ($individualmode == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS) {
            $allowedindividuals = oublog_individual_get_all_users($cm->course, $cm->instance, 0, $context);
        }
    }

    //if a group is selected ($currentgroup > 0)
    if ($currentgroup > 0) {
        //visible individual
        if ($individualmode == OUBLOG_VISIBLE_INDIVIDUAL_BLOGS) {
            $allowedindividuals = oublog_individual_get_all_users($cm->course, $cm->instance, $currentgroup );
        }
        //separate individual
        if ($individualmode == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS) {
            $allowedindividuals = oublog_individual_get_all_users($cm->course, $cm->instance, $currentgroup, $context);
        }
    }
    $activeindividual = oublog_individual_get_active_user($allowedindividuals, $individualmode, true);

    //setup the drop-down menu
    $menu = array();
    if (count($allowedindividuals) > 1) {
        if ($currentgroup > 0) {//selected group
            $menu[0] = get_string('viewallusersingroup', 'oublog');
        } else {//no groups or all groups
            $menu[0] = get_string('viewallusers', 'oublog');
        }
    }

    if ($allowedindividuals) {
        foreach ($allowedindividuals as $user) {
            $menu[$user->id] = format_string($user->firstname . '&nbsp;' . $user->lastname);
        }
    }

    if ($individualmode == OUBLOG_VISIBLE_INDIVIDUAL_BLOGS) {
        $label = get_string('visibleindividual', 'oublog') . '&nbsp;';
    } else {
        $label = get_string('separateindividual', 'oublog') . '&nbsp;';
    }

    $output = "";

    if (count($menu) == 1) {
        $name = reset($menu);
        $output = $label.':&nbsp;'.$name;
    } else {
        foreach ($menu as $value=>$item) {
            $url = $urlroot.'&amp;individual='.$value;
            $url = str_replace($CFG->wwwroot, '', $url);
            $url = str_replace('&amp;', '&', $url);
            $urls[$url] = $item;
            if ($activeindividual == $value) {
                $active = $url;
            }
        }
        if (!empty($urls)) {
            $select = new url_select($urls, $active, null, 'selectindividual');

            $select->set_label($label);

            $output = $OUTPUT->render($select);
        }
    }

    $output = '<div class="oublog-individualselector">'.$output.'</div>';
    //set up the object details needed
    $individualdetails = new stdClass;
    $individualdetails->display = $output;
    $individualdetails->activeindividual = $activeindividual;
    $individualdetails->mode = $individualmode;
    $individualdetails->userids = array_keys($allowedindividuals);
    $individualdetails->newblogpost = true;

    //hid the "New blog post" button
    if ((($activeindividual == 0) && !array_key_exists($USER->id, $allowedindividuals))
        ||($activeindividual > 0 && $activeindividual != $USER->id)) {
        $individualdetails->newblogpost = false;
    }
    return $individualdetails;
}


function oublog_individual_get_all_users($courseid, $oublogid, $currentgroup=0, $context=0) {
    global $CFG, $USER;
    //add present user to the list
    $currentuser = array();
    $user = new stdClass;
    $user->firstname = $USER->firstname;
    $user->lastname = $USER->lastname;
    $user->id = $USER->id;
    $currentuser[$USER->id] = $user;
    if ($context && !has_capability('mod/oublog:viewindividual', $context)) {
        return $currentuser;
    }
    //no groups or all groups selected
    if ($currentgroup == 0) {
        $userswhoposted = oublog_individual_get_users_who_posted_to_this_blog($oublogid);
    } elseif ($currentgroup > 0) {//a group is selected
        //users who posted to the blog and belong to the selected group
        $userswhoposted = oublog_individual_get_users_who_posted_to_this_blog($oublogid, $currentgroup);
    }

    if (!$userswhoposted) {
        $userswhoposted = array();
    }
    $keys = array_keys($userswhoposted);
    if (in_array($USER->id, $keys)) {
        return $userswhoposted;
    } else {
        if ($currentgroup == 0 || groups_is_member($currentgroup, $USER->id)) {
            return $currentuser + $userswhoposted;
        } else {
            return $userswhoposted;
        }
    }
}


function oublog_individual_get_users_who_posted_to_this_blog($oublogid, $currentgroup=0) {
    global $DB;
    $params = array();
    if ($currentgroup > 0) {
        $sql = "SELECT u.id, u.firstname, u.lastname
                FROM {user} u
                INNER JOIN {oublog_instances} bi
                ON bi.oublogid = ? AND bi.userid = u.id
                INNER JOIN {groups_members} gm
                ON bi.userid = gm.userid
                WHERE gm.groupid = ?
                ORDER BY u.firstname ASC, u.lastname ASC";
        $params[] = $oublogid;
        $params[] = $currentgroup;
    } else {
        $sql = "SELECT u.id, u.firstname, u.lastname
            FROM {user} u
            JOIN {oublog_instances} bi
            ON bi.oublogid = $oublogid AND bi.userid = u.id
            ORDER BY u.firstname ASC, u.lastname ASC";
    }
    if ($userswhoposted = $DB->get_records_sql($sql, $params)) {
        return $userswhoposted;
    }
    return array();
}


function oublog_individual_get_active_user($allowedindividuals, $individualmode, $update=false) {
    global $USER, $SESSION;

    //only one userid in the list (this could be $USER->id or any other userid)
    if (count($allowedindividuals) == 1) {
        $chosenindividual =  array_keys($allowedindividuals);
        return $chosenindividual[0];
    }
    if (!property_exists($SESSION, 'oublog_individualid')) {
        $SESSION->oublog_individualid = '';
    }

    // set new active individual if requested
    $changeindividual = optional_param('individual', -1, PARAM_INT);

    if ($update && $changeindividual != -1) {
        $SESSION->oublog_individualid = $changeindividual;
    } elseif (isset($SESSION->oublog_individualid)) {
        return $SESSION->oublog_individualid;
    } else {
        $SESSION->oublog_individualid = $USER->id;
    }
    return $SESSION->oublog_individualid;
}


function oublog_individual_has_permissions($cm, $oublog, $groupid, $individualid=-1, $userid=0){
    global $USER;
    if (!$userid) {
        $userid = $USER->id;
    }

    //chosen an individual user who is the logged-in user
    if ($individualid > 0 && $userid == $individualid) {
        return true;
    }

    //get context
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    //no individual blogs
    $individualmode = $oublog->individual;
    if ($individualmode == OUBLOG_NO_INDIVIDUAL_BLOGS) {
        return true;
    }

    $groupmode = oublog_get_activity_groupmode($cm);

    //separate individual
    if ($individualmode == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS) {
        if (!has_capability('mod/oublog:viewindividual', $context, $userid)) {
            return false;
        }

        //No group
        if ($groupmode == NOGROUPS) {
            return true;
        }

        //chosen a group
        if ($groupid > 0) {
            //visible group
            if ($groupmode == VISIBLEGROUPS) {
                return true;
            }
            //has access to all groups
            if (has_capability('moodle/site:accessallgroups', $context, $userid)) {
                return true;
            }
            //same group
            if (groups_is_member($groupid, $userid) &&
                groups_is_member($groupid, $individualid)) {
                return true;
            }
            return false;
        } else {
            if (has_capability('moodle/site:accessallgroups', $context, $userid)) {
                return true;
            }
        }

        return false;
    }
    //visible individual
    elseif ($individualmode == OUBLOG_VISIBLE_INDIVIDUAL_BLOGS) {
        //No group
        if ($groupmode == NOGROUPS) {
            return true;
        }
        //visible group
        if ($groupmode == VISIBLEGROUPS) {
            return true;
        }
        //separate groups
        if ($groupmode == SEPARATEGROUPS) {
            //have accessallgroups
            if (has_capability('moodle/site:accessallgroups', $context, $userid)) {
                return true;
            }
            // If they don't have accessallgroups then they must select a group
            if (!$groupid) {
                return false;
            }
            //chosen individual
            if ($individualid > 0) {
                //same group
                if (groups_is_member($groupid, $userid) &&
                    groups_is_member($groupid, $individualid)) {
                    return true;
                }
                return false;
            } else {
                //chosen all users in the group
                if (groups_is_member($groupid, $userid)) {
                    return true;
                }
                return false;
            }
        }
        return false;
    }
    return false;
}


function oublog_individual_add_to_sqlwhere(&$sqlwhere, &$params, $userfield, $oublogid, $groupid=0, $individualid=0, $capable=true) {
    //has not capability
    if (!$capable) {
        return;
    }

    //only one user is chosen
    if ($individualid > 0) {
        $sqlwhere .= " AND $userfield = ? ";
        $params[] = $individualid;
        return;
    }

    //a list of user is chosen
    $from = " FROM {oublog_instances} bi ";
    $where = " WHERE bi.oublogid=$oublogid ";

    //individuals within a group
    if (isset($groupid) && $groupid > 0) {
        $from .= " INNER JOIN {groups_members} gm
                    ON bi.userid = gm.userid";
        $where .= " AND gm.groupid= ? ";
        $params[] = $groupid;
        $where .= " AND bi.userid=gm.userid ";
    }
    $subsql =  "SELECT bi.userid $from $where";
    $sqlwhere .= " AND $userfield IN ($subsql)";
}

/**
 * Get last-modified time for blog, as it appears to this user. This takes into
 * account the user's groups/individual settings if required. Only works on
 * course blogs. (Does not check that user can view the blog.)
 * @param object $cm Course-modules entry for wiki
 * @param object $Course Course object
 * @param int $userid User ID or 0 = current
 * @return int Last-modified time for this user as seconds since epoch
 */
function oublog_get_last_modified($cm, $course, $userid=0) {
    global $USER, $DB;
    if (!$userid) {
        $userid = $USER->id;
    }

    // Get blog record and groupmode
    if (!($oublog = $DB->get_record('oublog', array('id'=>$cm->instance)))) {
        return false;
    }
    $groupmode = oublog_get_activity_groupmode($cm, $course);

    // Default applies no restriction
    $restrictjoin = '';
    $restrictwhere = '';
    $rwparam = array();
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    // Restrict to separate groups
    if ($groupmode == SEPARATEGROUPS &&
        !has_capability('moodle/site:accessallgroups', $context, $userid)) {

        if ($oublog->individual) {
            // In individual mode, group restriction works by shared grouping
            $restrictjoin .= "
INNER JOIN {groups_members} gm2 ON gm2.userid = bi.userid
INNER JOIN {groups} g ON g.id = gm2.groupid";
            $groupfield = "g.id";
            $restrictwhere .= "
AND g.courseid = ?";
            $rwparam[] = $course->id;
        } else {
            // Outside individual mode, group restriction works based on groupid
            // in post.
            $groupfield = "p.groupid";
        }
        $restrictjoin .= "
INNER JOIN {groups_members} gm ON gm.groupid = $groupfield";
        $restrictwhere .= "
AND gm.userid = ?";
        $rwparam[] = $userid;

        if ($cm->groupingid) {
            $restrictjoin .= "
INNER JOIN {groupings_groups} gg ON gg.groupid = $groupfield";
            $restrictwhere .= "
AND gg.groupingid = ?";
            $rwparam[] = $cm->groupingid;
        }
    }

    // Restrict to separate individuals
    if ($oublog->individual == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS &&
        !has_capability('mod/oublog:viewindividual', $context, $userid)) {
        // Um, only your own blog
        $restrictwhere .= "
AND bi.userid = ?";
        $rwparam[] = $userid;
    }

// Query for newest version that follows these restrictions
    $result = $DB->get_field_sql("
SELECT
    MAX(p.timeposted)
FROM
    {oublog_posts} p
    INNER JOIN {oublog_instances} bi ON p.oubloginstancesid = bi.id
    $restrictjoin
WHERE
    bi.oublogid = ?
    AND p.timedeleted IS NULL
    $restrictwhere", array_merge(array($oublog->id), $rwparam));
    return $result;
}

/**
 * Prints mobile specific links on the view page
 * @param int $id blog id
 * @param string $blogdets whether blog details of posts are being shown
 */
function ou_print_mobile_navigation($id = null , $blogdets = null, $post = null, $user=null){
    global $CFG;

    if($id){
        $qs   = 'id='.$id.'&amp;direct=1';
        $file = 'view';
    }
    else if ($user){
        $qs   = 'user='.$user;
        $file = 'view';
    }
    else {
        $qs   = 'post=' . $post;
        $file = 'viewpost';
    }

    if($blogdets != 'show'){
        $qs           .= '&amp;blogdets=show';
        $desc        = get_string('viewblogdetails','oublog');
        $class_extras = '';
    }
    else {
        $qs           .= '';
        $desc         = get_string('viewblogposts','oublog');
        $class_extras = ' oublog-mobile-space';
    }
    print '<div class="oublog-mobile-main-link'.$class_extras.'">';
    print '<a href="'.$CFG->wwwroot.'/mod/oublog/'.$file.'.php?'.$qs.'">';
    print $desc;
    print '</a></div>';
}

/**
 * For moderated users; rate-limits comments by IP address.
 * @return True if user has made too many comments within past hour
 */
function oublog_too_many_comments_from_ip() {
    global $DB;
    $ip = getremoteaddr();
    $hourago = time() - 3600;
    $count = $DB->count_records_sql("SELECT COUNT(1) FROM " .
        "{oublog_comments_moderated} WHERE authorip = ? " .
        "AND timeposted > ?", array($ip, $hourago));
    // Max comments per hour = 10 at present
    return $count > 10;
}

/**
 * Works out the typical time it takes a given user to approve blog entries,
 * based on all entries from the past year.
 * @param int $userid User ID
 * @return string String representation of typical time e.g. '24 hours' or
 *   '3 days', or false if unable to estimate yet
 */
function oublog_get_typical_approval_time($userid) {
    global $DB;

    // Get delays for all the posts they approved in the past year
    $rs = $DB->get_recordset_sql("
SELECT (bc.timeapproved - bc.timeposted) AS delay FROM {oublog_comments} bc
INNER JOIN {oublog_posts} bp on bc.postid = bp.id
INNER JOIN {oublog_instances} bi on bp.oubloginstancesid = bi.id
WHERE
bi.userid=?
AND bc.userid IS NULL
ORDER BY (bc.timeapproved - bc.timeposted)", array($userid));
    if (empty($rs)) {
        print_error('invalidblog','oublog');
    }
    $times = array();
    foreach ($rs as $rec) {
        $times[] = $rec->delay;
    }
    $rs->close();

    // If the author hasn't approved that many comments, don't give an estimate
    if (count($times) < 5) {
        return false;
    }

    // Use the 75th percentile
    $index = floor((count($times) * 3) / 4);
    $delay = $times[$index];

    // If it's less than a day
    if ($delay < 24 * 3600) {
        // Round up to hours (at least 2)
        $delay = ceil($delay/3600);
        if ($delay < 2) {
            $delay = 2;
        }
        return get_string('numhours', '', $delay);
    } else {
        // Round up to days (at least 2)
        $delay = ceil($delay / (24*3600));
        if ($delay < 2) {
            $delay = 2;
        }
        return get_string('numdays', '', $delay);
    }
}

/**
 * Applies high security restrictions to HTML input from moderated comments.
 * Recursive function.
 * @param $element DOM element
 */
function oublog_apply_high_security(DOMElement $element) {
    // Note that Moodle security should probably already prevent this (and
    // should include a whitelist approach), but just to increase the paranoia
    // level a bit with these comments.
    static $allowtags = array(
        'html' => 1, 'body' => 1,
        'em' => 1, 'strong' => 1, 'b' => 1, 'i' => 1, 'del' => 1, 'sup' => 1,
            'sub' => 1, 'span' => 1, 'a' => 1, 'img' => 1,
        'p' => 1, 'div' => 1
    );

    // Chuck away any disallowed tags
    if (!array_key_exists(strtolower($element->tagName), $allowtags)) {
        $parent = $element->parentNode;
        while($child = $element->firstChild) {
            $element->removeChild($child);
            $parent->insertBefore($child, $element);
            if ($child->nodeType == XML_ELEMENT_NODE) {
                oublog_apply_high_security($child);
            }
        }
        $parent->removeChild($element);
        return;
    }

    // Chuck away all attributes except href, src pointing to a folder or HTML, image
    // (this prevents SWF embed by link, if site is unwise enough to have that
    // turned on)
    $attributenames = array();
    $keepattributes = array();
    foreach($element->attributes as $name => $value) {
        $attributenames[] = $name;
        $keep = false;
        if ($name === 'href' && preg_match('~^https?://~', $value->nodeValue)) {
            $keep = true;
        } else if ($name === 'src' &&
                preg_match('~^https?://.*\.(jpg|jpeg|png|gif|svg)$~', $value->nodeValue)) {
            $keep = true;
        } else if($name === 'alt') {
            $keep = true;
        }
        if ($keep) {
            $keepattributes[$name] = $value->nodeValue;
        }
    }
    foreach($attributenames as $name) {
        $element->removeAttribute($name);
    }
    foreach($keepattributes as $name=>$value) {
        $element->setAttribute($name, $value);
    }

    // Recurse to children
    $children = array();
    foreach($element->childNodes as $child) {
        $children[] = $child;
    }
    foreach($children as $child) {
        if ($child->nodeType == XML_ELEMENT_NODE) {
            oublog_apply_high_security($child);
        }
    }
}

/**
 * Adds a new moderated comment into the database ready for approval, and sends
 * an approval email to the moderator (person who owns the blog post).
 * @param object $oublog Blog object
 * @param object $oubloginstance Blog instance object
 * @param object $post Blog post object
 * @param object $comment Comment object (including slashes)
 */
function oublog_add_comment_moderated($oublog, $oubloginstance, $post, $comment) {
    global $CFG, $USER, $SESSION, $SITE, $DB;

    // Extra security on moderated comment
    $dom = @DOMDocument::loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body><div>' .
            $comment->message . '</div></body></html>');
    oublog_apply_high_security($dom->documentElement);
    $html = $dom->saveHTML();
    $start = strpos($html, '<body><div>') + 11;
    $end = strrpos($html, '</div></body>');
    $comment->message = substr($html, $start, $end - $start);
    $comment->title = trim($comment->title);

    // Add comment to database
    unset($comment->userid);
    $comment->timeposted = time();
    $comment->authorip = getremoteaddr();
    // Secret key is half the SHA-1 of a random number plus current time
    $comment->secretkey = substr(sha1(rand() . microtime(true)), 0, 20);
    $comment->id = $DB->insert_record('oublog_comments_moderated', $comment);
    if (!$comment->id) {
        return false;
    }

    // Get blog owner
    $result = true;
    $user = $DB->get_record('user', array('id'=>$oubloginstance->userid));
    if (!$user) {
        $result = false;
        $user = (object)array('lang'=>'');
    }

    // Change language temporarily (UGH - this is horrible but there doesn't
    // seem to be a way to do it in moodle without logging in as the other
    // user). This is based on (defeating) the logic in current_language.
    $oldsessionlang = null;
    if (!empty($SESSION->lang)) {
        // If this user has chosen a language in session, turn that off
        $oldsessionlang = $SESSION->lang;
        $SESSION->lang = null;
    }
    $USER->lang = $user->lang;

    // Subject line
    $commenterhtml = s($comment->authorname);
    $a = (object)array(
        'blog' => s($oublog->global ? $oubloginstance->name : $oublog->name),
        'commenter' => $commenterhtml
    );
    $subject = get_string('moderated_emailsubject', 'oublog', $a);

    // Main text
    $approvebase = $CFG->wwwroot . '/mod/oublog/approve.php?mcomment=' .
        $comment->id . '&amp;key=' . $comment->secretkey;
    $a = (object)array(
        'postlink' => '<a href="' . $CFG->wwwroot .
            '/mod/oublog/viewpost.php?post=' . $post->id . '">' .
            ($post->title ? s($post->title) : shorten_text(strip_tags($post->message))) .
            '</a>',
        'commenter' => $commenterhtml,
        'commenttitle' => $comment->title ? $comment->title : '',
        'comment' =>
            format_text($comment->message, FORMAT_MOODLE,
            null, $oublog->course),
        'approvelink' => $approvebase . '&amp;approve=1',
        'approvetext' => get_string('moderated_approve', 'oublog'),
        'rejectlink' => $approvebase . '&amp;approve=0',
        'rejecttext' => get_string('moderated_reject', 'oublog'),
        'restrictpostlink' => $CFG->wwwroot .
            '/mod/oublog/restrictcomments.php?post=' . $post->id,
        'restrictposttext' => get_string('moderated_restrictpost', 'oublog'),
        'restrictbloglink' => $CFG->wwwroot .
            '/mod/oublog/restrictcomments.php?blog=' . $oublog->id,
        'restrictblogtext' => get_string('moderated_restrictblog', 'oublog')
    );
    $messagetext = get_string('moderated_emailtext', 'oublog', $a);
    $messagehtml = get_string('moderated_emailhtml', 'oublog', $a);
    // hack to remove empty tags when there is no title
    $messagehtml = str_replace('<h3></h3>', '', $messagehtml);
    $result = $result && email_to_user($user, $SITE->fullname,
        $subject, $messagetext, $messagehtml);

    // Put language back
    if ($oldsessionlang) {
        $SESSION->lang = $oldsessionlang;
    }
    $USER->lang = null;

    if (!$result) {
        // Oh well, better delete it from database
        $DB->delete_records('oublog_comments_moderated', array('id'=>$comment->id));
        return false;
    }
    return true;
}

/**
 * Obtains comments that are awaiting moderation for a particulasr post
 * @param object $oublog Moodle data object from oublog
 * @param object $post Moodle data object from oublog_posts
 * @param bool $includeset If true, includes comments which have already been
 *   rejected or approved, as well as those which await processing
 * @return array Array of Moodle data objects from oublog_comments_moderated;
 *   empty array (not false) if none; objects are in date order (oldest first)
 */
function oublog_get_moderated_comments($oublog, $post, $includeset=false) {
    global $DB;
    // Don't bother checking if public comments are not allowed
    if ($oublog->allowcomments < OUBLOG_COMMENTS_ALLOWPUBLIC
            && $post->allowcomments < OUBLOG_COMMENTS_ALLOWPUBLIC) {
        return array();
    }

    // Query for moderated comments
    $result = $DB->get_records_select('oublog_comments_moderated',
        ($includeset ? '' : 'approval=0 AND ') . 'postid= ?', array($post->id), 'id');
    return $result ? $result : array();
}

/**
 * Approves or rejects a moderated comment.
 * @param object $mcomment Moderated comment object (no slashes)
 * @param bool $approve True to approve, false to reject
 * @return ID of new comment row, or false if failure
 */
function oublog_approve_comment($mcomment, $approve) {
    global $DB;
    // Get current time and start transaction
    $now = time();
    $tw=new transaction_wrapper();

    // Update the moderated comment record
    $update = (object)array(
        'id' => $mcomment->id,
        'approval' => $approve ? OUBLOG_MODERATED_APPROVED :
            OUBLOG_MODERATED_REJECTED,
        'timeset' => $now
    );
    if (!$DB->update_record('oublog_comments_moderated', $update)) {
        return false;
    }

    // Add the new comment record
    if ($approve) {
        $insert = (object)array(
            'postid' => $mcomment->postid,
            'title' => $mcomment->title,
            'message' => $mcomment->message,
            'timeposted' => $mcomment->timeposted,
            'authorname' => $mcomment->authorname,
            'authorip' => $mcomment->authorip,
            'timeapproved' => $now);
        if (!($id = $DB->insert_record('oublog_comments', $insert))) {
            return false;
        }
    } else {
        $id = true;
    }

    // Commit transaction and return id
    $tw->commit();
    return $id;
}

/**
 * Gets the extra navigation needed for pages relating to a post.
 * @param object $post Moodle database object for post
 * @param bool $link True if post name should be a link
 */
function oublog_get_post_extranav($post, $link=true) {
    global $PAGE;
    if ($link) {
        $url = new moodle_url('/mod/oublog/viewpost.php', array('post'=>$post->id));
    } else {
        $url = null;
    }
    if (!empty($post->title)) {
        $PAGE->navbar->add(format_string($post->title), $url);
    } else {
        $PAGE->navbar->add(shorten_text(format_string($post->message, 30)), $url);
    }
}

class oublog_portfolio_caller extends portfolio_module_caller_base {

    protected $postid;
    protected $attachment;

    private $post;
    private $keyedfiles = array(); // keyed on entry

    /**
     * @return array
     */
    public static function expected_callbackargs() {
        return array(
            'postid'       => false,
            'attachment'   => false,
        );
    }
    /**
     * @param array $callbackargs
     */
    function __construct($callbackargs) {
        parent::__construct($callbackargs);
        if (!$this->postid) {
            throw new portfolio_caller_exception('mustprovidepost', 'oublog');
        }
    }
    /**
     * @global object
     */
    public function load_data() {
        global $DB;

        if ($this->postid) {
            if (!$this->post = $DB->get_record('oublog_posts', array('id' => $this->postid))) {
                throw new portfolio_caller_exception('invalidpostid', 'oublog');
            }
        }

        if (!$this->oubloginstance = $DB->get_record('oublog_instances', array('id'=>$this->post->oubloginstancesid))) {
            throw new portfolio_caller_exception('postid', 'oublog');
        }

        if (!$this->oublog = $DB->get_record('oublog', array('id' => $this->oubloginstance->oublogid))) {
            throw new portfolio_caller_exception('invalidpostid', 'oublog');
        }

        if (!$this->cm = get_coursemodule_from_instance('oublog', $this->oublog->id)) {
            throw new portfolio_caller_exception('invalidcoursemodule');
        }

        $this->modcontext = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        $fs = get_file_storage();
        if ($this->attachment) {
            $this->set_file_and_format_data($this->attachment);
        } else {
            $attach = $fs->get_area_files($this->modcontext->id, 'mod_oublog', 'attachment',$this->post->id);
            $embed  = $fs->get_area_files($this->modcontext->id, 'mod_oublog', 'message',$this->post->id);
            $files = array_merge($attach, $embed);
            $this->set_file_and_format_data($files);
        }
        if (!empty($this->multifiles)) {
            $this->keyedfiles[$this->post->id] = $this->multifiles;
        } else if (!empty($this->singlefile)) {
            $this->keyedfiles[$this->post->id] = array($this->singlefile);
        }
        if (empty($this->multifiles) && !empty($this->singlefile)) {
            $this->multifiles = array($this->singlefile); // copy_files workaround
        }
        // depending on whether there are files or not, we might have to change richhtml/plainhtml
        if (empty($this->attachment)) {
            if (!empty($this->multifiles)) {
                $this->add_format(PORTFOLIO_FORMAT_RICHHTML);
            } else {
                $this->add_format(PORTFOLIO_FORMAT_PLAINHTML);
            }
        }
    }

    /**
     * @global object
     * @return string
     */
    function get_return_url() {
        global $CFG;
        return $CFG->wwwroot . '/mod/oublog/viewpost.php?post=' . $this->post->id;
    }
    /**
     * @global object
     * @return array
     */
    function get_navigation() {
        global $CFG;

        $navlinks = array();
        $navlinks[] = array(
            'name' => format_string($this->oublog->name),
            'link' => $CFG->wwwroot . '/mod/oublog/viewpost.php?post=' . $this->post->id,
            'type' => 'title'
        );
        return array($navlinks, $this->cm);
    }
    /**
     * either a whole discussion
     * a single post, with or without attachment
     * or just an attachment with no post
     *
     * @global object
     * @global object
     * @uses PORTFOLIO_FORMAT_RICH
     * @return mixed
     */
    function prepare_package() {
        global $CFG;

        if ($this->attachment) { // simplest case first - single file attachment
            $this->copy_files(array($this->singlefile), $this->attachment);
        } else { // exporting a single post
            $posthtml = $this->prepare_post($this->post);

            $content = $posthtml;
            $name = 'post.html';
            $manifest = ($this->exporter->get('format') instanceof PORTFOLIO_FORMAT_RICH);

            $this->copy_files($this->multifiles);
            $this->get('exporter')->write_new_file($content, $name, $manifest);
        }
    }

    /**
     * @param array $files
     * @param mixed $justone false of id of single file to copy
     * @return bool|void
     */
    private function copy_files($files, $justone=false) {
        if (empty($files)) {
            return;
        }
        foreach ($files as $f) {
            if ($justone && $f->get_id() != $justone) {
                continue;
            }
            $this->get('exporter')->copy_existing_file($f);
            if ($justone && $f->get_id() == $justone) {
                return true; // all we need to do
            }
        }
    }
    /**
     * this is a very cut down version of what is in forum_make_mail_post
     *
     * @global object
     * @param int $post
     * @return string
     */
    private function prepare_post($post, $fileoutputextras=null) {
        global $DB, $PAGE;
        static $users;
        if (empty($users)) {
            $users = array($this->user->id => $this->user);
        }
        if (!array_key_exists($this->oubloginstance->userid, $users)) {
            $users[$this->oubloginstance->userid] = $DB->get_record('user',
                    array('id' => $this->oubloginstance->userid));
        }
        // Add the user object on to the post.
        $post->author = $users[$this->oubloginstance->userid];
        $viewfullnames = true;
        // Format the post body.
        $options = portfolio_format_text_options();
        $options->context = get_context_instance(CONTEXT_COURSE, $this->get('course')->id);
        $format = $this->get('exporter')->get('format');
        $formattedtext = format_text($post->message, FORMAT_HTML, $options);
        $formattedtext = portfolio_rewrite_pluginfile_urls($formattedtext, $this->modcontext->id,
                'mod_oublog', 'message', $post->id, $format);

        $output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" ' .
                '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' .
                html_writer::start_tag('html', array('xmlns' => 'http://www.w3.org/1999/xhtml'));
        $output .= html_writer::tag('head',
                html_writer::empty_tag('meta',
                    array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8')) .
                html_writer::tag('title', get_string('exportedpost', 'oublog')));
        $output .= html_writer::start_tag('body') . "\n";
        if (!$oublog = oublog_get_blog_from_postid($post->id)) {
            print_error('invalidpost', 'oublog');
        }
        if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
            print_error('invalidcoursemodule');
        }
        $oublogoutput = $PAGE->get_renderer('mod_oublog');
        $context = context_module::instance($cm->id);
        $canmanageposts = has_capability('mod/oublog:manageposts', $context);

        if ($oublog->global) {
            $blogtype = 'personal';
        } else {
            $blogtype = 'course';
        }
        // Recover complete post object for rendering.
        $post = oublog_get_post($post->id);
        $post->allowcomments = false;
        $output .= $oublogoutput->render_post($cm, $oublog, $post, false, $blogtype,
                $canmanageposts, false, false, true);
        if (!empty($post->comments)) {
            $output .= $oublogoutput->render_comments($post, $oublog, false, false, true, $cm);
        }
        $output .= html_writer::end_tag('body') . html_writer::end_tag('html');
        return $output;
    }
    /**
     * @return string
     */
    function get_sha1() {
        $filesha = '';
        try {
            $filesha = $this->get_sha1_file();
        } catch (portfolio_caller_exception $e) { } // no files

        return sha1($filesha . ',' . $this->post->title . ',' . $this->post->message);
    }

    function expected_time() {
        return $this->expected_time_file();
    }
    /**
     * @uses CONTEXT_MODULE
     * @return bool
     */
    function check_permissions() {
        $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        return (has_capability('mod/oublog:exportpost', $context)
            || ($this->oubloginstance->userid == $this->user->id
                && has_capability('mod/oublog:exportownpost', $context)));
    }
    /**
     * @return string
     */
    public static function display_name() {
        return get_string('modulename', 'oublog');
    }

    public static function base_supported_formats() {
        return array(PORTFOLIO_FORMAT_FILE, PORTFOLIO_FORMAT_RICHHTML, PORTFOLIO_FORMAT_PLAINHTML);
    }
}

/**
 * Returns html for a search form for the nav bar
 * @param string $name blog identifier field e.g. id
 * @param string $value blog identifier value e.g. 266
 * @param string $strblogsearch search this blog text
 * @param string $querytext optional search term
 * @returns string html
 */
function oublog_get_search_form($name, $value, $strblogsearch, $querytext='') {
    if (!oublog_search_installed()) {
        return '';
    }
    global $OUTPUT;
    $out = html_writer::start_tag('form', array('action' => 'search.php', 'method' => 'get'));
    $out .= html_writer::start_tag('div');
    $out .= html_writer::tag('label', $strblogsearch . ' ', array('for' => 'oublog_searchquery'));
    $out .= $OUTPUT->help_icon('searchthisblog', 'oublog');
    $out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $name,
            'value' => $value));
    $out .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'query',
            'id' => 'oublog_searchquery', 'value' => $querytext));
    $out .= html_writer::empty_tag('input', array('type' => 'submit',
            'id' => 'ousearch_searchbutton', 'value' => '', 'alt' => get_string('search'),
            'title' => get_string('search')));
    $out .= html_writer::end_tag('div');
    $out .= html_writer::end_tag('form');
    return $out;
}

/**
 * Checks what level of participation the currently
 * logged in user can view
 *
 * @param object $course current course object
 * @param object $oublog current oublog object
 * @param object $cm current course module object
 * @param int $groupid optional group id term
 */
function oublog_can_view_participation($course, $oublog, $cm, $groupid=0) {
    global $USER;

    // no participation at all on global blogs
    if ($oublog->global == 1) {
        return OUBLOG_NO_PARTICIPATION;
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $groupmode = groups_get_activity_groupmode($cm, $course);
    $allowgroup =
            ($groupmode == NOGROUPS || $groupmode == VISIBLEGROUPS)
            || (has_capability('moodle/site:accessallgroups', $context))
            || (groups_is_member($groupid, $USER->id));

    if (has_capability('mod/oublog:viewparticipation', $context)
        && $allowgroup
        && (($oublog->individual == OUBLOG_VISIBLE_INDIVIDUAL_BLOGS
        || $oublog->individual == OUBLOG_NO_INDIVIDUAL_BLOGS)
        || has_capability('mod/oublog:viewindividual', $context))) {
        return OUBLOG_USER_PARTICIPATION;
    } else if ((has_capability('mod/oublog:post', $context)
        || has_capability('mod/oublog:comment', $context))
        && $allowgroup) {
        return OUBLOG_MY_PARTICIPATION;
    }

    return OUBLOG_NO_PARTICIPATION;
}

/**
 * Checks if current user is allowed to grade the given blog.
 * @param object $course Moodle course object
 * @param object $oublog Row from oublog table
 * @param object $cm Course-module object
 * @param int $groupid Optional group id
 * @return bool True if you can grade the blog
 */
function oublog_can_grade($course, $oublog, $cm, $groupid=0) {
    global $USER;

    // Cannot grade if blog has grading turned off
    if (!$oublog->grade) {
        return false;
    }

    // Cannot grade if you do not have the capability
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if (!has_capability('mod/oublog:grade', $context)) {
        return false;
    }

    // Grading is a 'write' activity so you can only do it for your own
    // group unless you have accessallgroups
    $groupmode = groups_get_activity_groupmode($cm, $course);
    $ok = $groupmode == NOGROUPS ||
            has_capability('moodle/site:accessallgroups', $context) ||
            ($groupid && groups_is_member($groupid, $USER->id));
    return $ok;
}

/**
 * Returns information about the participation of users in this blog.
 *
 * @param object $oublog current oublog object
 * @param object $context current context
 * @param int $groupid optional group id term
 * @param object $cm course-module object
 * @param object $course current course object
 * @param string $sort optional string to sort users by fields
 * @return array user participation
 */
function oublog_get_participation($oublog, $context, $groupid=0, $cm,
    $course, $sort='u.firstname,u.lastname') {
    global $DB;

    // get user objects
    list($esql, $params) = get_enrolled_sql($context, 'mod/oublog:post', $groupid);
    $fields = user_picture::fields('u');
    $fields .= ',u.username,u.idnumber';
    $sql = "SELECT $fields
                FROM {user} u
                JOIN ($esql) eu ON eu.id = u.id
                ORDER BY $sort ASC";
    $users = $DB->get_records_sql($sql, $params);
    if (empty($users)) {
        return array();
    }
    if ($oublog->individual > 0) {
        $groupid = 0;
    }
    $postswhere = ' WHERE bi.userid IN (' . implode(',', array_keys($users)) .')';
    $commentswhere = ' WHERE c.userid IN (' . implode(',', array_keys($users)) .')';

    $groupcheck = $groupid ? 'AND groupid = :groupid' : '';

    $postssql = 'SELECT bi.userid, p.posts
        FROM {oublog_instances} bi
        LEFT OUTER JOIN (
            SELECT oubloginstancesid, COUNT(id) as posts
            FROM {oublog_posts}
            WHERE timedeleted IS NULL ' . $groupcheck . '
            GROUP BY oubloginstancesid
        ) p ON p.oubloginstancesid = bi.id' .
        $postswhere .
        ' AND bi.oublogid = :oublogid';

    $commentssql = 'SELECT c.userid, COUNT(c.id) AS comments
        FROM {oublog_comments} c, {oublog_instances} bi ' .
        $commentswhere .
        ' AND c.postid IN (
            SELECT id
            FROM {oublog_posts}
            WHERE oubloginstancesid = bi.id ' . $groupcheck . '
            AND timedeleted IS NULL
        )
        AND c.timedeleted IS NULL
        AND bi.oublogid = :oublogid GROUP BY c.userid';
    $params['oublogid'] = $oublog->id;
    $params['groupid'] = $groupid;

    // get all user post information
    $posts = $DB->get_records_sql($postssql, $params);

    // get all user comment information
    $comments = $DB->get_records_sql($commentssql, $params);

    if (!empty($users)) {
        // is grading enabled and available for the current user
        $gradinginfo = null;
        if (oublog_can_grade($course, $oublog, $cm, $groupid)) {
            $gradinginfo = grade_get_grades($course->id, 'mod',
                'oublog', $oublog->id, array_keys($users));
        }

        foreach ($users as $user) {
            if (!empty($posts) && isset($posts[$user->id])) {
                $user->posts = $posts[$user->id]->posts;
            }
            if (!empty($comments) && isset($comments[$user->id])) {
                $user->comments = $comments[$user->id]->comments;
            }
            if ($gradinginfo && !empty($gradinginfo->items[0]->grades)) {
                if (isset($gradinginfo->items[0]->grades[$user->id])) {
                    $user->gradeobj = $gradinginfo->items[0]->grades[$user->id];
                }
            }
        }
    }

    return $users;
}

/**
 * Returns user participation to view in userparticipation.php
 *
 * @param object $oublog current oublog object
 * @param object $context current context
 * @param int $userid required userid term for participation being viewed
 * @param int $groupid optional group id term
 * @param object $cm course-module object
 * @param object $course current course object
 * @return array user participation
 */
function oublog_get_user_participation($oublog, $context, $userid, $groupid=0, $cm, $course) {
    global $DB;
    $testgroupid = $groupid;
    if ($oublog->individual > 0) {
        $testgroupid = 0;
    }
    $groupcheck = $testgroupid ? 'AND groupid = :groupid' : '';

    $postssql = 'SELECT id, title, message, timeposted
        FROM {oublog_posts}
        WHERE oubloginstancesid = (
            SELECT id
            FROM {oublog_instances}
            WHERE oublogid = :oublogid AND userid = :userid
        )
        AND timedeleted IS NULL ' . $groupcheck . '
        ORDER BY timeposted ASC';

    $commentssql = 'SELECT c.id, c.postid, c.title, c.message, c.timeposted,
        a.id AS authorid, a.firstname, a.lastname,
        p.title AS posttitle, p.timeposted AS postdate
        FROM {user} a, {oublog_comments} c
            INNER JOIN {oublog_posts} p ON (c.postid = p.id)
            INNER JOIN {oublog_instances} bi ON (bi.id = p.oubloginstancesid)
        WHERE bi.oublogid = :oublogid AND a.id = bi.userid
        AND p.timedeleted IS NULL ' . $groupcheck . '
        AND c.userid = :userid AND c.timedeleted IS NULL
            ORDER BY c.timeposted ASC';

    $params = array(
        'oublogid' => $oublog->id,
        'userid' => $userid,
        'groupid' => $testgroupid
    );

    $fields = user_picture::fields();
    $fields .= ',username,idnumber';
    $user = $DB->get_record('user', array('id' => $userid), $fields, MUST_EXIST);

    $participation = new StdClass;
    $participation->user = $user;
    $participation->posts = $DB->get_records_sql($postssql, $params);
    $participation->comments = $DB->get_records_sql($commentssql, $params);
    if (oublog_can_grade($course, $oublog, $cm, $groupid)) {
        $gradinginfo = grade_get_grades($course->id, 'mod',
            'oublog', $oublog->id, array($userid));
        $participation->gradeobj = $gradinginfo->items[0]->grades[$userid];
    }
    return $participation;
}

/**
 * Grades users from the participation.php page
 *
 * @param array $newgrades array of grade records to update
 * @param array $oldgrades array of old grade records to check
 * @param object $cm current course module object
 * @param object $oublog current oublog object
 * @param object $course current course object
 */
function oublog_update_grades($newgrades, $oldgrades, $cm, $oublog, $course) {
    global $CFG, $SESSION;

    require_once($CFG->libdir.'/gradelib.php');

    $grades = array();
    foreach ($oldgrades as $key => $user) {
        if (array_key_exists($key, $newgrades)) {
            if (empty($user->gradeobj->grade)
                || ($newgrades[$key] != $user->gradeobj->grade)) {
                $grade = new StdClass;
                $grade->userid = $key;
                $grade->dategraded = time();
                if ($newgrades[$key] == -1) {
                    // no grade
                    $grade->rawgrade = null;
                } else {
                    $grade->rawgrade = $newgrades[$key];
                }
                $oublog->cmidnumber = $cm->id;

                $grades[$key] = $grade;
            }
        }
    }
    oublog_grade_item_update($oublog, $grades);

    // add a message to display to the page
    if (!isset($SESSION->oubloggradesupdated)) {
        $SESSION->oubloggradesupdated = get_string('gradesupdated', 'oublog');
    }
}
