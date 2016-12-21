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
 * Library of functions for the oublog module.
 *
 * This contains functions that are called also from outside the oublog module
 * Functions that are only called by the quiz module itself are in {@link locallib.php}
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package oublog
 */




/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $oublog the data from the mod form
 * @return int The id od the newly inserted module
 */
function oublog_add_instance($oublog) {
    global $DB;
    // Generate an accesstoken.
    $oublog->accesstoken = md5(uniqid(rand(), true));

    if (empty($oublog->ratingtime) || empty($oublog->assessed)) {
        $oublog->assesstimestart = 0;
        $oublog->assesstimefinish = 0;
    }

    if (!$oublog->id = $DB->insert_record('oublog', $oublog)) {
        return(false);
    }
    if (!empty($oublog->tagslist)) {
        $blogtags = oublog_clarify_tags($oublog->tagslist);
        // For each tag added to the blog check if it exists in oublog_tags table,
        // if it does not a tag record is created.
        foreach ($blogtags as $tag) {
            if (!$DB->get_record('oublog_tags', array('tag' => $tag))) {
                $DB->insert_record('oublog_tags', (object) array('tag' => $tag));
            }
        }
    }
    oublog_grade_item_update($oublog);

    return($oublog->id);
}



/**
 * Given an object containing all the necessary data,(defined by the
 * form in mod_form.php) this function will update an existing instance
 * with new data.
 *
 * @param object $oublog the data from the mod form
 * @return boolean true on success, false on failure.
 */
function oublog_update_instance($oublog) {
    global $DB;
    $oublog->id = $oublog->instance;

    if (!$DB->get_record('oublog', array('id' => $oublog->id))) {
        return(false);
    }

    if (empty($oublog->ratingtime) || empty($oublog->assessed)) {
        $oublog->assesstimestart = 0;
        $oublog->assesstimefinish = 0;
    }

    if (!$DB->update_record('oublog', $oublog)) {
        return(false);
    }

    $blogtags = oublog_clarify_tags($oublog->tagslist);
    // For each tag in the blog check if it already exists in oublog_tags table,
    // if it does not a tag record is created.
    foreach ($blogtags as $tag) {
        if (!$DB->get_record('oublog_tags', array('tag' => $tag))) {
            $DB->insert_record('oublog_tags', (object) array('tag' => $tag));
        }
    }

    oublog_grade_item_update($oublog);

    return(true);
}



/**
 * Given an ID of an instance of this module, this function will
 * permanently delete the instance and any data that depends on it.
 *
 * @param int $id The ID of the module instance
 * @return boolena true on success, false on failure.
 */
function oublog_delete_instance($oublogid) {
    global $DB, $CFG;
    if (!$oublog = $DB->get_record('oublog', array('id'=>$oublogid))) {
        return(false);
    }

    if ($oublog->global) {
        print_error('deleteglobalblog', 'oublog');
    }

    if ($instances = $DB->get_records('oublog_instances', array('oublogid'=>$oublog->id))) {

        foreach ($instances as $oubloginstancesid => $bloginstance) {
            // tags
            $DB->delete_records('oublog_taginstances', array('oubloginstancesid'=>$oubloginstancesid));

            if ($posts = $DB->get_records('oublog_posts', array('oubloginstancesid'=>$oubloginstancesid))) {

                foreach ($posts as $postid => $post) {
                    // comments
                    $DB->delete_records('oublog_comments', array('postid'=>$postid));

                    // edits
                    $DB->delete_records('oublog_edits', array('postid'=>$postid));
                }

                // posts
                $DB->delete_records('oublog_posts', array('oubloginstancesid'=>$oubloginstancesid));

            }
        }
    }

    // links
    $DB->delete_records('oublog_links', array('oublogid'=>$oublog->id));

    // instances
    $DB->delete_records('oublog_instances', array('oublogid'=>$oublog->id));

    // Fulltext search data
    require_once(dirname(__FILE__).'/locallib.php');
    if (oublog_search_installed()) {
        $moduleid=$DB->get_field('modules', 'id', array('name'=>'oublog'));
        $cm=$DB->get_record('course_modules', array('module'=>$moduleid, 'instance'=>$oublog->id));
        if (!$cm) {
            print_error('invalidcoursemodule');
        }
        local_ousearch_document::delete_module_instance_data($cm);
    }

    oublog_grade_item_delete($oublog);

    // oublog
    return($DB->delete_records('oublog', array('id'=>$oublog->id)));

}



/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $oublog
 * @return object containing a time and info properties
 */
function oublog_user_outline($course, $user, $mod, $oublog) {
    global $CFG, $DB;

    $sql = "SELECT count(*) AS postcnt, MAX(timeposted) as lastpost
            FROM {oublog_posts} p
                INNER JOIN {oublog_instances} i ON p.oubloginstancesid = i.id
            WHERE p.deletedby IS NULL AND i.userid = ? AND oublogid = ?";

    if ($postinfo = $DB->get_record_sql($sql, array($user->id, $mod->instance))) {
        $result = new stdClass();
        $result->info = get_string('numposts', 'oublog', $postinfo->postcnt);
        $result->time = $postinfo->lastpost;

        return($result);
    }

    return(null);
}



/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $oublog
 * @return object containing a time and info properties
 */
