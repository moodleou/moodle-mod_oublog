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
 * This page generates blog RSS and ATOM feeds
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */
require_once("../../config.php");
require_once("locallib.php");
require_once($CFG->libdir.'/rsslib.php');
require_once('atomlib.php');

$format             = required_param('format', PARAM_TEXT);
$blogid             = optional_param('blog', 0, PARAM_INT);
$bloginstancesid    = optional_param('bloginstance', 0, PARAM_INT);
$comments           = optional_param('comments', 0, PARAM_INT);
$postid             = optional_param('post', 0, PARAM_INT);
$loggedin           = optional_param('loggedin', '', PARAM_TEXT);
$full               = optional_param('full', '', PARAM_TEXT);
$viewer             = optional_param('viewer', 0, PARAM_INT);
$groupid            = optional_param('group', 0, PARAM_INT);
$individualid       = optional_param('individual', 0, PARAM_INT);
$childblogid        = optional_param('childblog', 0, PARAM_INT);

$url = new moodle_url('/mod/oublog/feed.php', array('format'=>$format, 'blog'=>$blogid,
        'bloginstance'=>$bloginstancesid, 'post'=>$postid));
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
// Validate Parameters.
$format = strtolower($format);

if (empty($CFG->enablerssfeeds)) {
    print_error('feedsnotenabled', 'oublog');
}
if ($format != 'atom' && $format != 'rss') {
    print_error('invalidformat', 'oublog');
}
if (!$blogid && !$bloginstancesid && !$postid) {
    print_error('missingrequiredfield');
}
if (($loggedin || $full) && !$viewer) {
    print_error('missingrequiredfield');
}
if ($groupid && !$viewer) {
    print_error('missingrequiredfield');
}

if (isset($bloginstancesid) && $bloginstancesid!='all') {
    $bloginstance = $DB->get_record('oublog_instances', array('id'=>$bloginstancesid));
    $blog         = $DB->get_record('oublog', array('id'=>$bloginstance->oublogid));
} else if ($blogid) {
    $blog = $DB->get_record('oublog', array('id'=>$blogid));

} else if ($postid) {
    $post         = $DB->get_record('oublog_posts', array('id'=>$postid));
    $bloginstance = $DB->get_record('oublog_instances', array('id'=>$post->oubloginstancesid));
    $blog         = $DB->get_record('oublog', array('id'=>$bloginstance->oublogid));
}
if (!isset($blog->id) || !$cm = get_coursemodule_from_instance('oublog', $blog->id)) {
    print_error('invalidcoursemodule');
}
// Get child blog.
if (!empty($childblogid)) {
    $childblog = $DB->get_record('oublog', array('id' => $childblogid), '*', MUST_EXIST);
    // Get cm of child blog.
    if (!$cmchildblog = get_coursemodule_from_instance('oublog', $childblog->id)) {
        print_error('invalidcoursemodule');
    }
}
$feedcm = !empty($cmchildblog) ? $cmchildblog : $cm;
$feedblog = !empty($childblog) ? $childblog : $blog;
// Work out link for ordinary web page equivalent to requested feed
if ($feedblog->global) {
    if ($bloginstancesid == 'all') {
        $url = $CFG->wwwroot . '/mod/oublog/allposts.php';
    } else {
        $url = $CFG->wwwroot . '/mod/oublog/view.php?user=' .
            $bloginstance->userid;
    }
    $groupmode = 0;
} else {
    $url = $CFG->wwwroot . '/mod/oublog/view.php?id=' . $cm->id .
        ($groupid ? '&group=' . $groupid : '') .
        ($individualid ? '&individual=' . $individualid : '');
    if (!($course = $DB->get_record('course', array('id'=>$feedcm->course)))) {
        print_error('coursemisconf');
    }
    $groupmode = oublog_get_activity_groupmode($feedcm, $course);
}

