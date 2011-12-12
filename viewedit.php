<?php
/**
 * This page prints information about edits to a blog post.
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */

require_once("../../config.php");
require_once("locallib.php");

$editid = required_param('edit', PARAM_INT);       // Blog post edit ID

if (!$edit = $DB->get_record('oublog_edits', array('id'=>$editid))) {
    print_error('invalidedit','oublog');
}

if (!$post = oublog_get_post($edit->postid)) {
    print_error('invalidpost','oublog');
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

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

$url = new moodle_url('/mod/oublog/viewedit.php', array('edit'=>$editid));
$PAGE->set_url($url);

/// Check security
$canpost            = oublog_can_post($oublog, $post->userid, $cm);
$canmanageposts     = has_capability('mod/oublog:manageposts', $context);
$canmanagecomments  = has_capability('mod/oublog:managecomments', $context);
$canaudit           = has_capability('mod/oublog:audit', $context);

/// Get strings
$stroublogs     = get_string('modulenameplural', 'oublog');
$stroublog      = get_string('modulename', 'oublog');
$strtags        = get_string('tags', 'oublog');
$strviewedit    = get_string('viewedit', 'oublog');

/// Set-up groups
$currentgroup = oublog_get_activity_group($cm, true);
$groupmode = oublog_get_activity_groupmode($cm, $course);


/// Print the header
if ($oublog->global) {
    if (!$oubloginstance = $DB->get_record('oublog_instances', array('id'=>$post->oubloginstancesid))) {
        print_error('invalidblog','oublog');
    }
    if (!$oubloguser = $DB->get_record('user', array('id'=>$oubloginstance->userid))) {
        print_error('invaliduserid');
    }

    $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id'=>$oubloguser->id)));
    $PAGE->navbar->add(format_string($oublog->name), new moodle_url('/mod/oublog/view.php', array('user'=>$oubloguser->id)));
}

if (!empty($post->title)) {
    $PAGE->navbar->add(format_string($post->title), new moodle_url('/mod/oublog/viewpost.php', array('post'=>$post->id)));
} else {
    $PAGE->navbar->add(shorten_text(format_string($post->message, 30)), new moodle_url('/mod/oublog/viewpost.php', array('post'=>$post->id)));
}

$PAGE->navbar->add($strviewedit);
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();

/// Print the main part of the page
echo '<div class="oublog-topofpage"></div>';


/// Print blog posts
?>
<div id="middle-column">
    <div class="oublog-post">
        <h3><?php print format_string($edit->oldtitle) ?></h3>
        <div class="oublog-post-date">
            <?php print oublog_date($edit->timeupdated) ?>
        </div>
        <p>
<?php
$text = file_rewrite_pluginfile_urls($edit->oldmessage, 'pluginfile.php', $context->id, 'mod_oublog', 'message', $edit->postid);
print format_text($text, FORMAT_HTML);
?>
        </p>
<?php
echo '<div class="oublog-post-attachments">';
$fs = get_file_storage();
if ($files = $fs->get_area_files($context->id, 'mod_oublog', 'edit', $edit->id, "timemodified", false)) {
    foreach ($files as $file) {
        $filename = $file->get_filename();
        $mimetype = $file->get_mimetype();
        $iconimage = '<img src="'.$OUTPUT->pix_url(file_mimetype_icon($mimetype)).'" class="icon" alt="'.$mimetype.'" />';
        $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_oublog/edit/'.$edit->id.'/'.$filename);
        echo "<a href=\"$path\">$iconimage</a> ";
        echo "<a href=\"$path\">".s($filename)."</a>";
    }
}
echo '</div>';
?>
    </div>
</div>
<?php

/// Finish the page
echo '<div class="clearfix"></div>';
echo $OUTPUT->footer();