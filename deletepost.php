<?php
/**
 * This page allows a user to delete a blog posts
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */
require_once("../../config.php");
require_once("locallib.php");
require_once($CFG->libdir . '/completionlib.php');

$blog    = required_param('blog', PARAM_INT);    // Blog ID
$postid  = required_param('post', PARAM_INT);    // Post ID for editing
$confirm = optional_param('confirm', 0, PARAM_INT); // Confirm that it is ok to delete post

if (!$oublog = $DB->get_record("oublog", array("id"=>$blog))) {
    print_error('invalidblog','oublog');
}
if (!$cm = get_coursemodule_from_instance('oublog', $blog)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record("course", array("id"=>$oublog->course))) {
    print_error('coursemisconf');
}
$url = new moodle_url('/mod/oublog/deletepost.php', array('blog'=>$blog, 'post'=>$postid, 'confirm'=>$confirm));
$PAGE->set_url($url);

/// Check security
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

$postauthor=$DB->get_field_sql("
SELECT
    i.userid
FROM
    {oublog_posts} p
    INNER JOIN {oublog_instances} i on p.oubloginstancesid=i.id
WHERE p.id = ?", array($postid));
if($postauthor!=$USER->id) {
    require_capability('mod/oublog:manageposts', $context);
}

if ($oublog->global) {
    $blogtype = 'personal';
    $oubloguser = $USER;
    $viewurl = new moodle_url('/mod/oublog/view.php', array('user'=>$USER->id));
} else {
    $blogtype = 'course';
    $viewurl = new moodle_url('/mod/oublog/view.php', array('id'=>$cm->id));
}

if (!empty($postid) && !empty($confirm)) {
    $expost=$DB->get_record('oublog_posts',array('id'=>$postid));

    $updatepost = (object)array(
        'id' => $postid,
        'deletedby' => $USER->id,
        'timedeleted' => time()
    );

    $tw=new transaction_wrapper();
    $DB->update_record('oublog_posts', $updatepost);
    if(!oublog_update_item_tags($expost->oubloginstancesid, $expost->id, array(),$expost->visibility)) {
        $tw->rollback();
        print_error('tagupdatefailed','oublog');
    }
    if(oublog_search_installed()) {
        $doc=oublog_get_search_document($updatepost, $cm);
        $doc->delete();
    }
    // Inform completion system, if available
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && ($oublog->completionposts)) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $postauthor);
    }
    $tw->commit();
    redirect($viewurl);
    exit;
}

/// Get Strings
$stroublogs  = get_string('modulenameplural', 'oublog');
$stroublog   = get_string('modulename', 'oublog');

// Print the header

if ($blogtype == 'personal') {
    $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id'=>$oubloguser->id)));
    $PAGE->navbar->add(format_string($oublog->name));
}
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();
echo $OUTPUT->confirm(get_string('confirmdeletepost', 'oublog'),
                     new moodle_url('/mod/oublog/deletepost.php',array('blog'=>$blog, 'post'=>$postid, 'confirm'=>'1')),
                     $viewurl);