function oublog_user_complete($course, $user, $mod, $oublog) {
    global $CFG, $DB, $PAGE;
    include_once($CFG->dirroot.'/mod/oublog/locallib.php');

    $oublogoutput = $PAGE->get_renderer('mod_oublog');

    $baseurl = $CFG->wwwroot.'/mod/oublog/view.php?id='.$mod->id;

    $sql = "SELECT p.*
            FROM {oublog_posts} p
                INNER JOIN {oublog_instances} i ON p.oubloginstancesid = i.id
            WHERE p.deletedby IS NULL AND i.userid = ? AND oublogid = ? ";

    if ($posts = $DB->get_records_sql($sql, array($user->id, $mod->instance))) {
        foreach ($posts as $post) {
            $postdata = oublog_get_post($post->id);
            echo $oublogoutput->render_post($mod, $oublog, $postdata, $baseurl, 'course');
        }
    } else {
        echo get_string('noblogposts', 'oublog');
    }

    return(null);
}



/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in newmodule activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param object $course
 * @param bool $isteacher
 * @param int $timestart
 * @return boolean true on success, false on failure.
 **/
function oublog_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG, $DB, $OUTPUT;

    include_once('locallib.php');

    $sql = "SELECT i.oublogid, p.id AS postid, p.*, u.firstname, u.lastname, u.email, u.idnumber, i.userid
            FROM {oublog_posts} p
                INNER JOIN {oublog_instances} i ON p.oubloginstancesid = i.id
                INNER JOIN {oublog} b ON i.oublogid = b.id
                INNER JOIN {user} u ON i.userid = u.id
            WHERE b.course = ? AND p.deletedby IS NULL AND p.timeposted >= ? ";

    if (!$rs = $DB->get_recordset_sql($sql, array($course->id, $timestart))) {
        return(true);
    }

    $modinfo = get_fast_modinfo($course);

    $strftimerecent = get_string('strftimerecent');
    echo $OUTPUT->heading(get_string('newblogposts', 'oublog'), 3);

    echo "\n<ul class='unlist'>\n";
    foreach ($rs as $blog) {
        if (!isset($modinfo->instances['oublog'][$blog->oublogid])) {
            // not visible
            continue;
        }
        $cm = $modinfo->instances['oublog'][$blog->oublogid];
        if (!$cm->uservisible) {
            continue;
        }
        if (!has_capability('mod/oublog:view', context_module::instance($cm->id))) {
            continue;
        }
        if (!has_capability('mod/oublog:view', context_user::instance($blog->userid))) {
            continue;
        }

        $groupmode = oublog_get_activity_groupmode($cm, $course);

        if ($groupmode) {
            if ($blog->groupid && $groupmode != VISIBLEGROUPS) {
                // separate mode
                if (isguestuser()) {
                    // shortcut
                    continue;
                }

                if (is_null($modinfo->groups)) {
                    $modinfo->groups = groups_get_user_groups($course->id); // load all my groups and cache it in modinfo
                }

                if (!array_key_exists($blog->groupid, $modinfo->groups[0])) {
                    continue;
                }
            }
        }

        echo '<li><div class="head">'.
               '<div class="date">'.oublog_date($blog->timeposted, $strftimerecent).'</div>'.
               '<div class="name">'.fullname($blog).'</div>'.
             '</div>';
        echo '<div class="info">';
        echo "<a href=\"{$CFG->wwwroot}/mod/oublog/viewpost.php?post={$blog->postid}\">";
        echo break_up_long_words(format_string(empty($blog->title) ? $blog->message : $blog->title));
        echo '</a>';
        echo '</div>';
    }
    $rs->close();
    echo "</ul>\n";
}



/**
 * Get recent activity for a course
 *
 * @param array $activities
 * @param int $index
 * @param int $timestart
 * @param int $courseid
 * @param int $cmid
 * @param int $userid
 * @param int $groupid
 * @return bool
 */
function oublog_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
    global $CFG, $COURSE, $DB;

    $sql = "SELECT i.oublogid, p.id AS postid, p.*, u.firstname, u.lastname, u.email, u.idnumber, u.picture, u.imagealt, i.userid
            FROM {oublog_posts} p
                INNER JOIN {oublog_instances} i ON p.oubloginstancesid = i.id
                INNER JOIN {oublog} b ON i.oublogid = b.id
                INNER JOIN {user} u ON i.userid = u.id
            WHERE b.course = ? AND p.deletedby IS NULL AND p.timeposted >= ? ";

    if (!$rs = $DB->get_recordset_sql($sql, array($courseid, $timestart))) {
        return(true);
    }

    $modinfo = get_fast_modinfo($COURSE);

    foreach ($rs as $blog) {
        if (!isset($modinfo->instances['oublog'][$blog->oublogid])) {
            // not visible
            continue;
        }
        $cm = $modinfo->instances['oublog'][$blog->oublogid];
        if (!$cm->uservisible) {
            continue;
        }
        if (!has_capability('mod/oublog:view', context_module::instance($cm->id))) {
            continue;
        }
        if (!has_capability('mod/oublog:view', context_user::instance($blog->userid))) {
            continue;
        }

        $groupmode = oublog_get_activity_groupmode($cm, $COURSE);

        if ($groupmode) {
            if ($blog->groupid && $groupmode != VISIBLEGROUPS) {
                // separate mode
                if (isguestuser()) {
                    // shortcut
                    continue;
                }

                if (is_null($modinfo->groups)) {
                    $modinfo->groups = groups_get_user_groups($courseid); // load all my groups and cache it in modinfo
                }

                if (!array_key_exists($blog->groupid, $modinfo->groups[0])) {
                    continue;
                }
            }
        }

        $tmpactivity = new stdClass();

        $tmpactivity->type         = 'oublog';
        $tmpactivity->cmid         = $cm->id;
        $tmpactivity->name         = $blog->title;
        $tmpactivity->sectionnum   = $cm->sectionnum;
        $tmpactivity->timeposted    = $blog->timeposted;

        $tmpactivity->content = new stdClass();
        $tmpactivity->content->postid   = $blog->postid;
        $tmpactivity->content->title    = format_string($blog->title);

        $tmpactivity->user = new stdClass();
        $tmpactivity->user->id        = $blog->userid;
        $tmpactivity->user->firstname = $blog->firstname;
        $tmpactivity->user->lastname  = $blog->lastname;
        $tmpactivity->user->picture   = $blog->picture;
        $tmpactivity->user->imagealt  = $blog->imagealt;
        $tmpactivity->user->email     = $blog->email;

        $activities[$index++] = $tmpactivity;
    }
    $rs->close();
}