// Check browser compatibility.
if (core_useragent::check_browser_version('MSIE', 0) || core_useragent::check_browser_version('Firefox', 0)) {
    if (!core_useragent::check_browser_version('MSIE', '7') && !core_useragent::check_browser_version('Firefox', '2')) {
        if ($feedblog->global) {
            $url='view.php?user='.$bloginstance->userid;
        } else {
            $url='view.php?id='.$cm->id.($groupid ? '&group='.$groupid : '');
        }
        print_error('unsupportedbrowser', 'oublog', $url);
    }
}
// Determine if feed has changed since the if-modified-since HTTP header and exit if it hasn't.
// Override default Moodle behaviour which prevents all caching (ouch).
header('Cache-Control:');
header('Pragma: ');
header('Expires: ');
if ($mtime = oublog_feed_last_changed($blogid, $bloginstancesid, $postid, $comments)) {
    $mtimegm = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
    header("Last-Modified: $mtimegm");
}
if ($mtime && isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $iftime = strtotime(preg_replace('/;.*$/', '',
        $_SERVER['HTTP_IF_MODIFIED_SINCE']));
    if ($mtime <= $iftime) {
        header("HTTP/1.0 304 Not Modified");
        exit;
    }
}

if ($feedblog->global && $bloginstancesid != 'all') {
    $accesstoken = $bloginstance->accesstoken;
} else {
    $accesstoken = $feedblog->accesstoken;
}

if ($full) {
    // We had an issue where the system leaked 'full' view tokens to users
    // who should not get them. To resolve this, I changed the view tokens
    // to use 'v2' in the hash
    if ($full == md5($accesstoken.$viewer.OUBLOG_VISIBILITY_COURSEUSER.'v2') && $user =
            $DB->get_record('user', array('id'=>$viewer))) {
        $allowedvisibility = OUBLOG_VISIBILITY_COURSEUSER;
    } else if ($full == md5($accesstoken.$viewer.OUBLOG_VISIBILITY_COURSEUSER) && $user =
            $DB->get_record('user', array('id'=>$viewer))) {
        // This is the old token. Ooops. We know that at least users were
        // logged in, so they get that version...
        $allowedvisibility = OUBLOG_VISIBILITY_LOGGEDINUSER;
        if (!$feedblog->global) {
            // For course blogs, security was actually correct, so let's
            // keep allowing them to read the whole blog
            $allowedvisibility = OUBLOG_VISIBILITY_COURSEUSER;
        }
    } else {
        print_error('nopermissiontoshow');
    }
} else if ($loggedin) {
    if ($loggedin == md5($accesstoken.$viewer.OUBLOG_VISIBILITY_LOGGEDINUSER) && $user =
            $DB->get_record('user', array('id'=>$viewer))) {
        $allowedvisibility = OUBLOG_VISIBILITY_LOGGEDINUSER;
    } else {
        print_error('nopermissiontoshow');
    }
} else {
    $allowedvisibility = OUBLOG_VISIBILITY_PUBLIC;
    $user = $USER;
}

// Check individual
if ($feedblog->individual) {
    if (!oublog_individual_has_permissions($feedcm, $feedblog, $groupid, $individualid, $user->id)) {
         print_error('nopermissiontoshow');
    }
}
// Check separate groups
if ($groupmode == SEPARATEGROUPS) {
    // If you are a member of the group and viewing a specified group then
    // you are OK
    if ($groupid && groups_is_member($groupid, $user->id)) {
        // Group members are OK
    } else {
        // Must have access all groups
        require_capability('moodle/site:accessallgroups',
                context_module::instance($feedcm->id), $user->id);
    }
}

// Get data for feed in a standard form.
if ($comments) {
    $feeddata = oublog_get_feed_comments($blogid, $bloginstancesid, $postid, $user,
            $allowedvisibility, $groupid, $feedcm, $blog, $individualid);
    $feedname = strip_tags($feedblog->name) . ': ' . get_string('commentsfeed', 'oublog');
    $feedsummary='';
} else {
    $feeddata = oublog_get_feed_posts($blogid,
        isset($bloginstance) ? $bloginstance : null, $user,
        $allowedvisibility, $groupid, $feedcm, $blog, $individualid);
    $feedname=strip_tags($feedblog->name);
    if ($bloginstancesid=='all') {
        $feedsummary=strip_tags($feedblog->intro);
    } else {
        $feedsummary=strip_tags($bloginstance->summary);
    }
}

// Generate feed in RSS or ATOM format.
if ($format == 'rss') {
    header('Content-type: application/rss+xml');
    echo rss_standard_header($feedname, $url, $feedsummary);
    echo rss_add_items($feeddata);
    echo rss_standard_footer();
} else {
    header('Content-type: application/atom+xml');
    $updated=count($feeddata)==0 ? time() : reset($feeddata)->pubdate;
    echo atom_standard_header($FULLME, $FULLME, $updated, $feedname, $feedsummary);
    echo atom_add_items($feeddata);
    echo atom_standard_footer();
}
