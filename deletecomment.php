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
$cmid = optional_param('cmid', null, PARAM_INT);
$referurl = optional_param('referurl', null, PARAM_LOCALURL);
if (!$comment = $DB->get_record('oublog_comments', array('id'=>$commentid))) {
    print_error('invalidcomment',  'oublog');
}

if (!$post = oublog_get_post($comment->postid)) {
    print_error("invalidpost", 'oublog');
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

// Check security.
$context = context_module::instance($cm->id);
$childdata = oublog_get_blog_data_base_on_cmid_of_childblog($cmid, $oublog);
$childoublog = null;
$childcm = null;
$childcourse = null;
if (!empty($childdata)) {
    $context = $childdata['context'];
    $childoublog = $childdata['ousharedblog'];
    $childcm = $childdata['cm'];
    $childcourse = $childdata['course'];
    oublog_check_view_permissions($childdata['ousharedblog'], $childdata['context'], $childdata['cm']);
} else {
    oublog_check_view_permissions($oublog, $context, $cm);
}
$correctglobal = isset($childoublog->global) ? $childoublog->global : $oublog->global;

// You can always delete your own comments, or any comment on your own
// personal blog
if (!($comment->userid==$USER->id ||
    ($correctglobal && $post->userid == $USER->id))) {
    require_capability('mod/oublog:managecomments', $context);
}

if ($correctglobal) {
    $blogtype = 'personal';
    // Get blog user from the oublog_get_post result (to save making an
    // extra query); this is only used to display their name anyhow
    $oubloguser = new stdClass();
    $oubloguser->id = $post->userid;
    foreach (get_all_user_name_fields() as $field) {
        $oubloguser->$field = $post->$field;
    }
} else {
    $blogtype = 'course';
}
$viewurl = !empty($referurl) ? $referurl : new moodle_url('/mod/oublog/viewpost.php', array('post' => $post->id));
if (!empty($commentid) && !empty($confirm)) {
    $timedeleted = time();
    $updatecomment = (object)array(
        'id' => $commentid,
        'deletedby' => $USER->id,
        'timedeleted' => $timedeleted);
    $DB->update_record('oublog_comments', $updatecomment);

    // Inform completion system, if available
    $completion = new completion_info($childcourse ? $childcourse : $course);
    $condition = $childdata ? ($completion->is_enabled($childcm) && ($childoublog->completioncomments) &&
        $completion->is_enabled($cm) && ($oublog->completioncomments)) :
        ($completion->is_enabled($cm) && ($oublog->completioncomments));
    if ($condition) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $comment->userid);
    }

    // Log delete comment event.
    $params = array(
        'context' => $context,
        'objectid' => $comment->id,
        'other' => array(
            'oublogid' => $oublog->id,
            'postid' => $comment->postid,
        )
    );
    $event = \mod_oublog\event\comment_deleted::create($params);
    $event->trigger();

    redirect($viewurl);
    exit;
}

// Get Strings.
$stroublogs  = get_string('modulenameplural', 'oublog');
$stroublog   = get_string('modulename', 'oublog');

// Print the header.
$PAGE->set_title(format_string(!empty($childoublog->name) ? $childoublog->name : $oublog->name));
$PAGE->set_heading(format_string(!empty($childcourse->fullname) ? $childcourse->fullname : $course->fullname));
if ($blogtype == 'personal') {
    $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id'=>$oubloguser->id)));
    $PAGE->navbar->add(format_string($oublog->name));
}
echo $OUTPUT->header();
echo $OUTPUT->confirm(get_string('confirmdeletecomment', 'oublog'),
    new moodle_url('/mod/oublog/deletecomment.php', array('comment'=>$commentid, 'confirm'=>'1',
    'referurl' => $referurl, 'cmid' => $cmid)),
    $viewurl);
echo $OUTPUT->footer();