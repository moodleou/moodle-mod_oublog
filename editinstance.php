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
 * This page allows a user to edit their personal blog
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */
define('OUBLOG_EDIT_INSTANCE', true);

require_once('../../config.php');
require_once('locallib.php');
require_once('lib.php');
require_once('mod_form.php');

$bloginstancesid = required_param('instance', PARAM_INT);        // Bloginstance
$postid = optional_param('post', 0, PARAM_INT);   // Post ID for editing

if (!$oubloginstance = $DB->get_record('oublog_instances', array('id'=>$bloginstancesid))) {
    print_error('invalidblog', 'oublog');
}
if (!$oublog = $DB->get_record("oublog", array("id"=>$oubloginstance->oublogid))) {
    print_error('invalidblog', 'oublog');
}
if (!$oublog->global) {
    print_error('invalidblog', 'oublog');
}
if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record("course", array("id"=>$oublog->course))) {
    print_error('invalidcoursemodule');
}

// Check security.
if (!$oublog->global) {
    print_error('onlyworkspersonal', 'oublog');
}
$url = new moodle_url('/mod/oublog/editinstance.php', array('instance'=>$bloginstancesid, 'post'=>$postid));
$PAGE->set_url($url);

$context = context_module::instance($cm->id);
oublog_check_view_permissions($oublog, $context, $cm);
$oubloguser = $DB->get_record('user', array('id'=>$oubloginstance->userid));
$viewurl = 'view.php?user='.$oubloginstance->userid;

if ($USER->id != $oubloginstance->userid && !has_capability('mod/oublog:manageposts', $context)) {
    print_error('accessdenied', 'oublog');
}

// Get strings.
$stroublogs     = get_string('modulenameplural', 'oublog');
$stroublog      = get_string('modulename', 'oublog');
$streditpost    = get_string('editpost', 'oublog');
$strblogoptions = get_string('blogoptions', 'oublog');

// Set-up groups.
$currentgroup = oublog_get_activity_group($cm, true);
$groupmode = oublog_get_activity_groupmode($cm, $course);

$mform = new mod_oublog_mod_form('editinstance.php', array('maxvisibility' => $oublog->maxvisibility, 'edit' => !empty($postid),
            'postperpage' => $oublog->postperpage));

if ($mform->is_cancelled()) {
    redirect($viewurl);
    exit;
}

$textfieldoptions = array(
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => $CFG->maxbytes,
        'context' => $context,
        );

if (!$frmoubloginstance = $mform->get_data()) {

    $oubloginstance->instance = $oubloginstance->id;
    $oubloginstance->summaryformat = FORMAT_HTML;
    $oubloginstance = file_prepare_standard_editor($oubloginstance, 'summary', $textfieldoptions, $context,
            'mod_oublog', 'summary', $oubloginstance->id);
    $mform->set_data($oubloginstance);

    // Print the header.
    oublog_build_navigation($oublog, $oubloginstance, $oubloguser);
    $PAGE->navbar->add($strblogoptions);
    $PAGE->set_title(format_string($oublog->name));
    echo $OUTPUT->header();

    echo '<br />';
    $mform->display();

    echo $OUTPUT->footer();

} else {
    // Handle form submission.
    $frmoubloginstance->id = $frmoubloginstance->instance;
    $frmoubloginstance->summaryformat = FORMAT_HTML;
    $frmoubloginstance = file_postupdate_standard_editor($frmoubloginstance, 'summary', $textfieldoptions, $context,
            'mod_oublog', 'summary', $frmoubloginstance->id);
    $DB->update_record('oublog_instances', $frmoubloginstance);

    redirect($viewurl);
}