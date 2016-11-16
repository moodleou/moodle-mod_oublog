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
 * Page for saving grades for all or one user participation
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
$userid     = optional_param('user', 0, PARAM_INT);

$params = array();
$params['id'] = $id;
$params['group'] = $groupid;
$url = new moodle_url('/mod/oublog/savegrades.php');
if ($id) {
    $cm = get_coursemodule_from_id('oublog', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $oublog = $DB->get_record('oublog', array('id' => $cm->instance), '*', MUST_EXIST);

    $PAGE->set_cm($cm);
}
$context = context_module::instance($cm->id);
require_course_login($course, true, $cm);
require_sesskey();

// participation capability check
$canview = oublog_can_view_participation($course, $oublog, $cm, $groupid);
if ($canview != OUBLOG_USER_PARTICIPATION) {
    print_error('nopermissiontoshow');
}

// grading capability check
if (!oublog_can_grade($course, $oublog, $cm, $groupid)) {
    print_error('nopermissiontoshow');
}

$mode = '';
if (!empty($_POST['menu'])) {
    $gradeinfo = $_POST['menu'];
    $oldgrades = oublog_get_participation($oublog, $context, $groupid, $cm, $course);
} else if ($userid && !empty($_POST['grade'])) {
    $gradeinfo[$userid] = $_POST['grade'];
    $user = oublog_get_user_participation($oublog, $context, $userid, $groupid, $cm, $course);
    $oldgrades = array($userid => $user);
}

// Update grades.
if (!empty($gradeinfo)) {
    oublog_update_manual_grades($gradeinfo, $oldgrades, $cm, $oublog, $course);
}

// redirect
redirect('participation.php?id=' . $id . '&group=' . $groupid);
