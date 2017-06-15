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

$linkid  = required_param('link', PARAM_INT);          // Link ID to delete
$confirm = optional_param('confirm', 0, PARAM_INT);    // Confirm that it is ok to delete link

if (!$link = $DB->get_record('oublog_links', array('id'=> $linkid))) {
    print_error('invalidlink', 'oublog');
}

if (!$cm = get_coursemodule_from_instance('oublog', $link->oublogid)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id"=> $cm->course))) {
    print_error('coursemisconf');
}

if (!$oublog = $DB->get_record("oublog", array("id"=> $cm->instance))) {
    print_error('invalidcoursemodule');
}

$url = new moodle_url('/mod/oublog/deletelink.php', array('link'=>$linkid, 'confirm'=>$confirm));
$PAGE->set_url($url);

// Check security.
$context = context_module::instance($cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

$oubloginstance = $link->oubloginstancesid ? $DB->get_record('oublog_instances', array('id'=>$link->oubloginstancesid)) : null;
oublog_require_userblog_permission('mod/oublog:managelinks', $oublog, $oubloginstance, $context);

if ($oublog->global) {
    $blogtype = 'personal';
    $oubloguser = $USER;
} else {
    $blogtype = 'course';
}

$viewurl = new moodle_url('/mod/oublog/view.php', array('id'=>$cm->id));

if (!empty($linkid) && !empty($confirm)) {
    oublog_delete_link($oublog, $link);
    redirect($viewurl);
    exit;
}

// Get Strings.
$stroublogs  = get_string('modulenameplural', 'oublog');
$stroublog   = get_string('modulename', 'oublog');

// Print the header.
if ($blogtype == 'personal') {
        $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id'=>$oubloguser->id)));
        $PAGE->navbar->add(format_string($oublog->name));
}
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();
echo $OUTPUT->confirm(get_string('confirmdeletelink', 'oublog'),
                 new moodle_url('/mod/oublog/deletelink.php', array('link'=>$linkid, 'confirm'=>'1')),
                 $viewurl);
echo $OUTPUT->footer();
