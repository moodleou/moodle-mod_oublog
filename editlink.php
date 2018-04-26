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
$cmid = optional_param('cmid', null, PARAM_INT);

if ($blog) {
    if (!$oublog = $DB->get_record("oublog", array("id"=>$blog))) {
        print_error('invalidblog', 'oublog');
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $blog)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id"=>$oublog->course))) {
        print_error('coursemisconf');
    }
}
// TODO: If statement didn't look right! CC-Inline control structures not allowed.
if ($linkid) {
    if (!$link = $DB->get_record('oublog_links', array('id'=>$linkid))) {
        $link = false;
    }
}

$url = new moodle_url('/mod/oublog/editlink.php', array('blog'=>$blog, 'bloginstance'=>$bloginstancesid, 'link'=>$linkid));
$PAGE->set_url($url);

// Check security.
$context = context_module::instance($cm->id);
$childdata = oublog_get_blog_data_base_on_cmid_of_childblog($cmid, $oublog);
$childoublog = null;
$childcourse = null;
if (!empty($childdata)) {
    $context = $childdata['context'];
    $childoublog = $childdata['ousharedblog'];
    $childcourse = $childdata['course'];
    oublog_check_view_permissions($childdata['ousharedblog'], $childdata['context'], $childdata['cm']);
} else {
    oublog_check_view_permissions($oublog, $context, $cm);
}
$correctglobal = isset($childoublog->global) ? $childoublog->global : $oublog->global;
if ($linkid) {
    $bloginstancesid=$link->oubloginstancesid;
}
$oubloginstance = $bloginstancesid ? $DB->get_record('oublog_instances', array('id'=>$bloginstancesid)) : null;
    oublog_require_userblog_permission('mod/oublog:managelinks', $oublog, $oubloginstance, $context);

if ($correctglobal) {
    $blogtype = 'personal';
    $oubloguser = $USER;
    $viewurl = 'view.php?user='.$oubloginstance->userid;
} else {
    $blogtype = 'course';
    $viewurl = $cmid ? 'view.php?id=' . $cmid : 'view.php?id=' . $cm->id;
}

// Get strings.
$stroublogs  = get_string('modulenameplural', 'oublog');
$stroublog   = get_string('modulename', 'oublog');
$straddlink  = get_string('addlink', 'oublog');
$streditlink = get_string('editlink', 'oublog');
$mform = new mod_oublog_link_form('editlink.php', array('edit' => !empty($linkid), 'cmid' => $cmid));

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
    $PAGE->set_title(format_string(!empty($childoublog->name) ? $childoublog->name : $oublog->name));
    $PAGE->set_heading(format_string(!empty($childcourse->fullname) ? $childcourse->fullname : $course->fullname));
    echo $OUTPUT->header();

    echo '<br />';
    $mform->display();

    echo $OUTPUT->footer();

} else {
    if ($frmlink->link) {
        $frmlink->id = $frmlink->link;
        $frmlink->oublogid = $oublog->id;

        if (!oublog_edit_link($frmlink)) {
            print_error('couldnotaddlink', 'oublog');
        }

    } else {
        unset($frmlink->id);
        $frmlink->oublogid = $oublog->id;
        $frmlink->oubloginstancesid = $bloginstancesid;

        if (!oublog_add_link($frmlink)) {
            print_error('couldnotaddlink', 'oublog');
        }
    }

    redirect($viewurl);
}
