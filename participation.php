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

$id         = required_param('id', PARAM_INT); // Course Module ID
$groupid    = optional_param('group', 0, PARAM_INT);
$download   = optional_param('download', '', PARAM_TEXT);
$page       = optional_param('page', 0, PARAM_INT); // flexible_table page

$params = array(
    'id'        => $id,
    'group'     => $groupid,
    'download'  => $download,
    'page'      => $page,
);
$url = new moodle_url('/mod/oublog/participation.php', $params);
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
if ($canview != OUBLOG_USER_PARTICIPATION) {
    print_error('nopermissiontoshow');
}
$viewfullnames = has_capability('moodle/site:viewfullnames', $context);

$groupname = '';
if ($groupid) {
    $groupname = $DB->get_field('groups', 'name', array('id' => $groupid));
}

// set up whether the group selector should display
$showgroupselector = true;
$masterblog = null;
$cmmaster = null;
$coursemaster = null;
if ($oublog->individual) {
    // if separate individual and visible group, do not show groupselector
    // unless the current user has permission
    if ($oublog->individual == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS
        && !has_capability('mod/oublog:viewindividual', $context)) {
        $showgroupselector = false;
    }

    // Get master blog.
    if ($oublog->idsharedblog) {
        $masterblog = oublog_get_master($oublog->idsharedblog);

        // Get cm master.
        if (!$cmmaster = get_coursemodule_from_instance('oublog', $masterblog->id)) {
            throw new moodle_exception('invalidcoursemodule');
        }

        // Get course master.
        if (!$coursemaster = $DB->get_record('course', array('id' => $masterblog->course))) {
            throw new moodle_exception('coursemisconf');
        }
    }
}
// Get CM master.
$participationcm = !empty($cmmaster) ? $cmmaster : $cm;
// All enrolled users for table pagination.
$coursecontext = context_course::instance($course->id);
// If data has been received from this form.
$curgroup = -1;
if ($cm->groupmode > NOGROUPS) {
    // Get currently viewed group.
    $curgroup = optional_param('curgroup', oublog_get_activity_group($cm), PARAM_INT);
}
// Create time filter options form.
$default = get_user_preferences('mod_oublog_postformfilter', OUBLOG_STATS_TIMEFILTER_ALL);
    // Create time filter options form.
    $customdata = array(
            'type' => 'participation',
            'cmid' => $participationcm->id,
            'user' => $USER->id,
            'group' => $groupid,
            'download' => $download,
            'page' => $page,
            'startyear' => $course->startdate,
            'params' => array('curgroup', $curgroup)
    );
$timefilter = new oublog_participation_timefilter_form(null, $customdata);

// If data has been received from this form.
$start = $end = 0;
$info = 'The info needs to be provided';
if ($submitted = $timefilter->get_data()) {
    if ($submitted->start) {
        $start = strtotime('00:00:00', $submitted->start);
    }
    if ($submitted->end) {
        $end = strtotime('23:59:59', $submitted->end);
    }
}

$participation = oublog_get_participation($oublog, $context, $groupid, $cm, $course, $start, $end,
        'u.firstname,u.lastname', $masterblog, $cmmaster, $coursemaster);
$PAGE->navbar->add(get_string('userparticipation', 'oublog'));
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($oublog->name));

// Log visit list event.
$params = array(
    'context' => $context,
    'objectid' => $oublog->id,
    'other' => array(
        'info' => 'user participation',
        'logurl' => 'participation.php?id=' . $cm->id
    )
);
$event = \mod_oublog\event\participation_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$oublogoutput = $PAGE->get_renderer('mod_oublog');

if (empty($download)) {
    echo $OUTPUT->header();

    // Gets a message after grades updated.
    if (isset($SESSION->oubloggradesupdated)) {
        $message = $SESSION->oubloggradesupdated;
        unset($SESSION->oubloggradesupdated);
        echo $OUTPUT->notification($message, 'notifysuccess');
    }

    // Print Groups drop-down menu.
    echo '<div class="oublog-groups-individual-selectors">';
    $returnurl = $CFG->wwwroot . '/mod/oublog/participation.php?id=' . $cm->id;
    if ($showgroupselector) {
        groups_print_activity_menu($cm, $returnurl);
    }
    echo '</div>';
}

if (!$start && !$end) {
    $title = get_string('participation_all', 'oublog');
    $info = get_string('participation_all', 'oublog');
}
$startdate = userdate($start);
$enddate = userdate($end);
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
if (empty($download)) {
    echo html_writer::tag('h2', $info,
            array('class' => 'oublog-post-title'));
    $timefilter->display();
}

$oublogoutput->render_participation_list($cm, $course, $oublog, $groupid,
    $download, $page, $participation, $coursecontext, $viewfullnames,
    $groupname);

echo $oublogoutput->get_link_back_to_oublog($cm->name, $cm->id);

if (empty($download)) {
    echo $OUTPUT->footer();
}