/**
 * Print recent oublog activity for a course
 *
 * @param object $activity
 * @param int $courseid
 * @param bool $detail
 * @param array $modnames
 * @param bool $viewfullnames
 */
function oublog_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class=oublog-recent">';

    echo "<tr><td class=\"userpicture\" valign=\"top\">";
    echo $OUTPUT->user_picture($activity->user, array('courseid'=>$courseid));
    echo "</td><td>";

    echo '<div class="title">';
    if ($detail) {
        echo "<img src=\"".$OUTPUT->pix_url('icon', $activity->type)."\" class=\"icon\" alt=\"".s($activity->title)."\" />";
    }
    echo "<a href=\"$CFG->wwwroot/mod/oublog/viewpost.php?post={$activity->content->postid}\">{$activity->content->title}</a>";
    echo '</div>';

    echo '<div class="user">';
    $fullname = fullname($activity->user, $viewfullnames);
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">"
    ."{$fullname}</a> - ".oublog_date($activity->timeposted);
    echo '</div>';
    echo "</td></tr></table>";

    return;
}


/**
 * Obtains a search document given the ousearch parameters.
 * @param object $document Object containing fields from the ousearch documents table
 * @return mixed False if object can't be found, otherwise object containing the following
 *   fields: ->content, ->title, ->url, ->activityname, ->activityurl
 */
