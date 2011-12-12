<?php
/**
 * This page allows a user to delete a blog comments
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */
require_once("../../config.php");
require_once("locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$commentid  = required_param('comment', PARAM_INT);    // Comment ID to delete
$confirm = optional_param('confirm', 0, PARAM_INT);    // Confirm that it is ok to delete comment

if (!$comment = $DB->get_record('oublog_comments', array('id'=>$commentid))) {
    print_error('invalidcomment','oublog');
}

if (!$post = oublog_get_post($comment->postid)) {
    print_error("invalidpost",'oublog');
}

if (!$cm = get_coursemodule_from_instance('oublog', $post->oublogid)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (!$oublog = $DB->get_record("oublog", array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}
$url = new moodle_url('/mod/oublog/deletepost.php', array('comment'=>$commentid, 'confirm'=>$confirm));
$PAGE->set_url($url);

/// Check security
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

// You can always delete your own comments, or any comment on your own
// personal blog
if(!($comment->userid==$USER->id ||
    ($oublog->global && $post->userid == $USER->id))) {
    require_capability('mod/oublog:managecomments', $context);
}

if ($oublog->global) {
    $blogtype = 'personal';
    // Get blog user from the oublog_get_post result (to save making an
    // extra query); this is only used to display their name anyhow
    $oubloguser = (object)array('id'=>$post->userid,
        'firstname'=>$post->firstname, 'lastname'=>$post->lastname);
} else {
    $blogtype = 'course';
}
$viewurl = new moodle_url('/mod/oublog/viewpost.php', array('post'=>$post->id));

if (!empty($commentid) && !empty($confirm)) {
    $updatecomment = (object)array(
        'id' => $commentid,
        'deletedby' => $USER->id,
        'timedeleted' => time());
    $DB->update_record('oublog_comments', $updatecomment);

    // Inform completion system, if available
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && ($oublog->completioncomments)) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $comment->userid);
    }

    redirect($viewurl);
    exit;
}

/// Get Strings
$stroublogs  = get_string('modulenameplural', 'oublog');
$stroublog   = get_string('modulename', 'oublog');

/// Print the header
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($course->fullname));
if ($blogtype == 'personal') {
    $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id'=>$oubloguser->id)));
    $PAGE->navbar->add(format_string($oublog->name));
}
echo $OUTPUT->header();
echo $OUTPUT->confirm(get_string('confirmdeletecomment', 'oublog'),
                 new moodle_url('/mod/oublog/deletecomment.php',array('comment'=>$commentid, 'confirm'=>'1')),
                 $viewurl);
