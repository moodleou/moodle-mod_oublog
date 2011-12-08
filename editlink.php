<?php
/**
 * This page allows a user to add and edit related blog links
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */

require_once("../../config.php");
require_once("locallib.php");
require_once('link_form.php');

$blog = required_param('blog', PARAM_INT);                          // Blog ID
$bloginstancesid = optional_param('bloginstance', 0, PARAM_INT);     // Blog instances ID
$linkid = optional_param('link', 0, PARAM_INT);                     // Comment ID for editing

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
//TODO: the following if statement doesn't look right!
if ($linkid) {
    if (!$link = $DB->get_record('oublog_links', array('id'=>$linkid)));
}

$url = new moodle_url('/mod/oublog/editlink.php', array('blog'=>$blog, 'bloginstance'=>$bloginstancesid, 'link'=>$linkid));
$PAGE->set_url($url);

/// Check security
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

if($linkid) {
    $bloginstancesid=$link->oubloginstancesid;
}
$oubloginstance = $bloginstancesid ? $DB->get_record('oublog_instances', array('id'=>$bloginstancesid)) : null;
    oublog_require_userblog_permission('mod/oublog:managelinks', $oublog,$oubloginstance,$context);

if ($oublog->global) {
    $blogtype = 'personal';
    $oubloguser = $USER;
    $viewurl = 'view.php?user='.$oubloginstance->userid;
} else {
    $blogtype = 'course';
    $viewurl = 'view.php?id='.$cm->id;
}

/// Get strings
$stroublogs  = get_string('modulenameplural', 'oublog');
$stroublog   = get_string('modulename', 'oublog');
$straddlink  = get_string('addlink', 'oublog');
$streditlink = get_string('editlink', 'oublog');

$mform = new mod_oublog_link_form('editlink.php', array('edit' => !empty($linkid)));

if ($mform->is_cancelled()) {
    redirect($viewurl);
    exit;
}

if (!$frmlink = $mform->get_data()) {

    if (!isset($link)) {
        $link = new stdClass;
        $link->general = $straddlink;
    } else {
        $link->link = $link->id;
    }

    $link->blog = $blog;
    $link->bloginstance = $bloginstancesid;

    $mform->set_data($link);


    // Print the header

    if ($blogtype == 'personal') {
        $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id'=>$oubloguser->id)));
        $PAGE->navbar->add(format_string($oublog->name), new moodle_url('/mod/oublog/view.php', array('blog'=>$blog)));
    } else {
        $PAGE->navbar->add(($linkid ? $streditlink : $straddlink));
    }
    $PAGE->set_title(format_string($oublog->name));
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

    echo '<br />';
    $mform->display();

    echo $OUTPUT->footer();

} else {
    if ($frmlink->link) {
        $frmlink->id = $frmlink->link;
        $frmlink->oublogid = $oublog->id;

        if (!oublog_edit_link($frmlink)) {
            print_error('couldnotaddlink','oublog');
        }

    } else {
        unset($frmlink->id);
        $frmlink->oublogid = $oublog->id;
        $frmlink->oubloginstancesid = $bloginstancesid;

        if (!oublog_add_link($frmlink)) {
            print_error('couldnotaddlink','oublog');
        }
    }

    redirect($viewurl);
}