function oublog_ousearch_get_document($document) {
    global $CFG, $DB;
    require_once('locallib.php');

    // Get data
    if (!($cm=$DB->get_record('course_modules', array('id' => $document->coursemoduleid)))) {
        return false;
    }
    if (!($oublog=$DB->get_record('oublog', array('id' => $cm->instance)))) {
        return false;
    }
    if (!($post=$DB->get_record_sql("
SELECT
    p.*,bi.userid
FROM
{oublog_posts} p
    INNER JOIN {oublog_instances} bi ON p.oubloginstancesid=bi.id
WHERE
    p.id= ? ", array($document->intref1)))) {
        return false;
    }

    $result=new StdClass;

    // Set up activity name and URL
    $result->activityname=$oublog->name;
    if ($oublog->global) {
        $result->activityurl=$CFG->wwwroot.'/mod/oublog/view.php?user='.
        $document->userid;
    } else {
        $result->activityurl=$CFG->wwwroot.'/mod/oublog/view.php?id='.
        $document->coursemoduleid;
    }

    // Now do the post details
    $result->title=$post->title;
    $result->content=$post->message;
    $result->url=$CFG->wwwroot.'/mod/oublog/viewpost.php?post='.$document->intref1;

    // Sort out tags for use as extrastrings
    $taglist=oublog_get_post_tags($post, true);
    if (count($taglist)!=0) {
        $result->extrastrings=$taglist;
    }

    // Post object is used in filter
    $result->data=$post;

    return $result;
}

/**
 * Update all documents for ousearch.
 * @param bool $feedback If true, prints feedback as HTML list items
 * @param int $courseid If specified, restricts to particular courseid
 */
function oublog_ousearch_update_all($feedback=false, $courseid=0) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/oublog/locallib.php');

    // Get all existing blogs as $cm objects (which we are going to need to
    // do the updates). get_records is ok here because we're only taking a
    // few fields and there's unlikely to be more than a few thousand blog
    // instances [user blogs all use a single course-module]
    $coursemodules=$DB->get_records_sql("
SELECT
    cm.id,cm.course,cm.instance
FROM
{modules} m
    INNER JOIN {course_modules} cm ON m.id=cm.module
WHERE
    m.name='oublog'".($courseid ? " AND cm.course= ? " : ""), array($courseid));
    if (!$coursemodules) {
        $coursemodules = array();
    }

    // Display info and loop around each coursemodule
    if ($feedback) {
        print '<li><strong>'.count($coursemodules).'</strong> instances to process.</li>';
        $dotcount=0;
    }
    $posts=0; $instances=0;
    foreach ($coursemodules as $coursemodule) {

        // Get all the posts that aren't deleted
        $rs=$DB->get_recordset_sql("
SELECT
    p.id,p.title,p.message,p.groupid,i.userid
FROM
{oublog_instances} i
    INNER JOIN {oublog_posts} p ON p.oubloginstancesid=i.id
WHERE
    p.deletedby IS NULL AND i.oublogid= ? ", array($coursemodule->instance));

        foreach ($rs as $post) {
            oublog_search_update($post, $coursemodule);

            // Add to count and do user feedback every 100 posts
            $posts++;
            if ($feedback && ($posts%100)==0) {
                if ($dotcount==0) {
                    print '<li>';
                }
                print '.';
                $dotcount++;
                if ($dotcount == 20 || $instances == count($coursemodules)) {
                    print "done $posts posts ($instances instances)</li>";
                    $dotcount=0;
                }
                flush();
            }
        }
        $rs->close();

        $instances++;
    }
    if ($feedback && ($dotcount!=0 || $posts<100)) {
        print ($dotcount==0?'<li>':'')."done $posts posts ($instances instances)</li>";
    }
}

/**
 * Indicates API features that the module supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function oublog_supports($feature) {
    switch($feature) {
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        case FEATURE_BACKUP_MOODLE2: return true;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_GROUPINGS: return true;
        case FEATURE_GROUPS: return true;
        case FEATURE_GRADE_HAS_GRADE: return true;
        case FEATURE_RATE: return true;
        default: return null;
    }
}

/**
 * Obtains the automatic completion state for this module based on any conditions
 * in module settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function oublog_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Get oublog details
    if (!($oublog=$DB->get_record('oublog', array('id' => $cm->instance)))) {
        throw new Exception("Can't find oublog {$cm->instance}");
    }

    $result=$type; // Default return value

    if ($oublog->completionposts) {
        // Count of posts by user
        $value = $oublog->completionposts <= $DB->get_field_sql("
SELECT
    COUNT(1)
FROM
{oublog_instances} i
    INNER JOIN {oublog_posts} p ON i.id=p.oubloginstancesid
WHERE
    i.userid= ? AND i.oublogid=? AND p.deletedby IS NULL", array($userid, $oublog->id));
        if ($type==COMPLETION_AND) {
            $result=$result && $value;
        } else {
            $result=$result || $value;
        }
    }
    if ($oublog->completioncomments) {
        // Count of comments by user (on posts by any user)
        $value = $oublog->completioncomments <= $DB->get_field_sql("
SELECT
    COUNT(1)
FROM
{oublog_comments} c
    INNER JOIN {oublog_posts} p ON p.id=c.postid
    INNER JOIN {oublog_instances} i ON i.id=p.oubloginstancesid
WHERE
    c.userid= ? AND i.oublogid= ? AND p.deletedby IS NULL AND c.deletedby IS NULL", array($userid, $oublog->id));
        if ($type==COMPLETION_AND) {
            $result=$result && $value;
        } else {
            $result=$result || $value;
        }
    }

    return $result;
}


/**
 * This function returns a summary of all the postings since the current user
 * last logged in.
 */
function oublog_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$blogs = get_all_instances_in_courses('oublog', $courses)) {
        return;
    }

    // get all  logs in ONE query
    $sql = "SELECT instance,cmid,l.course,COUNT(l.id) as count FROM {log} l "
    ." JOIN {course_modules} cm ON cm.id = cmid "
    ." WHERE (";
    $params = array();
    foreach ($courses as $course) {
        $sql .= '(l.course = ? AND l.time > ? )  OR ';
        $params[] = $course->id;
        $params[] = $course->lastaccess;
    }
    $sql = substr($sql, 0, -3); // take off the last OR

    // Ignore comment actions for now, only entries.
    $sql .= ") AND l.module = 'oublog' AND action in('add post','edit post')
      AND userid != ? GROUP BY cmid,l.course,instance";
    $params[] = $USER->id;
    if (!$new = $DB->get_records_sql($sql, $params)) {
        $new = array(); // avoid warnings
    }

    $strblogs = get_string('modulenameplural', 'oublog');

    $site = get_site();
    if (count( $courses ) == 1 && isset( $courses[$site->id])) {
        $strnumrespsince1 = get_string('overviewnumentrylog1', 'oublog');
        $strnumrespsince = get_string('overviewnumentrylog', 'oublog');
    } else {
        $strnumrespsince1 = get_string('overviewnumentryvw1', 'oublog');
        $strnumrespsince = get_string('overviewnumentryvw', 'oublog');
    }

    // Go through the list of all oublog instances build previously, and check whether
    // they have had any activity.
    foreach ($blogs as $blog) {
        if (array_key_exists($blog->id, $new) && !empty($new[$blog->id])) {
            $count = $new[$blog->id]->count;
            if ($count > 0) {
                if ($count == 1) {
                    $strresp = $strnumrespsince1;
                } else {
                    $strresp = $strnumrespsince;
                }

                $str = '<div class="overview oublog"><div class="name">'.
                $strblogs.': <a title="'.$strblogs.'" href="';
                if ($blog->global=='1') {
                    $str .= $CFG->wwwroot.'/mod/oublog/allposts.php">'.$blog->name.'</a></div>';
                } else {
                    $str .= $CFG->wwwroot.'/mod/oublog/view.php?id='.$new[$blog->id]->cmid.'">'.$blog->name.'</a></div>';
                }
                $str .= '<div class="info">';
                $str .= $count.' '.$strresp;
                $str .= '</div></div>';

                if (!array_key_exists($blog->course, $htmlarray)) {
                    $htmlarray[$blog->course] = array();
                }
                if (!array_key_exists('oublog', $htmlarray[$blog->course])) {
                    $htmlarray[$blog->course]['oublog'] = ''; // initialize, avoid warnings
                }
                $htmlarray[$blog->course]['oublog'] .= $str;

            }

        }

    }

}

/**
 * Serves the oublog attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function oublog_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    $fileareas = array('attachment', 'message', 'edit', 'messagecomment', 'summary');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }
    require_once(dirname(__FILE__).'/locallib.php');
    if ($filearea=='edit') {
        $editid = (int)array_shift($args);

        if (!$edit = $DB->get_record('oublog_edits', array('id'=>$editid))) {
            return false;
        }
        $postid = $edit->postid;
        $fileid = $editid;
    } else {
        $postid = (int)array_shift($args);
        $fileid = $postid;
    }

    if ($filearea != 'summary') {
        if ($filearea == 'messagecomment') {
            if (!$comment = $DB->get_record('oublog_comments', array('id' => $postid), 'postid')) {
                return false;
            }
            $postid = $comment->postid;
        }
        if (!$post = $DB->get_record('oublog_posts', array('id'=>$postid))) {
            return false;
        }
        if (!($oublog = oublog_get_blog_from_postid($post->id))) {
            return false;
        }
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_oublog/$filearea/$fileid/$relativepath";

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Make sure we're allowed to see it...
    // Check if coming from webservice - if so always allow.
    $ajax = constant('AJAX_SCRIPT') ? true : false;
    if ($filearea != 'summary' && !$ajax && !oublog_can_view_post($post, $USER, $context, $oublog->global)) {
        return false;
    }
    if ($filearea == 'attachment') {
        $forcedownload = true;
    } else {
        $forcedownload = false;
    }
    // Finally send the file.
    send_stored_file($file, 0, 0, $forcedownload);
}

/**
 * File browsing support for oublog module.
 * @param object $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance Representing an actual file or folder (null if not found
 * or cannot access)
 */
function oublog_get_file_info($browser, $areas, $course, $cm, $context, $filearea,
        $itemid, $filepath, $filename) {
    global $CFG, $USER, $DB;
    require_once($CFG->dirroot . '/mod/oublog/locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }
    $fileareas = array('attachment', 'message', 'edit', 'messagecomment');
    if (!in_array($filearea, $fileareas)) {
        return null;
    }
    $postid = $itemid;
    if ($filearea == 'messagecomment') {
        if (!$comment = $DB->get_record('oublog_comments', array('id' => $postid), 'postid')) {
            return null;
        }
        $postid = $comment->postid;
    }

    if (!($oublog = oublog_get_blog_from_postid($postid))) {
        return null;
    }
    // Check if the user is allowed to view the blog.
    if (!has_capability('mod/oublog:view', $context)) {
        return null;
    }

    if (!$post = oublog_get_post($postid)) {
        return null;
    }
    // Check if the user is allowed to view the post
    try {
        if (!oublog_can_view_post($post, $USER, $context, $oublog->global)) {
            return null;
        }
    } catch (mod_oublog_exception $e) {
        return null;
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!($storedfile = $fs->get_file($context->id, 'mod_oublog', $filearea, $itemid,
            $filepath, $filename))) {
        return null;
    }

    $urlbase = $CFG->wwwroot . '/pluginfile.php';
    return new file_info_stored($browser, $context, $storedfile, $urlbase, $filearea,
            $itemid, true, true, false);
}

/**
 * Sets the module uservisible to false if the user has not got the view capability
 * @param cm_info $cm
 */
function oublog_cm_info_dynamic(cm_info $cm) {
    global $remoteuserid, $USER;
    $userid = $USER;
    if (isset($remoteuserid) && !empty($remoteuserid)) {
        // Hack using dodgy global. The actual user id for specific user e.g. from webservice.
        $userid = $remoteuserid;
    }
    $capability = 'mod/oublog:view';
    if ($cm->course == SITEID && $cm->instance == 1) {
        // Is global blog (To save DB call we make suspect assumption it is instance 1)?
        $capability = 'mod/oublog:viewpersonal';
    }
    if (!has_capability($capability,
            context_module::instance($cm->id), $userid)) {
        $cm->set_user_visible(false);
        $cm->set_available(false);
    }
}

/**
 * Show last updated date + time (post created).
 *
 * @param cm_info $cm
 */
function oublog_cm_info_view(cm_info $cm) {
    global $CFG;
    if (!$cm->uservisible) {
        return;
    }
    require_once($CFG->dirroot . '/mod/oublog/locallib.php');

    $lastpostdate = oublog_get_last_modified($cm, $cm->get_course());
    if (!empty($lastpostdate)) {
        $cm->set_after_link(html_writer::span(get_string('lastmodified', 'oublog',
                        userdate($lastpostdate, get_string('strftimerecent', 'oublog'))), 'lastmodtext oubloglmt'));
    }
}

/**
 * Return blogs on course that have last modified date for current user
 *
 * @param stdClass $course
 * @return array
 */
function oublog_get_ourecent_activity($course) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/oublog/locallib.php');

    $modinfo = get_fast_modinfo($course);

    $return = array();

    foreach ($modinfo->get_instances_of('oublog') as $blog) {
        if ($blog->uservisible) {
            $lastpostdate = oublog_get_last_modified($blog, $blog->get_course());
            if (!empty($lastpostdate)) {
                $data = new stdClass();
                $data->cm = $blog;
                $data->text = get_string('lastmodified', 'oublog',
                        userdate($lastpostdate, get_string('strftimerecent', 'oublog')));
                $data->date = $lastpostdate;
                $return[$data->cm->id] = $data;
            }
        }
    }
    return $return;
}

/**
 * Create grade item for given oublog
 *
 * @param object $oublog
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function oublog_grade_item_update($oublog, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->dirroot . '/mod/oublog/locallib.php');
    // Use 'grade' or 'scale' depends upon 'grading'.
    if ($oublog->grading == OUBLOG_USE_RATING) {
        $oublogscale = $oublog->scale;
    } else if ($oublog->grading == OUBLOG_NO_GRADING) {
        $oublogscale = 0;
    } else {
        $oublogscale = $oublog->grade;
    }

    $params = array('itemname' => $oublog->name);

    if ($oublogscale > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $oublogscale;
        $params['grademin'] = 0;

    } else if ($oublogscale < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid'] = -$oublogscale;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/oublog', $oublog->course, 'mod',
        'oublog', $oublog->id, 0, $grades, $params);
}

/**
 * Delete grade item for given oublog
 *
 * @param object $oublog object
 * @return object oublog
 */
function oublog_grade_item_delete($oublog) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/oublog', $oublog->course, 'mod',
        'oublog', $oublog->id, 0, null, array('deleted' => 1));
}

