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
 * This page prints a particular post from an oublog, including any comments.
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */

require_once("../../config.php");
require_once("locallib.php");
require_once($CFG->dirroot . '/rating/lib.php');

$postid = required_param('post', PARAM_INT);       // Post id.
// Support redirects across systems - find post by username and time created.
$username = optional_param('u', '', PARAM_USERNAME);
$time = optional_param('time', 0, PARAM_INT);
if ($postid == 0 && !empty($username) && $time != 0) {
    // Search DB for an existing post (Personal blog only).
    $redirectto = new moodle_url('/mod/oublog/view.php', array('u' => $username));
    if (!$user = $DB->get_record('user', array('username' => $username), 'id')) {
        print_error('invaliduser');
    }
    if (!list($oublog, $oubloginstance) = oublog_get_personal_blog($user->id)) {
        // We redirect on error as system can create blog on access.
        redirect($redirectto);
    }
    // Get any posts matching user and time (If more than one just get first record).
    if (!$post = $DB->get_record('oublog_posts', array('oubloginstancesid' => $oubloginstance->id,
            'timeposted' => $time), 'id', IGNORE_MULTIPLE)) {
        // Go to their blog home page if no post found.
        redirect($redirectto);
    }
    $postid = $post->id;
}

// This query based on the post id is so that we can get the blog etc to
// check permissions before calling oublog_get_post.
if (!$oublog = oublog_get_blog_from_postid($postid)) {
    print_error('invalidpost', 'oublog');
}

if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

$url = new moodle_url('/mod/oublog/viewpost.php', array('post' => $postid));
$PAGE->set_url($url);

