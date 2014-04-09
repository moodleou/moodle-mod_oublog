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
 * Page for viewing all user participation
 *
 * @package mod
 * @subpackage oublog
 * @copyright 2011 The Open University
 * @author Stacey Walker <stacey@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/oublog/locallib.php');
require_once($CFG->libdir.'/gradelib.php');

$id         = required_param('id', PARAM_INT); // Course Module ID
$userid     = required_param('user', PARAM_INT);
$groupid    = optional_param('group', 0, PARAM_INT);
$download   = optional_param('download', '', PARAM_TEXT);
$page       = optional_param('page', 0, PARAM_INT); // flexible_table page

$params = array(
    'id'        => $id,
    'user'      => $userid,
    'group'     => $groupid,
    'download'  => $download,
    'page'      => $page,
);
$url = new moodle_url('/mod/oublog/userparticipation.php', $params);
$PAGE->set_url($url);

$cm = get_coursemodule_from_id('oublog', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$oublog = $DB->get_record('oublog', array('id' => $cm->instance), '*', MUST_EXIST);

$PAGE->set_cm($cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$PAGE->set_pagelayout('incourse');
require_course_login($course, true, $cm);

// participation capability check
$canview = oublog_can_view_participation($course, $oublog, $cm, $groupid);
$viewonlyown = ($canview == OUBLOG_MY_PARTICIPATION && $USER->id != $userid);
if ($oublog->global && $USER->id == $userid) {
    $viewonlyown = false;
    $canview = OUBLOG_MY_PARTICIPATION;
}
if ($canview == OUBLOG_NO_PARTICIPATION || $viewonlyown) {
    print_error('nopermissiontoshow');
}
$viewfullnames = has_capability('moodle/site:viewfullnames', $context);

// all enrolled users for table pagination
$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

// Create time filter options form.
$default = get_user_preferences('mod_oublog_postformfilter', OUBLOG_STATS_TIMEFILTER_ALL);
    // Create time filter options form.
    $customdata = array(
            'options' => array(),
            'cmid' => $cm->id,
            'user'      => $userid,
            'group'     => $groupid,
            'download'  => $download,
            'startyear' => $course->startdate,
            'params' => array()
    );
$timefilter = new oublog_participation_timefilter_form(null, $customdata);

$start = $end = 0;
// If data has been received from this form.
if ($submitted = $timefilter->get_data()) {
    if ($submitted->start) {
        $start = strtotime('00:00:00', $submitted->start);
    }
    if ($submitted->end) {
        $end = strtotime('23:59:59', $submitted->end);
    }
} else {
    // Recieved via post back.
    if ($start = optional_param('start', null, PARAM_INT)) {
        $start = strtotime('00:00:00', $start);
    }
    if ($end = optional_param('end', null, PARAM_INT)) {
        $end = strtotime('23:59:59', $end);
    }
}

$participation = oublog_get_user_participation($oublog, $context,
        $userid, $groupid, $cm, $course, $start, $end);
// Add extra navigation link for users who can see all participation.
$canviewall = oublog_can_view_participation($course, $oublog, $cm, $groupid);
if ($canviewall == OUBLOG_USER_PARTICIPATION) {
    $allusersurl = new moodle_url('/mod/oublog/participation.php',
        array('id' => $cm->id, 'group' => $groupid));
    $PAGE->navbar->add(get_string('userparticipation', 'oublog'), $allusersurl);
}
$PAGE->navbar->add(fullname($participation->user, $viewfullnames));
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($oublog->name));

$groupname = '';
if ($groupid) {
    $groupname = $DB->get_field('groups', 'name', array('id' => $groupid));
}

$oublogoutput = $PAGE->get_renderer('mod_oublog');

if (empty($download)) {
    echo $OUTPUT->header();
}

if (!$start && !$end) {
    $title = get_string('contribution_all', 'oublog');
    $info = get_string('contribution_all', 'oublog');
}
$startdate = userdate($start, get_string('strftimedaydate'));
$enddate = userdate($end, get_string('strftimedaydate'));
if ($start && !$end) {
    $title = get_string('contribution', 'oublog');
    $info = get_string('contribution_from', 'oublog', $startdate);
}
if (!$start && $end) {
    $title = get_string('contribution', 'oublog');
    $info = get_string('contribution_to', 'oublog', $enddate);
}
if ($start && $end) {
    $a = new stdClass();
    $a->start = $startdate;
    $a->end   = $enddate;
    $title = get_string('contribution', 'oublog');
    $info = get_string('contribution_fromto', 'oublog', $a);
}
if (empty($download)) {
    echo html_writer::tag('h2', $info,
            array('class' => 'oublog-post-title'));
    $timefilter->display();
}
if (!count($participation->posts)) {
    $postsmessage = get_string('posts', 'oublog') . ': ' . get_string('nouserpostsfound', 'oublog');
} else {
    $postsmessage = get_string('posts', 'oublog') . ': ' . count($participation->posts);
}
if (!count($participation->comments)) {
    $commentsmessage = get_string('comments', 'oublog') . ': ' . get_string('nousercommentsfound', 'oublog');
} else {
    $commentsmessage = get_string('comments', 'oublog') . ': ' . count($participation->comments);
}

$output = $OUTPUT->box_start('generalbox', 'notice');
$output .= html_writer::tag('p', $postsmessage .'<br>'.$commentsmessage.'<br>');
$output .= $OUTPUT->box_end();
if (empty($download)) {
    echo $output;
}

$oublogoutput->render_user_participation_list($cm, $course, $oublog, $participation,
        $groupid, $download, $page, $coursecontext, $viewfullnames, $groupname, $start, $end);

if (empty($download)) {
    echo $OUTPUT->footer();
}

// Log visit.
$logurl = 'userparticipation.php?id=' . $id . '&user=' . $userid
    . '&group=' . $groupid . '&download=' . $download . '&page=' . $page;
add_to_log($course->id, 'oublog', 'view', $logurl, $oublog->id, $cm->id);