/**
 * Returns all other caps used in oublog at module level.
 */
function oublog_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames',
            'report/oualerts:managealerts', 'report/restrictuser:view',
            'report/restrictuser:restrict', 'report/restrictuser:removerestrict');
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the oublog.
 *
 * @param object $mform form passed by reference
 */
function oublog_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'oublogheader', get_string('modulenameplural', 'oublog'));
    $mform->addElement('advcheckbox', 'reset_oublog', get_string('removeblogs', 'oublog'));
}

/**
 * Actual implementation of the reset course functionality, delete all
 * oublog posts.
 *
 * @global object
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function oublog_reset_userdata($data) {
    global $DB;

    $componentstr = get_string('modulenameplural', 'oublog');
    $status = array();

    if (!empty($data->reset_oublog)) {
        // Delete post-related data.
        $postidsql = "SELECT pst.id
                        FROM {oublog_posts} pst
                        JOIN {oublog_instances} ins ON (ins.id = pst.oubloginstancesid)
                        JOIN {oublog} obl ON (obl.id = ins.oublogid)
                       WHERE obl.course = ?";
        $params = array($data->courseid);
        $DB->delete_records_select('oublog_comments', "postid IN ($postidsql)", $params);
        $DB->delete_records_select('oublog_comments_moderated', "postid IN ($postidsql)", $params);
        $DB->delete_records_select('oublog_edits', "postid IN ($postidsql)", $params);

        // Delete instance-related data.
        $insidsql = "SELECT ins.id
                       FROM {oublog_instances} ins
                       JOIN {oublog} obl ON (obl.id = ins.oublogid)
                      WHERE obl.course = ?";
        $DB->delete_records_select('oublog_links', "oubloginstancesid IN ($insidsql)", $params);
        $DB->delete_records_select('oublog_taginstances', "oubloginstancesid IN ($insidsql)", $params);
        $DB->delete_records_select('oublog_posts', "oubloginstancesid IN ($insidsql)", $params);

        $blogidsql = "SELECT obl.id
                        FROM {oublog} obl
                       WHERE obl.course = ?";
        // Delete instances:
        $DB->delete_records_select('oublog_instances', "oublogid IN ($blogidsql)", $params);

        // Reset views:
        $DB->execute("UPDATE {oublog} SET views = 0 WHERE course = ?", $params);

        $rm = new rating_manager();
        $ratingdeloptions = new stdClass;
        $ratingdeloptions->component = 'mod_oublog';
        $ratingdeloptions->ratingarea = 'post';

        // Now get rid of all attachments and ratings.
        $fs = get_file_storage();
        $oublogs = get_coursemodules_in_course('oublog', $data->courseid);
        if ($oublogs) {
            foreach ($oublogs as $oublogid => $unused) {
                if (!$cm = get_coursemodule_from_instance('oublog', $oublogid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);
                $fs->delete_area_files($context->id, 'mod_oublog', 'attachment');
                $fs->delete_area_files($context->id, 'mod_oublog', 'message');
                $fs->delete_area_files($context->id, 'mod_oublog', 'messagecomment');

                $ratingdeloptions->contextid = $context->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        $status[] = array(
                'component' => $componentstr,
                'item' => get_string('removeblogs', 'oublog'),
                'error' => false
        );
    }
    return $status;
}

/**
 * List of view style log actions
 * @return array
 */