$context = context_module::instance($cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

$oublogoutput = $PAGE->get_renderer('mod_oublog');

// Check security.
$canmanageposts    = has_capability('mod/oublog:manageposts', $context);
$canmanagecomments = has_capability('mod/oublog:managecomments', $context);
$canaudit          = has_capability('mod/oublog:audit', $context);

if (!$post = oublog_get_post($postid, $canaudit)) {
    print_error('invalidpost', 'oublog');
}

if (!$oubloginstance = $DB->get_record('oublog_instances',
        array('id' => $post->oubloginstancesid))) {
    print_error('invalidblog', 'oublog');
}

if (!isloggedin() && $post->visibility != OUBLOG_VISIBILITY_PUBLIC) {
    // User should be logged-in, so log them in automatically.
    redirect('/mod/oublog/bloglogin.php?returnurl=' . urlencode($url->out()));
}

if (!oublog_can_view_post($post, $USER, $context, $oublog->global)) {
    print_error('accessdenied', 'oublog');
}

// Get strings.
$stroublogs     = get_string('modulenameplural', 'oublog');
$stroublog      = get_string('modulename', 'oublog');
$strdelete      = get_string('delete', 'oublog');
$strtags        = get_string('tags', 'oublog');
$strcomments    = get_string('comments', 'oublog');
$strlinks       = get_string('links', 'oublog');
$strfeeds       = get_string('feeds', 'oublog');

// Set-up groups.
$groupmode = oublog_get_activity_groupmode($cm, $course);
$currentgroup = oublog_get_activity_group($cm, true);

// Check permissions for group (of post).
if ($groupmode == VISIBLEGROUPS && !groups_is_member($post->groupid) &&
        !has_capability('moodle/site:accessallgroups', $context)) {
    $canpost = false;
    $canmanageposts = false;
    $canaudit = false;
}

// Print the header.
$strblogsearch = get_string('searchthisblog', 'oublog', oublog_get_displayname($oublog));
if ($oublog->global) {
    $blogtype = 'personal';
    $returnurl = 'view.php?user=' . $oubloginstance->userid;
    $blogname = format_string($oubloginstance->name);

    if (!$oubloguser = $DB->get_record('user', array('id' => $oubloginstance->userid))) {
        print_error('invaliduserid');
    }

    $PAGE->navbar->add(fullname($oubloguser), new moodle_url("/user/view.php",
            array('id' => $oubloguser->id)));
    $PAGE->navbar->add($blogname, new moodle_url("/mod/oublog/view.php",
            array('user' => $oubloginstance->userid)));

    $url = new moodle_url("$CFG->wwwroot/course/mod.php", array('update' => $cm->id, 'return' => true, 'sesskey' => sesskey()));
    $buttontext = oublog_get_search_form('user', $oubloguser->id, $strblogsearch);
} else {
    $blogtype = 'course';
    $returnurl = 'view.php?id='.$cm->id;
    $blogname = $oublog->name;
    $url = new moodle_url("$CFG->wwwroot/course/mod.php", array('update' => $cm->id, 'return' => true, 'sesskey' => sesskey()));
    $buttontext = oublog_get_search_form('id', $cm->id, $strblogsearch);
}

// Log view post event.
$params = array(
        'context' => $context,
        'objectid' => $post->id,
        'other' => array(
            'oublogid' => $oublog->id
   )
);
$event = \mod_oublog\event\post_viewed::create($params);
$event->add_record_snapshot('oublog_posts', $post);
$event->trigger();

$CFG->additionalhtmlhead .= oublog_get_meta_tags($oublog, $oubloginstance, $currentgroup, $cm, $post);
$PAGE->set_title(format_string($post->title));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_button($buttontext);
oublog_get_post_extranav($post, false);

$oublogoutput->pre_display($cm, $oublog, 'viewpost');

echo $OUTPUT->header();
// Print the main part of the page.
echo '<div class="oublog-topofpage"></div>';

// Print blog posts.
echo '<div id="middle-column" >';

echo $oublogoutput->render_header($cm, $oublog, 'viewpost');

echo '<div class="oublog-post-commented">';

// Load ratings.
if ($oublog->assessed != RATING_AGGREGATE_NONE) {
    $ratingoptions = new stdClass();
    $ratingoptions->context = $context;
    $ratingoptions->component = 'mod_oublog';
    $ratingoptions->ratingarea = 'post';
    $ratingoptions->items = array('post' => $post);
    $ratingoptions->aggregate = $oublog->assessed;// The aggregation method.
    $ratingoptions->scaleid = $oublog->scale;
    $ratingoptions->userid = $USER->id;
    $ratingoptions->assesstimestart = $oublog->assesstimestart;
    $ratingoptions->assesstimefinish = $oublog->assesstimefinish;

    $rm = new rating_manager();
    $postswithratings = $rm->get_ratings($ratingoptions);
    $post = $postswithratings['post'];
}

echo $oublogoutput->render_post($cm, $oublog, $post, $returnurl, $blogtype, $canmanageposts,
        $canaudit, false, false);

if (!empty($post->comments)) {
    // Code extracted to new renderer function.
    echo $oublogoutput->render_comments($post, $oublog, $canaudit, $canmanagecomments, false, $cm);
}
echo '</div>';
// If it is your own post, then see if there are any moderated comments -
// for security reasons, you must also be allowed to comment on the post in
// order to moderate it (because 'approving' a comment is basically equivalent
// to commenting).
// Logic should be if public comments are allowed and,
// either post user and can comment, or can manage comments.
$includeset = $canaudit;
if ($post->allowcomments >= OUBLOG_COMMENTS_ALLOWPUBLIC &&
        (($post->userid == $USER->id && oublog_can_comment($cm, $oublog, $post)) || $canmanagecomments)) {
    // Also, if this is a personal global blog include accepted/rejected comments.
    if ($oublog->global) {
        $includeset = true;
    }
    $moderated = oublog_get_moderated_comments($oublog, $post, $includeset);
    $display = array();
    foreach ($moderated as $comment) {
        if ($comment->approval != OUBLOG_MODERATED_APPROVED) {
            $display[] = $comment;
        }
    }
    if (count($display)) {
        print '<h2 id="awaiting">' . get_string('moderated_awaiting', 'oublog') . '</h2>';
        print '<p>' . get_string('moderated_awaitingnote', 'oublog') . '</p>';
        print '<div class="oublog-awaiting">';
        foreach ($display as $comment) {
            if ($comment->approval == OUBLOG_MODERATED_APPROVED) {
                continue; // Don't bother showing approved comments as they
                          // appear above.
            }

            $extraclasses = '';
            $extramessage = '';
            if ($comment->approval == OUBLOG_MODERATED_REJECTED) {
                $extraclasses = 'oublog-rejected';
                $extramessage = '<div class="oublog-rejected-info">' .
                        get_string('moderated_rejectedon', 'oublog',
                            oublog_date($comment->timeset)) . ' </div>';
            }
            $extraclasses .= ' oublog-hasuserpic';

            // Start of comment.
            print '<div class="oublog-comment ' . $extraclasses . '">' .
                    $extramessage;

            // Title.
            if (trim(format_string($comment->title)) !== '') {
                print '<h3 class="oublog-comment-title">' .
                        format_string($comment->title) . '</h3>';
            }

            // Date and author.
            print '<div class="oublog-comment-date">' .
                    oublog_date($comment->timeposted) . ' </div>';
            print '<div class="oublog-postedby">' .
                    get_string('moderated_postername', 'oublog',
                        s($comment->authorname)) .
                    ($canaudit ? ' (' . s($comment->authorip) . ')' : '') . '</div>';

            print '<div class="oublog-comment-content">' .
                    format_text($comment->message, FORMAT_MOODLE) . '</div>';

            // You can only approve/reject it once; and we don't let admins
            // approve/reject (because there's no way of tracking who did it
            // and it displays the post owner as having approved it)...
            if ($comment->approval == OUBLOG_MODERATED_UNSET &&
                    $post->userid == $USER->id) {
                print '<form action="approve.php" method="post"><div>' .
                        '<input type="hidden" name="sesskey" value="' . sesskey() . '" />' .
                        '<input type="hidden" name="mcomment" value="' . $comment->id . '" />';
                if (count($moderated) == 1) {
                    // Track if this is the last comment so we can jump to the
                    // top of the page instead of the moderating bit.
                    print '<input type="hidden" name="last" value="1" />';
                }
                print '<input type="submit" name="bapprove" value="' .
                    get_string('moderated_approve', 'oublog') . '" /> ';
                print '<input type="submit" name="breject" value="' .
                    get_string('moderated_reject', 'oublog') . '" />';
                print '</div></form>';
            }

            // End of comment.
            print '</div>';
        }
        // End of comments awaiting approval.
        print '</div>';
    }
}


echo '</div>';

if ($oublog->global) {
    echo $oublogoutput->get_link_back_to_oublog($blogname, $oubloginstance->userid, true);
} else {
    echo $oublogoutput->get_link_back_to_oublog($blogname, $cm->id);
}

// Finish the page.
echo '<div class="clearfix"></div>';
echo $OUTPUT->footer();
