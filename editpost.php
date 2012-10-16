<?php
/**
 * This page allows a user to add and edit blog posts
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */

require_once("../../config.php");
require_once("locallib.php");
require_once('post_form.php');

$blog = required_param('blog', PARAM_INT);        // Blog ID
$postid = optional_param('post', 0, PARAM_INT);   // Post ID for editing

if ($blog) {
    if (!$oublog = $DB->get_record("oublog", array("id"=>$blog))) {
        print_error('invalidblog','oublog');
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $blog)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id"=>$oublog->course))) {
        print_error('coursemisconf');
    }
}
if ($postid) {
    if (!$post = $DB->get_record('oublog_posts', array('id'=>$postid))) {
        print_error('invalidpost','oublog');
    }
    if (!$oubloginstance = $DB->get_record('oublog_instances', array('id'=>$post->oubloginstancesid))) {
        print_error('invalidblog','oublog');
    }
}

$url = new moodle_url('/mod/oublog/editpost.php', array('blog'=>$blog, 'post'=>$postid));
$PAGE->set_url($url);
$PAGE->requires->yui2_lib('event');
$PAGE->requires->js('/mod/oublog/oublog.js');

/// Check security
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

if ($oublog->global) {
    $blogtype = 'personal';

    // New posts point to current user
    if(!isset($oubloginstance)) {
        $oubloguser = $USER;
        if (!$oubloginstance = $DB->get_record('oublog_instances', array('oublogid'=>$oublog->id, 'userid'=>$USER->id))) {
            print_error('invalidblog','oublog');
        }
    } else {
        $oubloguser = $DB->get_record('user',array('id'=>$oubloginstance->userid));
    }
    $viewurl = new moodle_url('/mod/oublog/view.php', array('user'=>$oubloguser->id));

} else {
    $blogtype = 'course';
    $viewurl = new moodle_url('/mod/oublog/view.php', array('id'=>$cm->id));
}

// If editing a post, must be your post or you have manageposts
$canmanage=has_capability('mod/oublog:manageposts', $context);
if (isset($post) && $USER->id != $oubloginstance->userid && !$canmanage) {
    print_error('accessdenied','oublog');
}

// Must be able to post in order to post OR edit a post. This is so that if
// somebody is blocked from posting, they can't just edit an existing post.
// Exception is that admin is allowed to edit posts even though they aren't
// allowed to post to the blog.
if(!(
    oublog_can_post($oublog,isset($oubloginstance) ? $oubloginstance->userid : 0,$cm) ||
    (isset($post) && $canmanage))) {
    print_error('accessdenied','oublog');
}

/// Get strings
$stroublogs  = get_string('modulenameplural', 'oublog');
$stroublog   = get_string('modulename', 'oublog');
$straddpost  = get_string('newpost', 'oublog');
$streditpost = get_string('editpost', 'oublog');


/// Set-up groups
$currentgroup = oublog_get_activity_group($cm, true);
$groupmode = oublog_get_activity_groupmode($cm, $course);
if($groupmode==VISIBLEGROUPS && !groups_is_member($currentgroup) && !$oublog->individual) {
    require_capability('moodle/site:accessallgroups',$context);
}

$mform = new mod_oublog_post_form('editpost.php', array(
    'individual' => $oublog->individual,
    'maxvisibility' => $oublog->maxvisibility,
    'allowcomments' => $oublog->allowcomments,
    'edit' => !empty($postid),
    'personal' => $oublog->global,
    'maxbytes' => $oublog->maxbytes));
if ($mform->is_cancelled()) {
    redirect($viewurl);
    exit;
}


if (!$frmpost = $mform->get_data()) {

    if ($postid) {
        $post->post  = $post->id;
        $post->general = $streditpost;
        $post->tags = oublog_get_tags_csv($post->id);
    } else {
        $post = new stdClass;
        $post->general = $straddpost;
    }

    $post->blog = $oublog->id;

    $draftitemid = file_get_submitted_draft_itemid('attachments');
    file_prepare_draft_area($draftitemid, $context->id, 'mod_oublog', 'attachment', empty($post->id)?null:$post->id);

    $draftid_editor = file_get_submitted_draft_itemid('message');
    $currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_oublog', 'message', empty($post->id) ? null : $post->id, array('subdirs'=>0), empty($post->message) ? '' : $post->message);

    $post->attachments = $draftitemid;
    $post->message = array('text'=>$currenttext,
                           'format'=>empty($post->messageformat) ? editors_get_preferred_format() : $post->messageformat,
                           'itemid'=>$draftid_editor);

    $mform->set_data($post);


    // Print the header

    if ($blogtype == 'personal') {
        $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id'=>$oubloguser->id)));
        $PAGE->navbar->add(format_string($oubloginstance->name),$viewurl);
    }
    $PAGE->navbar->add($post->general);
    $PAGE->set_title(format_string($oublog->name));
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

    $mform->display();

    echo $OUTPUT->footer();

} else {

    $post = $frmpost;
    /// Handle form submission
    if (!empty($post->post)) {
        // update the post
        $post->id = $post->post;
        $post->oublogid = $oublog->id;
        $post->userid = $oubloginstance->userid;

        oublog_edit_post($post,$cm);
        add_to_log($course->id, "oublog", "edit post", $viewurl, $oublog->id, $cm->id);
        redirect($viewurl);

    } else {
        // insert the post
        unset($post->id);
        $post->oublogid = $oublog->id;
        $post->userid = $USER->id;

        //consider groups only when it is not an individual blog
        if ($oublog->individual) {
            $post->groupid = 0;
        } else {
            if(!$currentgroup && $groupmode) {
                print_error('notaddpostnogroup','oublog');
            }
            $post->groupid = $currentgroup;
        }

        if (!oublog_add_post($post,$cm,$oublog,$course)) {
            print_error('notaddpost','oublog');
        }
        add_to_log($course->id, "oublog", "add post", $viewurl, $oublog->id, $cm->id);
        redirect($viewurl);
    }

}