function oublog_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List of update style log actions
 * @return array
 */
function oublog_get_post_actions() {
    return array('update', 'add', 'add comment', 'add post', 'edit post');
}

function oublog_oualerts_additional_recipients($type, $id) {
    global $CFG, $USER, $DB;
    require_once($CFG->dirroot . '/mod/oublog/locallib.php');
    $additionalemails = '';

    switch ($type) {
        case 'post':
            $data = oublog_get_blog_from_postid ($id);
            break;
        case 'comment':
            $postid = $DB->get_field('oublog_comments', 'postid', array('id' => $id));
            $data = oublog_get_blog_from_postid($postid);
            break;
        default:
            $data = false;
            break;
    }
    if ($data != false) {
        // Return alert recipients addresses for notification.
        $reportingemails = oublog_get_reportingemail($data);
        if ($reportingemails != false) {
            $additionalemails = explode(',', trim($reportingemails));
        }
    }
    return $additionalemails;
}

function oublog_oualerts_custom_info($item, $id) {
    global $CFG, $USER, $DB;

    require_once($CFG->dirroot . '/mod/oublog/locallib.php');

    switch ($item) {
        case 'post':
            $data =  oublog_get_post($id);
            $itemtitle = get_string('untitledpost', 'oublog');
            break;
        case 'comment':
            $data = $DB->get_record('oublog_comments', array('id' => $id));
            $itemtitle = get_string('untitledcomment', 'oublog');
            break;
        default:
            $data = false;
            break;
    }

    if ($data != false && !empty($data->title)) {
        $itemtitle = $data->title;
    }
    // Return just the title string value of the post or comment.
    return $itemtitle;
}

