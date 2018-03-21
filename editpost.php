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
 * This page allows a user to add and edit blog posts
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */

require_once("../../config.php");
require_once("locallib.php");
require_once('post_form.php');

$blog = required_param('blog', PARAM_INT);        // Blog ID.
$postid = optional_param('post', 0, PARAM_INT);   // Post ID for editing.
$referurl = optional_param('referurl', 0, PARAM_LOCALURL);
$cmid = optional_param('cmid', null, PARAM_INT);

if ($blog) {
    if (!$oublog = $DB->get_record("oublog", array('id' => $blog))) {
        print_error('invalidblog', 'oublog');
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $blog)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array('id' => $oublog->course))) {
        print_error('coursemisconf');
    }
}
if ($postid) {
    if (!$post = $DB->get_record('oublog_posts', array('id' => $postid))) {
        print_error('invalidpost', 'oublog');
    }
    if (!$oubloginstance = $DB->get_record('oublog_instances', array('id' => $post->oubloginstancesid))) {
        print_error('invalidblog', 'oublog');
    }
}
$url = new moodle_url('/mod/oublog/editpost.php', array('blog' => $blog, 'post' => $postid));
$PAGE->set_url($url);
$context = context_module::instance($cm->id);
$childdata = oublog_get_blog_data_base_on_cmid_of_childblog($cmid, $oublog);
$contextmaster = null;
$childcm = null;
$childcourse = null;
$childoublog = null;
// Check security.
if (!empty($childdata)) {
    $contextmaster = $context;
    require_course_login($childdata['cm']->course, true, $childdata['cm']);
    oublog_check_view_permissions($childdata['ousharedblog'], $childdata['context'], $childdata['cm']);
    $context = $childdata['context'];
    $childcm = $childdata['cm'];
    $childcourse = $childdata['course'];
    $childoublog = $childdata['ousharedblog'];
} else {
    require_course_login($cm->course, true, $cm);
    oublog_check_view_permissions($oublog, $context, $cm);
}
$correctinvidualsetting = isset($childoublog->individual) ? $childoublog->individual : $oublog->individual;
$correctcontextfiles = $contextmaster ? $contextmaster : $context;
$correctglobal = isset($childoublog->global) ? $childoublog->global : $oublog->global;
$PAGE->requires->js_init_call('M.mod_oublog.init', null, true);

if ($correctglobal) {
    $blogtype = 'personal';

    // New posts point to current user.
    if (!isset($oubloginstance)) {
        $oubloguser = $USER;
        if (!$oubloginstance = $DB->get_record('oublog_instances', array('oublogid' => $oublog->id, 'userid' => $USER->id))) {
            print_error('invalidblog', 'oublog');
        }
    } else {
        $oubloguser = $DB->get_record('user', array('id' => $oubloginstance->userid));
    }
    $viewurl = new moodle_url('/mod/oublog/view.php', array('user' => $oubloguser->id));
    if (isset($referurl) && $referurl != "" ) {
        $viewurl = $referurl;
    }
} else {
    $blogtype = 'course';
    $viewurl = new moodle_url('/mod/oublog/view.php', array('id' => $childcm ? $childcm->id : $cm->id));
    if (isset($referurl) && $referurl != "" ) {
        $viewurl = $referurl;
    }
}
// If editing a post, must be your post or you have manageposts.
$canmanage = has_capability('mod/oublog:manageposts', $context);
if (isset($post) && $USER->id != $oubloginstance->userid && !$canmanage) {
    print_error('accessdenied', 'oublog');
}

// Must be able to post in order to post OR edit a post. This is so that if
// somebody is blocked from posting, they can't just edit an existing post.
// Exception is that admin is allowed to edit posts even though they aren't
// allowed to post to the blog.
if (!(
    oublog_can_post($childoublog ? $childoublog : $oublog,
            isset($oubloginstance) ? $oubloginstance->userid : 0, $childcm ? $childcm : $cm) ||
            (isset($post) && $canmanage))) {
    print_error('accessdenied', 'oublog');
}

// Get strings.
$stroublogs  = get_string('modulenameplural', 'oublog');
$stroublog   = get_string('modulename', 'oublog');
$straddpost  = get_string('newpost', 'oublog', oublog_get_displayname($oublog));
$streditpost = get_string('editpost', 'oublog');


// Set-up groups.
$currentgroup = oublog_get_activity_group($childcm ? $childcm : $cm, true);
$groupmode = oublog_get_activity_groupmode($childcm ? $childcm : $cm, $childcourse ? $childcourse : $course);
if ($groupmode == VISIBLEGROUPS && !groups_is_member($currentgroup) && !$correctinvidualsetting) {
    require_capability('moodle/site:accessallgroups', $context);
}

// Setup tag list call.
$curindividual = -1;
$curgroup = false;
if ($correctinvidualsetting) {
    $curindividual = isset($oubloginstance->userid) ? $oubloginstance->userid : $USER->id;
} else {
    $curgroup = isset($post->groupid) ? $post->groupid : $currentgroup;
}
// Moved call to oublog_get_tag_list() here.
$tags = oublog_get_tag_list($oublog, $curgroup, $childcm ? $childcm : $cm,
    $correctglobal ? $oublog->id : null, $curindividual);

