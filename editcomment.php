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
 * This page allows a user to add and edit blog comments
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */
require_once("../../config.php");
require_once("locallib.php");
require_once('comment_form.php');

define('OUBLOG_CONFIRMED_COOKIE', 'OUBLOG_REALPERSON');

$blog = required_param('blog', PARAM_INT);              // Blog ID
$postid = required_param('post', PARAM_INT);            // Post ID for editing
$commentid = optional_param('comment', 0, PARAM_INT);   // Comment ID for editing

if (!$oublog = $DB->get_record("oublog", array("id"=>$blog))) {
    print_error('invalidblog', 'oublog');
}
if (!$cm = get_coursemodule_from_instance('oublog', $blog)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record("course", array("id"=>$oublog->course))) {
    print_error('coursemisconf');
}
if (!$post = oublog_get_post($postid)) {
    print_error('invalidpost', 'oublog');
}
if (!$oubloginstance = $DB->get_record('oublog_instances', array('id'=>$post->oubloginstancesid))) {
    print_error('invalidblog', 'oublog');
}
$url = new moodle_url('/mod/oublog/editcomment.php', array('blog'=>$blog, 'post'=>$postid, 'comment'=>$commentid));
$PAGE->set_url($url);

// Check security.
$context = context_module::instance($cm->id);

oublog_check_view_permissions($oublog, $context, $cm);
$post->userid=$oubloginstance->userid; // oublog_can_view_post needs this
if (!oublog_can_view_post($post, $USER, $context, $oublog->global)) {
    print_error('accessdenied', 'oublog');
}

oublog_get_activity_groupmode($cm, $course);
if (!oublog_can_comment($cm, $oublog, $post)) {
    print_error('accessdenied', 'oublog');
}

if ($oublog->allowcomments == OUBLOG_COMMENTS_PREVENT || $post->allowcomments == OUBLOG_COMMENTS_PREVENT) {
    print_error('commentsnotallowed', 'oublog');
}

$viewurl = 'viewpost.php?post='.$post->id;
if ($oublog->global) {
    $blogtype = 'personal';
    if (!$oubloguser = $DB->get_record('user', array('id'=>$oubloginstance->userid))) {
        print_error('invaliduserid');
    }
} else {
    $blogtype = 'course';
}

$renderer = $PAGE->get_renderer('mod_oublog');

// Get strings.
$stroublogs  = get_string('modulenameplural', 'oublog');
$stroublog   = get_string('modulename', 'oublog');
$straddcomment  = get_string('newcomment', 'oublog');

$moderated = !(isloggedin() && !isguestuser());
$confirmed = isset($_COOKIE[OUBLOG_CONFIRMED_COOKIE]) &&
        $_COOKIE[OUBLOG_CONFIRMED_COOKIE] == get_string(
            'moderated_confirmvalue', 'oublog');
$mform = new mod_oublog_comment_form('editcomment.php', array(
        'maxvisibility' => $oublog->maxvisibility,
        'edit' => !empty($commentid),
        'blogid' => $blog,
        'postid' => $postid,
        'moderated' => $moderated,
        'confirmed' => $confirmed,
        'maxbytes' => $oublog->maxbytes,
        'postrender' => $renderer->render_post($cm, $oublog, $post, $url, $blogtype, false, false, false, false, false, true),
        ));

if ($mform->is_cancelled()) {
    redirect($viewurl);
    exit;
}
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($course->fullname));

if (!$comment = $mform->get_data()) {

    $comment = new stdClass;
    $comment->general = $straddcomment;
    $comment->blog = $blog;
    $comment->post = $postid;
    $mform->set_data($comment);

    // Print the header

    if ($blogtype == 'personal') {
        oublog_build_navigation($oublog, $oubloginstance, $oubloguser);
    } else {
        oublog_build_navigation($oublog, $oubloginstance, null);
        $url = new moodle_url("$CFG->wwwroot/course/mod.php", array('update' => $cm->id, 'return' => true, 'sesskey' => sesskey()));
    }

    oublog_get_post_extranav($post);
    $PAGE->navbar->add($comment->general);
    echo $OUTPUT->header();


    echo '<br />';
    $mform->display();

    echo $OUTPUT->footer();

} else {
    // Prepare comment for database
    unset($comment->id);
    $comment->userid = $USER->id;
    $comment->postid = $postid;

    // Special behaviour for moderated users
    if ($moderated) {
        // Check IP address
        if (oublog_too_many_comments_from_ip()) {
            print_error('error_toomanycomments', 'oublog');
        }

        // Set the confirmed cookie if they haven't got it yet
        if (!$confirmed) {
            setcookie(OUBLOG_CONFIRMED_COOKIE, $comment->confirm,
                    time() + 365 * 24 * 3600); // Expire in 1 year
        }

        if (!oublog_add_comment_moderated($oublog, $oubloginstance, $post, $comment)) {
            print_error('couldnotaddcomment', 'oublog');
        }
        $approvaltime = oublog_get_typical_approval_time($post->userid);

        oublog_build_navigation($oublog, $oubloginstance, isset($oubloguser) ? $oubloguser : null);
        oublog_get_post_extranav($post);
        $PAGE->navbar->add(get_string('moderated_submitted', 'oublog'));
        echo $OUTPUT->header();
        notice(get_string('moderated_addedcomment', 'oublog') .
                ($approvaltime ? ' ' .
                    get_string('moderated_typicaltime', 'oublog', $approvaltime)
                : ''), 'viewpost.php?post=' . $postid, $course);
        // Does not continue.
    }

    $comment->userid = $USER->id;

    if (!oublog_add_comment($course, $cm, $oublog, $comment)) {
        print_error('couldnotaddcomment', 'oublog');
    }

    // Log add comment event.
    $params = array(
            'context' => $context,
            'objectid' => $comment->id,
            'other' => array(
                'oublogid' => $oublog->id,
                'postid' => $comment->postid,
        )
    );

    $event = \mod_oublog\event\comment_created::create($params);
    $event->trigger();

    redirect($viewurl);
}
