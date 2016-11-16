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

$id = required_param('id', PARAM_INT);// Course Module ID.
$userid = required_param('user', PARAM_INT);
$groupid = optional_param('group', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);// Page.
$tab = optional_param('tab', 0, PARAM_INT);// Current tab, 0:Posts,1:Comments,2:Grade.

$params = array(
        'id' => $id,
        'user' => $userid,
        'group' => $groupid,
        'download' => $download,
        'page' => $page,
        'tab' => $tab
);
$url = new moodle_url('/mod/oublog/userparticipation.php', $params);
$PAGE->set_url($url);

$cm = get_coursemodule_from_id('oublog', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$oublog = $DB->get_record('oublog', array('id' => $cm->instance), '*', MUST_EXIST);

$PAGE->set_cm($cm);
$context = context_module::instance($cm->id);
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
$coursecontext = context_course::instance($course->id);

// Create time filter options form.
$customdata = array(
        'options' => array(),
        'cmid' => $cm->id,
        'user' => $userid,
        'group' => $groupid,
        'download' => $download,
        'startyear' => $course->startdate,
        'params' => array('tab' => $tab)
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
} else if (!$timefilter->is_submitted()) {
    // Recieved via post back.
    if ($start = optional_param('start', null, PARAM_INT)) {
        $timefilter->set_data(array('start' => $start));
        $start = strtotime('00:00:00', $start);
    }
    if ($end = optional_param('end', null, PARAM_INT)) {
        $timefilter->set_data(array('end' => $end));
        $end = strtotime('23:59:59', $end);
    }
}
$url->params(array('start' => $start, 'end' => $end));
$PAGE->set_url($url);
$getposts = true;
$getcomments = false;
$getgrades = false;
$limitnum = OUBLOG_POSTS_PER_PAGE;
$limitfrom = empty($page) ? null : $page * $limitnum;
if (!empty($download)) {
    $limitnum = null;
    $limitfrom = null;
}
// Customise data sought based on current tab.
switch($tab) {
    case 1:
        $getposts = false;
        $getcomments = true;
    break;
    case 2:
        $getposts = false;
        $getgrades = true;
    break;
}
$participation = oublog_get_user_participation($oublog, $context,
        $userid, $groupid, $cm, $course, $start, $end, $getposts, $getcomments, $limitfrom,
        $limitnum, $getgrades);
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
    $groupname = groups_get_group_name($groupid);
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
    $a->end  = $enddate;
    $title = get_string('contribution', 'oublog');
    $info = get_string('contribution_fromto', 'oublog', $a);
}
if (empty($download)) {
    if ($oublog->individual == OUBLOG_NO_INDIVIDUAL_BLOGS) {
        $groupurl = clone $url;
        $groupurl->remove_params(array('page', 'tab', 'download'));
        groups_print_activity_menu($cm, $groupurl);
    }
    echo html_writer::tag('h2', $info, array('class' => 'oublog-post-title'));
    $timefilter->display();
    $taburl = clone $url;
    $taburl->remove_params(array('page', 'tab'));
    $tabs = array(
            new tabobject('tab0', $taburl, $participation->numposts . ' ' . get_string('posts', 'oublog')),
            new tabobject('tab1', $taburl->out() . '&amp;tab=1', $participation->numcomments . ' ' .
                    get_string('comments', 'oublog')),
    );
    if (oublog_can_grade($course, $oublog, $cm, $groupid)) {
        $tabs[] = new tabobject('tab2', $taburl->out() . '&amp;tab=2', get_string('usergrade', 'oublog'));
    }
    echo $OUTPUT->tabtree($tabs, "tab$tab");

    // Output message when no content for tab.
    $warning = '';
    if ($tab == 0 & !$participation->numposts) {
        $warning = get_string('nouserpostsfound', 'oublog');
    } else if ($tab == 1 && !$participation->numcomments) {
        $warning = get_string('nousercommentsfound', 'oublog');
    } else if ($tab == 2 && !isset($participation->gradeobj)) {
        $warning = get_string('nousergrade', 'oublog');
    }

    if (!empty($warning)) {
        $output = $OUTPUT->box_start('generalbox', 'notice');
        $output .= html_writer::tag('p', $warning);
        $output .= $OUTPUT->box_end();
        echo $output;
    }
    // Pagination.
    $pag = '';
    if (!empty($participation->posts) && $participation->numposts > $limitnum) {
        $pag = $OUTPUT->paging_bar($participation->numposts, $page, $limitnum, $url);
    } else if (!empty($participation->comments) && $participation->numcomments > $limitnum) {
        $pag = $OUTPUT->paging_bar($participation->numcomments, $page, $limitnum, $url);
    }
    echo $pag;
}

$oublogoutput->render_user_participation_list($cm, $course, $oublog, $participation,
        $groupid, $download, $page, $coursecontext, $viewfullnames, $groupname, $start, $end);

echo $oublogoutput->get_link_back_to_oublog($cm->name, $cm->id);

if (empty($download)) {
    echo $pag;
    echo $OUTPUT->footer();
}

// Log visit list event.
$params = array(
    'context' => $context,
    'objectid' => $oublog->id,
    'other' => array(
        'info' => 'user participation',
        'relateduserid' => $userid,
        'logurl' => 'userparticipation.php?id=' . $cm->id
    )
);
$event = \mod_oublog\event\participation_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();
