<?php
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
    // Generate an accesstoken
    $oublog->accesstoken = md5(uniqid(rand(), true));

    if (!$oublog->id = $DB->insert_record('oublog', $oublog)) {
        return(false);
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

    if (!$blog = $DB->get_record('oublog', array('id'=>$oublog->id))) {
        return(false);
    }

    if (!$DB->update_record('oublog', $oublog)) {
        return(false);
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
        print_error('deleteglobalblog','oublog');
    }

    if ($instances = $DB->get_records('oublog_instances', array('oublogid'=>$oublog->id))) {

        foreach ($instances as $oubloginstancesid => $bloginstance) {
            // tags
            $DB->delete_records('oublog_taginstances', array('oubloginstancesid'=>$oubloginstancesid));

            if ($posts = $DB->get_records('oublog_posts', array('oubloginstancesid'=>$oubloginstancesid))) {

                foreach($posts as $postid => $post) {
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
    if(oublog_search_installed()) {
        $moduleid=$DB->get_field('modules','id',array('name'=>'oublog'));
        $cm=$DB->get_record('course_modules',array('module'=>$moduleid,'instance'=>$oublog->id));
        if(!$cm) {
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

    if ($postinfo = $DB->get_record_sql($sql, array($user->id,$mod->instance))) {
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
        foreach($posts as $post) {
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
    foreach($rs as $blog) {
        if (!isset($modinfo->instances['oublog'][$blog->oublogid])) {
            // not visible
            continue;
        }
        $cm = $modinfo->instances['oublog'][$blog->oublogid];
        if (!$cm->uservisible) {
            continue;
        }
        if (!has_capability('mod/oublog:view', get_context_instance(CONTEXT_MODULE, $cm->id))) {
            continue;
        }
        if (!has_capability('mod/oublog:view', get_context_instance(CONTEXT_USER, $blog->userid))) {
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
function oublog_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0)  {
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



    foreach($rs as $blog) {
        if (!isset($modinfo->instances['oublog'][$blog->oublogid])) {
            // not visible
            continue;
        }
        $cm = $modinfo->instances['oublog'][$blog->oublogid];
        if (!$cm->uservisible) {
            continue;
        }
        if (!has_capability('mod/oublog:view', get_context_instance(CONTEXT_MODULE, $cm->id))) {
            continue;
        }
        if (!has_capability('mod/oublog:view', get_context_instance(CONTEXT_USER, $blog->userid))) {
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


        $tmpactivity = new object();

        $tmpactivity->type         = 'oublog';
        $tmpactivity->cmid         = $cm->id;
        $tmpactivity->name         = $blog->title;
        $tmpactivity->sectionnum   = $cm->sectionnum;
        $tmpactivity->timeposted    = $blog->timeposted;

        $tmpactivity->content = new object();
        $tmpactivity->content->postid   = $blog->postid;
        $tmpactivity->content->title    = format_string($blog->title);

        $tmpactivity->user = new object();
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
 * Function to be run periodically according to the moodle cron
 * This function runs every 4 hours.
 *
 * @uses $CFG
 * @return boolean true on success, false on failure.
 **/
function oublog_cron() {
    global $DB;

    // Delete outdated (> 30 days) moderated comments
    $outofdate = time() - 30 * 24 * 3600;
    $DB->delete_records_select('oublog_comments_moderated', "timeposted < ?", array($outofdate));

    return true;
}



/**
 * Execute post-install custom actions for the module
 *
 * @return boolean true if success, false on error
 */
function oublog_post_install() {
    global $DB,$CFG;
    require_once('locallib.php');

    /// Setup the global blog
    $oublog = new stdClass;
    $oublog->course = SITEID;
    $oublog->name = 'Personal Blogs';
    $oublog->summary = '';
    $oublog->accesstoken = md5(uniqid(rand(), true));
    $oublog->maxvisibility = OUBLOG_VISIBILITY_PUBLIC;
    $oublog->global = 1;
    $oublog->allowcomments = OUBLOG_COMMENTS_ALLOWPUBLIC;

    if (!$oublog->id = $DB->insert_record('oublog', $oublog)) {
        return(false);
    }

    $mod = new stdClass;
    $mod->course   = SITEID;
    $mod->module   = $DB->get_field('modules', 'id', array('name'=>'oublog'));
    $mod->instance = $oublog->id;
    $mod->visible  = 1;
    $mod->visibleold  = 0;
    $mod->section = 1;


    if (!$cm = add_course_module($mod)) {
        return(true);
    }
    $mod->id = $cm;
    $mod->coursemodule = $cm;

    $mod->section = add_mod_to_section($mod);

    $DB->update_record('course_modules', $mod);

    set_config('oublogsetup', true);

    return(true);
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
    if(!($cm=$DB->get_record('course_modules',array('id'=>$document->coursemoduleid)))) {
        return false;
    }
    if(!($oublog=$DB->get_record('oublog',array('id'=>$cm->instance)))) {
        return false;
    }
    if(!($post=$DB->get_record_sql("
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
    if($oublog->global) {
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
    $taglist=oublog_get_post_tags($post,true);
    if(count($taglist)!=0) {
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
function oublog_ousearch_update_all($feedback=false,$courseid=0) {
    global $CFG,$DB;
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
    if($feedback) {
        print '<li><strong>'.count($coursemodules).'</strong> instances to process.</li>';
        $dotcount=0;
    }
    $posts=0; $instances=0;
    foreach($coursemodules as $coursemodule) {

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
            oublog_search_update($post,$coursemodule);

            // Add to count and do user feedback every 100 posts
            $posts++;
            if($feedback && ($posts%100)==0) {
                if($dotcount==0) {
                    print '<li>';
                }
                print '.';
                $dotcount++;
                if($dotcount==20 || $count==count($coursemodules)) {
                    print "done $posts posts ($instances instances)</li>";
                    $dotcount=0;
                }
                flush();
            }
        }
        $rs->close();

        $instances++;
    }
    if($feedback && ($dotcount!=0 || $posts<100)) {
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
        case FEATURE_MOD_INTRO: return false;
        case FEATURE_GROUPINGS: return true;
        case FEATURE_GROUPS: return true;
        case FEATURE_GROUPMEMBERSONLY: return true;
        case FEATURE_GRADE_HAS_GRADE: return true;
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
function oublog_get_completion_state($course,$cm,$userid,$type) {
    global $DB;

    // Get oublog details
    if(!($oublog=$DB->get_record('oublog',array('id'=>$cm->instance)))) {
        throw new Exception("Can't find oublog {$cm->instance}");
    }

    $result=$type; // Default return value

    if($oublog->completionposts) {
        // Count of posts by user
        $value = $oublog->completionposts <= $DB->get_field_sql("
SELECT
    COUNT(1)
FROM
{oublog_instances} i
    INNER JOIN {oublog_posts} p ON i.id=p.oubloginstancesid
WHERE
    i.userid= ? AND i.oublogid=? AND p.deletedby IS NULL", array($userid,$oublog->id));
if($type==COMPLETION_AND) {
    $result=$result && $value;
} else {
    $result=$result || $value;
}
    }
    if($oublog->completioncomments) {
        // Count of comments by user (on posts by any user)
        $value = $oublog->completioncomments <= $DB->get_field_sql("
SELECT
    COUNT(1)
FROM
{oublog_comments} c
    INNER JOIN {oublog_posts} p ON p.id=c.postid
    INNER JOIN {oublog_instances} i ON i.id=p.oubloginstancesid
WHERE
    c.userid= ? AND i.oublogid= ? AND p.deletedby IS NULL AND c.deletedby IS NULL", array($userid,$oublog->id));
if($type==COMPLETION_AND) {
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
function oublog_print_overview($courses,&$htmlarray){
    global $USER, $CFG, $DB;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$blogs = get_all_instances_in_courses('oublog',$courses)) {
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
    $sql = substr($sql,0,-3); // take off the last OR

    //Ignore comment actions for now, only entries.
    $sql .= ") AND l.module = 'oublog' AND action in('add post','edit post')
      AND userid != ? GROUP BY cmid,l.course,instance";
    $params[] = $USER->id;
    if (!$new = $DB->get_records_sql($sql, $params)) {
        $new = array(); // avoid warnings
    }

    $strblogs = get_string('modulenameplural','oublog');

    $site = get_site();
    if( count( $courses ) == 1 && isset( $courses[$site->id] ) ){
        $strnumrespsince1 = get_string('overviewnumentrylog1','oublog');
        $strnumrespsince = get_string('overviewnumentrylog','oublog');
    }else{
        $strnumrespsince1 = get_string('overviewnumentryvw1','oublog');
        $strnumrespsince = get_string('overviewnumentryvw','oublog');
    }

    //Go through the list of all oublog instances build previously, and check whether
    //they have had any activity.
    foreach ($blogs as $blog) {
        if (array_key_exists($blog->id, $new) && !empty($new[$blog->id])) {
            $count = $new[$blog->id]->count;
            if( $count > 0 ){
                if( $count == 1 ){
                    $strresp = $strnumrespsince1;
                }else{
                    $strresp = $strnumrespsince;
                }

                $str = '<div class="overview oublog"><div class="name">'.
                $strblogs.': <a title="'.$strblogs.'" href="';
                if ($blog->global=='1'){
                    $str .= $CFG->wwwroot.'/mod/oublog/allposts.php">'.$blog->name.'</a></div>';
                } else {
                    $str .= $CFG->wwwroot.'/mod/oublog/view.php?id='.$new[$blog->id]->cmid.'">'.$blog->name.'</a></div>';
                }
                $str .= '<div class="info">';
                $str .= $count.' '.$strresp;
                $str .= '</div></div>';

                if (!array_key_exists($blog->course,$htmlarray)) {
                    $htmlarray[$blog->course] = array();
                }
                if (!array_key_exists('oublog',$htmlarray[$blog->course])) {
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

    $fileareas = array('attachment', 'message', 'edit');
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

    if (!$post = $DB->get_record('oublog_posts', array('id'=>$postid))) {
        return false;
    }
    if (!($oublog = oublog_get_blog_from_postid($post->id))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_oublog/$filearea/$fileid/$relativepath";

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Make sure we're allowed to see it...

    if (!oublog_can_view_post($post, $USER, $context, $oublog->global)) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
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
    global $CFG, $USER;
    require_once($CFG->dirroot . '/mod/oublog/locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }
    $fileareas = array('attachment', 'message', 'edit');
    if (!in_array($filearea, $fileareas)) {
        return null;
    }

    if (!($oublog = oublog_get_blog_from_postid($itemid))) {
        return null;
    }
    // Check if the user is allowed to view the blog
    try {
        oublog_check_view_permissions($oublog, $context, $cm);
    } catch (mod_forumng_exception $e) {
        return null;
    }

    if (!$post = oublog_get_post($itemid)) {
        return null;
    }
    // Check if the user is allowed to view the post
    try {
        if (!oublog_can_view_post($post, $USER, $context, $oublog->global)) {
            return null;
        }
    } catch (mod_forumng_exception $e) {
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
    if (!has_capability('mod/oublog:view',
            get_context_instance(CONTEXT_MODULE,$cm->id))) {
        $cm->uservisible = false;
        $cm->set_available(false);
    }
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
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname' => $oublog->name);

    if ($oublog->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $oublog->grade;
        $params['grademin']  = 0;

    } else if ($oublog->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$oublog->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
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
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames');
}
