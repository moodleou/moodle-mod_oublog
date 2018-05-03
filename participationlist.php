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
 * Page for viewing user  participation list
 *
 * @package mod_oublog
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/oublog/locallib.php');
require_once($CFG->libdir.'/gradelib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$groupid = optional_param('group', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$curindividual = optional_param('individual', 0, PARAM_INT);
$tab = optional_param('tab', 0, PARAM_INT);// Current tab, 0:Posts, 1:Comments.
if ($groupid < 0) {
    $groupid = 0;
}
$params = array(
    'id' => $id,
    'individual' => $curindividual,
    'group' => $groupid,
    'page' => $page,
    'tab' => $tab
);
$url = new moodle_url('/mod/oublog/participationlist.php', $params);
$PAGE->set_url($url);
$getposts = true;
$getcomments = false;
$limitnum = OUBLOG_PARTICIPATION_PERPAGE;
$limitfrom = empty($page) ? null : $page * $limitnum;

$cm = get_coursemodule_from_id('oublog', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$oublog = $DB->get_record('oublog', array('id' => $cm->instance), '*', MUST_EXIST);

$masterblog = null;
$cmmaster = null;
// Get master blog.
if ($oublog->individual && $oublog->idsharedblog) {
    $masterblog = oublog_get_master($oublog->idsharedblog);

    // Get cm master.
    if (!$cmmaster = get_coursemodule_from_instance('oublog', $masterblog->id)) {
        throw new moodle_exception('invalidcoursemodule');
    }
}

$PAGE->set_cm($cm);
$context = context_module::instance($cm->id);
$PAGE->set_pagelayout('incourse');
require_course_login($course, true, $cm);

// Create time filter options form.
$customdata = array(
        'cmid' => $cm->id,
        'individual' => $curindividual,
        'group' => $groupid,
        'startyear' => $course->startdate,
        'params' => array( 'tab' => $tab)
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
    // Recieved via post back for tab useage.
    if ($start = optional_param('start', null, PARAM_INT)) {
        $timefilter->set_data(array('start' => $start));
        $start = strtotime('00:00:00', $start);
    }
    if ($end = optional_param('end', null, PARAM_INT)) {
        $timefilter->set_data(array('end' => $end));
        $end = strtotime('23:59:59', $end);
    }
}
// Customise data sought based on current tab.
switch($tab) {
    case 1:
        $getposts = false;
        $getcomments = true;
        break;
    case 2:
        $getposts = false;
        break;
}

$url->params(array('individual' => $curindividual, 'start' => $start, 'end' => $end, 'group' => $groupid));
$PAGE->set_url($url);
// Add extra navigation link for users who can see all participation.
$PAGE->navbar->add(get_string('viewallparticipation', 'oublog'));
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($oublog->name));
$oublogoutput = $PAGE->get_renderer('mod_oublog');

echo $OUTPUT->header();

// Print Groups drop-down menu.
groups_print_activity_menu($cm, $url);
if ($oublog->individual) {
    $individualdetails = oublog_individual_get_activity_details($cm, $url, $oublog,
            $groupid, $context, $cmmaster);
    if ($individualdetails) {
        $curindividual = $individualdetails->activeindividual;
        $oublog->individual = $individualdetails->mode;
        echo $individualdetails->display;
        $url->params(array('individual' => $curindividual, 'start' => $start, 'end' => $end));
        $PAGE->set_url($url);
    }
}
if (!$start && !$end) {
    $title = get_string('participation_all', 'oublog');
    $info = get_string('participation_all', 'oublog');
}
$startdate = userdate($start, get_string('strftimedaydate'));
$enddate = userdate($end, get_string('strftimedaydate'));
if ($start && !$end) {
    $title = get_string('participation', 'oublog');
    $info = get_string('participation_from', 'oublog', $startdate);
}
if (!$start && $end) {
    $title = get_string('participation', 'oublog');
    $info = get_string('participation_to', 'oublog', $enddate);
}
if ($start && $end) {
    $a = new stdClass();
    $a->start = $startdate;
    $a->end = $enddate;
    $title = get_string('participation', 'oublog');
    $info = get_string('participation_fromto', 'oublog', $a);
}
$participation = oublog_get_participation_details($oublog, $groupid, $curindividual,
        $start, $end, $page, $getposts, $getcomments, $limitfrom, $limitnum, $masterblog);

$url->params(array('individual' => $curindividual, 'start' => $start, 'end' => $end));
echo html_writer::tag('h2', $info, array('class' => 'oublog-post-title'));
$timefilter->display();

$taburl = clone $url;
$taburl->remove_params(array('page', 'tab'));
$tabs = array(
        new tabobject('tab0', $taburl, $participation->postscount . ' ' . get_string('posts', 'oublog')),
        new tabobject('tab1', $taburl->out() . '&amp;tab=1', $participation->commentscount . ' ' .
                 get_string('comments', 'oublog')),
);

echo $OUTPUT->tabtree($tabs, "tab$tab");

// Output message when no content for tab.
$warning = '';
if ($tab == 0 && (!isset($participation->postscount) || $participation->postscount < 1)) {
    $warning = get_string('nouserpostsfound', 'oublog');
    $getposts = false;
} else if ($tab == 1 && (!isset($participation->commentscount) || $participation->commentscount < 1)) {
    $warning = get_string('nousercommentsfound', 'oublog');
    $getcomments = false;
}

if (!empty($warning)) {
    $output = $OUTPUT->box_start('generalbox', 'notice');
    $output .= html_writer::tag('p', $warning);
    $output .= $OUTPUT->box_end();
    echo $output;
}

$pagingurl = new moodle_url('/mod/oublog/participationlist.php',
        array('id' => $cm->id, 'individual' => $curindividual,
        'page' => $page, 'start' => $start, 'end' => $end, 'tab' => $tab, 'group' => $groupid));

echo $oublogoutput->render_all_users_participation_table($cm, $course, $oublog,
        $page, $limitnum, $participation, $getposts, $getcomments,
        $start, $end, $pagingurl, $cmmaster);

echo $oublogoutput->get_link_back_to_oublog($cm->name, $cm->id);

echo $OUTPUT->footer();

// Log visit list event.
$params = array(
    'context' => $context,
    'objectid' => $oublog->id,
    'other' => array(
        'info' => 'all participation',
        'logurl' => 'participationlist.php?id=' . $cm->id
    )
);
$event = \mod_oublog\event\participation_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();
