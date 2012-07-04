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
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
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
if ($oublog->individual) {
    // if separate individual and visible group, do not show groupselector
    // unless the current user has permission
    if ($oublog->individual == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS
        && !has_capability('mod/oublog:viewindividual', $context)) {
        $showgroupselector = false;
    }
}

// all enrolled users for table pagination
$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
$participation = oublog_get_participation($oublog, $context, $groupid, $cm, $course);

$PAGE->navbar->add(get_string('userparticipation', 'oublog'));
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($oublog->name));

$oublogoutput = $PAGE->get_renderer('mod_oublog');

if (empty($download)) {
    echo $OUTPUT->header();

    // gets a message after grades updated
    if (isset($SESSION->oubloggradesupdated)) {
        $message = $SESSION->oubloggradesupdated;
        unset($SESSION->oubloggradesupdated);
        echo $OUTPUT->notification($message, 'notifysuccess');
    }

    /// Print Groups drop-down menu
    echo '<div class="oublog-groups-individual-selectors">';
    $returnurl = $CFG->wwwroot . '/mod/oublog/participation.php?id=' . $cm->id;
    if ($showgroupselector) {
        groups_print_activity_menu($cm, $returnurl);
    }
    echo '</div>';
}

$oublogoutput->render_participation_list($cm, $course, $oublog, $groupid,
    $download, $page, $participation, $coursecontext, $viewfullnames,
    $groupname);

if (empty($download)) {
    echo $OUTPUT->footer();
}

/// Log visit
$logurl = "participation.php?id={$id}&group={$groupid}&download={$download}&page={$page}";
add_to_log($course->id, 'oublog', 'view', $logurl, $oublog->id, $cm->id);
