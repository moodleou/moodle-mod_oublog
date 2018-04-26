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
 * This page prints information about edits to a blog post.
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */

require_once("../../config.php");
require_once("locallib.php");

$editid = required_param('edit', PARAM_INT); // Blog post edit ID.
$cmid = optional_param('cmid', null, PARAM_INT);

if (!$edit = $DB->get_record('oublog_edits', array('id' => $editid))) {
    print_error('invalidedit', 'oublog');
}

if (!$post = oublog_get_post($edit->postid)) {
    print_error('invalidpost', 'oublog');
}

if (!$cm = get_coursemodule_from_instance('oublog', $post->oublogid)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array('id' => $cm->course))) {
    print_error('coursemisconf');
}

if (!$oublog = $DB->get_record("oublog", array('id' => $cm->instance))) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);
$contextmaster = null;
$childdata = oublog_get_blog_data_base_on_cmid_of_childblog($cmid, $oublog);
$childcm = null;
$childcourse = null;
$childoublog = null;
if (!empty($childdata)) {
    $contextmaster = $context;
    $context = $childdata['context'];
    $childcm = $childdata['cm'];
    $childcourse = $childdata['course'];
    $childoublog = $childdata['ousharedblog'];
    oublog_check_view_permissions($childdata['ousharedblog'], $childdata['context'], $childdata['cm']);
} else {
    oublog_check_view_permissions($oublog, $context, $cm);
}
$correctcontext = $contextmaster ? $contextmaster : $context;

$url = new moodle_url('/mod/oublog/viewedit.php', array('edit' => $editid));
$PAGE->set_url($url);

// Check security.
$canpost = $childdata ? oublog_can_post($childoublog, $post->userid, $childcm) :
            oublog_can_post($oublog, $post->userid, $cm);
$canmanageposts     = has_capability('mod/oublog:manageposts', $context);
$canmanagecomments  = has_capability('mod/oublog:managecomments', $context);
$canaudit           = has_capability('mod/oublog:audit', $context);

// Get strings.
$stroublogs     = get_string('modulenameplural', 'oublog');
$stroublog      = get_string('modulename', 'oublog');
$strtags        = get_string('tags', 'oublog');
$strviewedit    = get_string('viewedit', 'oublog');

// Set-up groups.
$currentgroup = oublog_get_activity_group($childcm ? $childcm : $cm, true);
$groupmode = oublog_get_activity_groupmode($childcm ? $childcm : $cm, $childcourse ? $childcourse : $course);

// Print the header.
if ($oublog->global) {
    if (!$oubloginstance = $DB->get_record('oublog_instances', array('id' => $post->oubloginstancesid))) {
        print_error('invalidblog', 'oublog');
    }
    if (!$oubloguser = $DB->get_record('user', array('id' => $oubloginstance->userid))) {
        print_error('invaliduserid');
    }

    $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id' => $oubloguser->id)));
    $PAGE->navbar->add(format_string($childoublog ? $childoublog->name : $oublog->name),
        new moodle_url('/mod/oublog/view.php', array('user' => $oubloguser->id)));
}
$navbarurl = $cmid ?  new moodle_url('/mod/oublog/viewpost.php', array('post' => $post->id,
            'cmid' => $cmid)) : new moodle_url('/mod/oublog/viewpost.php', array('post' => $post->id, 'cmid' => $cmid));
if (!empty($post->title)) {
    $PAGE->navbar->add(format_string($post->title), $navbarurl);
} else {
    $PAGE->navbar->add(shorten_text(format_string($post->message, 30)), $navbarurl);
}

$PAGE->navbar->add($strviewedit);
$PAGE->set_title(format_string($childoublog ? $childoublog->name : $oublog->name));
$PAGE->set_heading(format_string(!empty($childcourse->fullname) ? $childcourse->fullname : $course->fullname));

$renderer = $PAGE->get_renderer('mod_oublog');
$renderer->pre_display($childcm ? $childcm : $cm, $childoublog ? $childoublog : $oublog, 'viewedit');

echo $OUTPUT->header();

// Print the main part of the page.
echo '<div class="oublog-topofpage"></div>';

echo $renderer->render_header($childcm ? $childcm : $cm, $childoublog ? $childoublog : $oublog, 'viewedit');

// Print blog posts.
?>
<div id="middle-column">
    <div class="oublog-post">
        <h3><?php print format_string($edit->oldtitle) ?></h3>
        <?php
        $fs = get_file_storage();
        if ($files = $fs->get_area_files($correctcontext->id,
            'mod_oublog', 'edit', $edit->id, "timemodified", false)) {
            echo '<div class="oublog-post-attachments">';
            foreach ($files as $file) {
                $cmparam = $cmid ? '?cmid=' . $cmid : null;
                $filename = $file->get_filename();
                $mimetype = $file->get_mimetype();
                $iconimage = '<img src="'.$OUTPUT->image_url(file_mimetype_icon($mimetype)).'" class="icon" alt="'.
                        $mimetype.'" />';
                $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$correctcontext->id .'/mod_oublog/edit/' .
                        $edit->id.'/'.$filename);
                echo "<a href=\"$path\">$iconimage</a> ";
                echo "<a href=\"$path" . "$cmparam\">".s($filename)."</a><br />";
            }
            echo '</div>';
        }
        ?>
        <div class="oublog-post-date">
            <?php print oublog_date($edit->timeupdated) ?>
        </div>
        <p>
<?php
$text = file_rewrite_pluginfile_urls($edit->oldmessage, 'pluginfile.php',
        $correctcontext->id, 'mod_oublog',
        'message', $edit->postid);
print format_text($text, FORMAT_HTML);
?>
        </p>
    </div>
</div>
<?php

// Finish the page.
echo '<div class="clearfix"></div>';
echo $OUTPUT->footer();