/**
 * If OU alerts is enabled, and the blog has reporting email setup,
 * if the user has the report/oualerts:managealerts capability for the context then
 * the link to the alerts report should be added.
 *
 * @global object
 * @global object
 */
function oublog_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    global $DB, $CFG, $PAGE;

    if (!$oublog = $DB->get_record("oublog", array("id" => $PAGE->cm->instance))) {
        return;
    }

    include_once($CFG->dirroot.'/mod/oublog/locallib.php');
    if (oublog_oualerts_enabled() && oublog_get_reportingemail($oublog)) {
        if (has_capability('report/oualerts:managealerts',
                context_module::instance($PAGE->cm->id))) {
            $node->add(get_string('oublog_managealerts', 'oublog'),
                    new moodle_url('/report/oualerts/manage.php', array('cmid' => $PAGE->cm->id,
                            'coursename' => $PAGE->course->id, 'contextcourseid' => $PAGE->course->id)),
                            settings_navigation::TYPE_CUSTOM);
        }
    }
}

/**
 * Return rating related permissions
 *
 * @param string $options the context id
 * @return array an associative array of the user's rating permissions
 */
function oublog_rating_permissions($contextid, $component, $ratingarea) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($component != 'mod_oublog' || $ratingarea != 'post') {
        // We don't know about this component/ratingarea so just return null to get the
        // default restrictive permissions.
        return null;
    }
    return array(
        'view' => has_capability('mod/oublog:viewrating', $context),
        'viewany' => has_capability('mod/oublog:viewanyrating', $context),
        'viewall' => has_capability('mod/oublog:viewallratings', $context),
        'rate' => has_capability('mod/oublog:rate', $context)
    );
}

/**
 * Validates a submitted rating
 * @param array $params submitted data
 *            context => object the context in which the rated items exists [required]
 *            component => The component for this module - should always be mod_oublog [required]
 *            ratingarea => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated [required]
 *            scaleid => int the scale from which the user can select a rating. Used for bounds checking. [required]
 *            rating => int the submitted rating
 *            rateduserid => int the id of the user whose items have been rated. NOT the user who submitted the ratings. 0 to update all. [required]
 *            aggregation => int the aggregation method to apply when calculating grades ie RATING_AGGREGATE_AVERAGE [optional]
 * @return boolean true if the rating is valid. Will throw rating_exception if not
 */
function oublog_rating_validate($params) {
    global $DB, $USER;

    // Check the component is mod_oublog.
    if ($params['component'] != 'mod_oublog') {
        throw new rating_exception('invalidcomponent');
    }

    // Check the ratingarea is post (the only rating area in oublog).
    if ($params['ratingarea'] != 'post') {
        throw new rating_exception('invalidratingarea');
    }

    // Check the rateduserid is not the current user .. you can't rate your own posts.
    if ($params['rateduserid'] == $USER->id) {
        throw new rating_exception('nopermissiontorate');
    }

    $oublogsql = "SELECT p.id, o.id as oublogid, o.scale, o.course, p.timeposted,
                          o.assessed, o.assesstimestart, o.assesstimefinish
                    FROM {oublog} o
                    JOIN {oublog_instances} i ON i.oublogid = o.id
                    JOIN {oublog_posts} p ON p.oubloginstancesid = i.id
                   WHERE p.id = :itemid";

    $oublogparams = array('itemid' => $params['itemid']);
    $info = $DB->get_record_sql($oublogsql, $oublogparams);
    if (!$info) {
        // Item doesn't exist.
        throw new rating_exception('invaliditemid');
    }

    if ($info->scale != $params['scaleid']) {
        // The scale being submitted doesnt match the one in the database.
        throw new rating_exception('invalidscaleid');
    }

    // Check that the submitted rating is valid for the scale.

    // Lower limit.
    if ($params['rating'] < 0 && $params['rating'] != RATING_UNSET_RATING) {
        throw new rating_exception('invalidnum');
    }

    // Upper limit.
    if ($info->scale < 0) {
        // Its a custom scale.
        $scalerecord = $DB->get_record('scale', array('id' => -$info->scale));
        if ($scalerecord) {
            $scalearray = explode(',', $scalerecord->scale);
            if ($params['rating'] > count($scalearray)) {
                throw new rating_exception('invalidnum');
            }
        } else {
            throw new rating_exception('invalidscaleid');
        }
    } else if ($params['rating'] > $info->scale) {
        // If its numeric and submitted rating is above maximum.
        throw new rating_exception('invalidnum');
    }

    if (!$info->assessed) {
        // Item isnt approved.
        throw new rating_exception('nopermissiontorate');
    }

    // Check the item we're rating was created in the assessable time window.
    if (!empty($info->assesstimestart) && !empty($info->assesstimefinish)) {
        if ($info->timeposted < $info->assesstimestart || $info->timeposted > $info->assesstimefinish) {
            throw new rating_exception('notavailable');
        }
    }

    $cm = get_coursemodule_from_instance('oublog', $info->oublogid, $info->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id, MUST_EXIST);

    // If the supplied context doesnt match the item's context.
    if ($context->id != $params['context']->id) {
        throw new rating_exception('invalidcontext');
    }

    return true;
}

