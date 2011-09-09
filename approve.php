<?php
// Script used to approve moderated comments. This can be called in two ways;
// from email (which is a GET request) and from the web (which is POST).
require_once('../../config.php');
require_once('locallib.php');

// Shared parameter
$mcommentid = required_param('mcomment', PARAM_INT);

// Parameters for each type
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $email = true;
    // Check request parameters from email
    $key = required_param('key', PARAM_ALPHANUM);
    $approve = required_param('approve', PARAM_INT) ? true : false;
} else {
    $email = false;
    // Check request parameters from web
    require_sesskey();
    if (optional_param('bapprove', false, PARAM_TEXT)) {
        $approve = true;
    } else {
        required_param('breject', PARAM_TEXT); // Sanity check
        $approve = false;
    }
    $redirectlower = optional_param('last', 0, PARAM_INT) ? false : true;
}

// Load comment and check it
if (!($mcomment = $DB->get_record('oublog_comments_moderated', array('id'=> $mcommentid)))) {
    print_error('invalidrequest', 'error');
}

// Use post page for continue on error messages
$backlink = $CFG->wwwroot . '/mod/oublog/viewpost.php?post=' .
        $mcomment->postid;

// Load post, blog, etc
if (!$post = oublog_get_post($mcomment->postid, false)) {
    print_error('error_unspecified', 'oublog', $backlink, 'A1');
}
if (!($oublog = oublog_get_blog_from_postid($post->id))) {
    print_error('error_unspecified', 'oublog', $backlink, 'A2');
}
if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
    print_error('invalidcoursemodule', 'error', $backlink);
}
if (!$course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf', 'error', $backlink);
}

// Check state
if ($mcomment->approval) {
    print_error('error_alreadyapproved', 'oublog', $backlink);
}
if ($email && $key !== $mcomment->secretkey) {
    print_error('error_wrongkey', 'oublog', $backlink);
}

// Require login, it to be your own post, and commenting permission
require_login($course, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);
if ($USER->id !== $post->userid ||
        !oublog_can_view_post($post, $USER, $context, $oublog->global) ||
        !oublog_can_comment($cm, $oublog, $post)) {
    print_error('accessdenied', 'oublog', $backlink);
}

// The post must (still) allow public comments
if ($post->allowcomments < OUBLOG_COMMENTS_ALLOWPUBLIC ||
    $oublog->allowcomments < OUBLOG_COMMENTS_ALLOWPUBLIC) {
    print_error('error_moderatednotallowed', 'oublog', $backlink);
}

// OK they are actually allowed to approve / reject this
if (!oublog_approve_comment($mcomment, $approve)) {
    print_error('error_unspecified', 'oublog', 'A5', $backlink);
}

// Redirect back to view post
$target = 'viewpost.php?post=' . $post->id;
if (!$email && $redirectlower) {
    $target .= '#awaiting';
}
redirect($target);