$mform = new mod_oublog_post_form('editpost.php', array(
    'individual' => $correctinvidualsetting,
    'maxvisibility' => $childoublog ? $childoublog->maxvisibility : $oublog->maxvisibility,
    'allowcomments' => $childoublog ? $childoublog->allowcomments : $oublog->allowcomments,
    'edit' => !empty($postid),
    'personal' => $correctglobal,
    'maxbytes' => $childoublog ? $childoublog->maxbytes : $oublog->maxbytes,
    'maxattachments' => $childoublog ? $childoublog->maxattachments : $oublog->maxattachments,
    'restricttags' => $childoublog ? $childoublog->restricttags : $oublog->restricttags,
    'availtags' => $tags,
    'referurl' => $referurl,
    'cmid' => $cmid,
    'tagslist' => $childoublog ? $childoublog->tagslist : $oublog->tagslist));
if ($mform->is_cancelled()) {
    redirect($viewurl);
    exit;
}

if (!$frmpost = $mform->get_data()) {

    if ($postid) {
        $post->post  = $post->id;
        $post->general = $streditpost;
        $post->tags = oublog_get_tags_csv($post->id);
        // Add a trailing comma for autocompletion support.
        if (!empty($post->tags)) {
            $post->tags .= ', ';
        }
    } else {
        $post = new stdClass;
        $post->general = $straddpost;
    }

    $post->blog = $oublog->id;

    $draftitemid = file_get_submitted_draft_itemid('attachments');
    file_prepare_draft_area($draftitemid, $correctcontextfiles->id, 'mod_oublog', 'attachment',
            empty($post->id) ? null : $post->id);

    $draftideditor = file_get_submitted_draft_itemid('message');
    $currenttext = file_prepare_draft_area($draftideditor, $correctcontextfiles->id, 'mod_oublog',
            'message', empty($post->id) ? null : $post->id,
            array('subdirs' => 0), empty($post->message) ? '' : $post->message);

    $post->attachments = $draftitemid;
    $post->message = array('text' => $currenttext,
            'format' => empty($post->messageformat) ? editors_get_preferred_format() : $post->messageformat,
            'itemid' => $draftideditor);

    $mform->set_data($post);

    // Print the header.

    if ($blogtype == 'personal') {
        $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id' => $oubloguser->id)));
        $PAGE->navbar->add(format_string($oubloginstance->name), $viewurl);
    }
    $PAGE->navbar->add($post->general);
    $PAGE->set_title(format_string($childoublog ? $childoublog->name : $oublog->name));
    $PAGE->set_heading(format_string(!empty($childcourse->fullname) ? $childcourse->fullname : $course->fullname));
    $renderer = $PAGE->get_renderer('mod_oublog');
    $renderer->pre_display($cm, $oublog, 'editpost');
    echo $OUTPUT->header();
    echo $renderer->render_header($childcm ? $childcm : $cm, $childoublog ? $childoublog : $oublog, 'editpost');
    echo $renderer->render_pre_postform($childoublog ? $childoublog : $oublog, $childcm ? $childcm : $cm);
    $mform->display();
    // Add tagselector yui mod - autocomplete of tags.
    $PAGE->requires->yui_module('moodle-mod_oublog-tagselector', 'M.mod_oublog.tagselector.init',
            array('id_tags', $tags));
    $PAGE->requires->string_for_js('numposts', 'oublog');

    // Check the network connection on exiting the update page.
    $PAGE->requires->strings_for_js(array('savefailtitle', 'savefailnetwork'), 'oublog');
    $PAGE->requires->yui_module('moodle-mod_oublog-savecheck', 'M.mod_oublog.savecheck.init', array($context->id));

    echo $OUTPUT->footer();

} else {

    $post = $frmpost;
    // Handle form submission.
    if (!empty($post->post)) {
        // Update the post.
        $post->id = $post->post;
        $post->oublogid = $oublog->id;
        $post->userid = $oubloginstance->userid;

        oublog_edit_post($post, $cm);

        // Log post edited event.
        $params = array(
                'context' => $context,
                'objectid' => $post->id,
                'other' => array(
                    'oublogid' => $oublog->id
            )
        );

        $event = \mod_oublog\event\post_updated::create($params);
        $event->trigger();

        redirect($viewurl);

    } else {
        // Insert the post.
        unset($post->id);
        $post->oublogid = $oublog->id;
        $post->userid = $USER->id;

        // Consider groups only when it is not an individual blog.
        if ($correctinvidualsetting) {
            $post->groupid = 0;
        } else {
            if (!$currentgroup && $groupmode) {
                print_error('notaddpostnogroup', 'oublog');
            }
            $post->groupid = $currentgroup;
        }

        if (!oublog_add_post($post, $cm, $oublog, $course)) {
            print_error('notaddpost', 'oublog');
        }

        // Log add post event.
        $params = array(
                'context' => $context,
                'objectid' => $post->id,
                'other' => array(
                    'oublogid' => $oublog->id
            )
        );
        $event = \mod_oublog\event\post_created::create($params);
        $event->trigger();

        redirect($viewurl);
    }

}