/**
 * Can the current user see ratings for a given itemid?
 *
 * @param array $params submitted data
 *            contextid => int contextid [required]
 *            component => The component for this module - should always be mod_oublog [required]
 *            ratingarea => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated [required]
 *            scaleid => int scale id [optional]
 * @return bool
 * @throws coding_exception
 * @throws rating_exception
 */
function mod_oublog_rating_can_see_item_ratings($params) {
    global $USER, $CFG;
    require_once(dirname(__FILE__) . '/locallib.php');

    // Check the component is mod_forum.
    if (!isset($params['component']) || $params['component'] != 'mod_oublog') {
        throw new rating_exception('invalidcomponent');
    }

    // Check the ratingarea is post (the only rating area in forum).
    if (!isset($params['ratingarea']) || $params['ratingarea'] != 'post') {
        throw new rating_exception('invalidratingarea');
    }

    if (!isset($params['itemid'])) {
        throw new rating_exception('invaliditemid');
    }
    $context = context::instance_by_id($params['contextid']);

    $blog = oublog_get_blog_from_postid($params['itemid']);
    $post = oublog_get_post($params['itemid'], true);

    if (!oublog_can_view_post($post, $USER, $context, $blog->global)) {
        return false;
    }

    if (!has_capability('mod/oublog:viewallratings', $context)) {
        return false;
    }
    return true;
}

/**
 * Update activity grades
 *
 * @global object
 * @global object
 * @param object $oublog
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function oublog_update_grades($oublog, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->dirroot . '/mod/oublog/locallib.php');
    if ($oublog->grading != OUBLOG_USE_RATING) {
        return;
    }
    if (!$oublog->assessed) {
        oublog_grade_item_update($oublog);
    } else if ($grades = oublog_get_user_grades($oublog, $userid)) {
        oublog_grade_item_update($oublog, $grades);
    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        oublog_grade_item_update($oublog, $grade);
    } else {
        oublog_grade_item_update($oublog);
    }
}

/**
 * Return grade for given user or all users.
 *
 * @global object
 * @param object $dataplus
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function oublog_get_user_grades($oublog, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/rating/lib.php');
    require_once($CFG->dirroot . '/mod/oublog/locallib.php');

    $options = new stdClass();
    $options->component = 'mod_oublog';
    $options->ratingarea = 'post';
    $options->modulename = 'oublog';
    $options->moduleid = $oublog->id;
    $options->userid = $userid;
    $options->aggregationmethod = $oublog->assessed;
    $options->scaleid = $oublog->scale;
    $options->cmid = $oublog->cmidnumber;

    // There now follows a lift of get_user_grades() from rating lib
    // but with the requirement for items modified.
    $rm = new rating_manager();

    if (!isset($options->component)) {
        throw new coding_exception(
                'The component option is now a required option when getting user grades from ratings.'
        );
    }
    if (!isset($options->ratingarea)) {
        throw new coding_exception(
                'The ratingarea option is now a required option when getting user grades from ratings.'
        );
    }

    // Going direct to the db for the context id seemed wrong.
    $context = context_module::instance($options->cmid );

    $params = array();
    $params['contextid'] = $context->id;
    $params['component'] = $options->component;
    $params['ratingarea'] = $options->ratingarea;
    $scaleid = $options->scaleid;
    $aggregationstring = $rm->get_aggregation_method($options->aggregationmethod);
    // If userid is not 0 we only want the grade for a single user.
    $singleuserwhere = '';
    if ($options->userid != 0) {
        // Get the grades for the {posts} the user is responsible for.
        $cm = get_coursemodule_from_id('oublog', $oublog->cmidnumber);
        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, 0, $options->userid);
        foreach ($posts as $post) {
            $postids[] = (int)$post->id;
        }
        $params['userid'] = $userid;
        $singleuserwhere = " AND i.userid = :userid";
    }

    $sql = "SELECT u.id as id, u.id AS userid, $aggregationstring(r.rating) AS rawgrade
              FROM {oublog} o
              JOIN {oublog_instances} i ON i.oublogid = o.id
              JOIN {oublog_posts} p ON p.oubloginstancesid = i.id
              JOIN {rating} r ON r.itemid = p.id
              JOIN {user} u ON i.userid = u.id
             WHERE r.contextid = :contextid
                   AND r.component = :component
                   AND r.ratingarea = :ratingarea
                   $singleuserwhere
          GROUP BY u.id";

    $results = $DB->get_records_sql($sql, $params);

    if ($results) {
        $scale = null;
        $max = 0;
        if ($options->scaleid >= 0) {
            // Numeric.
            $max = $options->scaleid;
        } else {
            // Custom scales.
            $scale = $DB->get_record('scale', array('id' => -$options->scaleid));
            if ($scale) {
                $scale = explode(',', $scale->scale);
                $max = count($scale);
            } else {
                debugging(
                    'rating_manager::get_user_grades() received a scale ID that doesnt exist'
                );
            }
        }

        // It could throw off the grading if count and sum returned a rawgrade higher than scale
        // so to prevent it we review the results and ensure that rawgrade does not exceed
        // the scale, if it does we set rawgrade = scale (i.e. full credit).
        foreach ($results as $rid => $result) {
            if ($options->scaleid >= 0) {
                // Numeric.
                if ($result->rawgrade > $options->scaleid) {
                    $results[$rid]->rawgrade = $options->scaleid;
                }
            } else {
                // Scales.
                if (!empty($scale) && $result->rawgrade > $max) {
                    $results[$rid]->rawgrade = $max;
                }
            }
        }
    }
    return $results;
}
