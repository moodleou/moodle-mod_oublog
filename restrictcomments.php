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

// You can restrict comments (=change your posts so that they only permit
// comments from signed-in users) on either a post or a blog. A confirmation
// screen displays first.
require_once('../../config.php');
require_once('locallib.php');

// Get post or blog details
$postid = optional_param('post', 0, PARAM_INT);
if ($postid) {
    $isblog = false;
    if (!$oublog = oublog_get_blog_from_postid($postid)) {
        print_error('invalidrequest');
    }
} else {
    $blogid = required_param('blog', PARAM_INT);
    $isblog = true;
    if (!$oublog = $DB->get_record('oublog', array('id'=>$blogid))) {
        print_error('invalidrequest');
    }
}

// Get other details and check access
if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

// Require login and access to blog
require_login($course, $cm);
$context = context_module::instance($cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

// You must be able to post to blog (if blog = site blog, then your one)
if (!oublog_can_post($oublog, $USER->id, $cm)) {
    print_error('accessdenied', 'oublog');
}

// If there was a specified post, it must be yours
if (!$isblog) {
    $userid = $DB->get_field_sql("
SELECT
    bi.userid
FROM
    {oublog_posts} bp
    INNER JOIN {oublog_instances} bi ON bi.id=bp.oubloginstancesid
WHERE
    bp.id = ?", array($postid));
    if ($userid !== $USER->id) {
        print_error('accessdenied', 'oublog');
    }
}

// Is this the actual change or just the confirm?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_sesskey();

    // Apply actual change
    $rparam = array();
    if ($isblog) {
        $restriction = 'b.id = ? ';
        $rparam[] = $blogid;
    } else {
        $restriction = 'bp.id = ? ';
        $rparam[] = $postid;
    }
    if (!$DB->execute("
UPDATE {oublog_posts} SET allowcomments = " . OUBLOG_COMMENTS_ALLOW ."
WHERE id IN (
SELECT bp.id FROM
    {oublog_posts} bp
    INNER JOIN {oublog_instances} bi ON bi.id = bp.oubloginstancesid
    INNER JOIN {oublog} b ON b.id = bi.oublogid
WHERE
    bi.userid = ?
    AND bp.allowcomments >= " . OUBLOG_COMMENTS_ALLOWPUBLIC . "
    AND $restriction)", array_merge(array($USER->id), $rparam))) {
        print_error('error_unspecified', 'oublog', 'RC3');
    }

    // Redirect
    if ($isblog) {
        if ($oublog->global) {
            redirect('view.php?user=' . $USER->id);
        } else {
            redirect('view.php?id=' . $cm->id);
        }
    } else {
        redirect('viewpost.php?post=' . $postid);
    }
}

// This is the confirm screen. Do navigation first...
$oubloginstance = $DB->get_record('oublog_instances', array('oublogid'=>$oublog->id, 'userid'=>$USER->id));
oublog_build_navigation($oublog, $oubloginstance, $USER);
if (!$isblog) {
    $post = $DB->get_record('oublog_posts', array('id'=>$postid));
    oublog_get_post_extranav($post);
}

$PAGE->navbar->add(get_string('moderated_restrictpage', 'oublog'));
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();


if ($isblog) {
    if ($oublog->global) {
        $nourl = 'view.php';
        $noparams = array('user' => $USER->id);
    } else {
        $nourl = 'view.php';
        $noparams = array('id' => $cm->id);
    }
    $yesparams = array('blog' => $blogid);
} else {
    $nourl ='viewpost.php';
    $noparams = array('post' => $postid);
    $yesparams = array('post' => $postid);
}
$yesparams['sesskey'] = sesskey();

// Display the query
echo $OUTPUT->confirm(get_string('moderated_restrict' . ($isblog ? 'blog' : 'post') . '_info',
            'oublog'),
                     new moodle_url('/mod/oublog/restrictcomments.php', $yesparams),
                     new moodle_url('mod/oublog/'.$nourl, $noparams));


// Page footer
echo $OUTPUT->footer();
