<?php
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
    print_error('invalidblog','oublog');
}
if (!$oublog = $DB->get_record("oublog", array("id"=>$oubloginstance->oublogid))) {
    print_error('invalidblog','oublog');
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

/// Check security
if (!$oublog->global) {
    print_error('onlyworkspersonal','oublog');
}
$url = new moodle_url('/mod/oublog/editinstance.php', array('instance'=>$bloginstancesid, 'post'=>$postid));
$PAGE->set_url($url);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);
$oubloguser = $DB->get_record('user',array('id'=>$oubloginstance->userid));
$viewurl = 'view.php?user='.$oubloginstance->userid;

if ($USER->id != $oubloginstance->userid && !has_capability('mod/oublog:manageposts', $context)) {
    print_error('accessdenied','oublog');
}

/// Get strings
$stroublogs     = get_string('modulenameplural', 'oublog');
$stroublog      = get_string('modulename', 'oublog');
$straddpost     = get_string('newpost', 'oublog');
$streditpost    = get_string('editpost', 'oublog');
$strblogoptions = get_string('blogoptions', 'oublog');

/// Set-up groups
$currentgroup = oublog_get_activity_group($cm, true);
$groupmode = oublog_get_activity_groupmode($cm, $course);

$mform = new mod_oublog_mod_form('editinstance.php', array('maxvisibility' => $oublog->maxvisibility, 'edit' => !empty($postid)));

if ($mform->is_cancelled()) {
    redirect($viewurl);
    exit;
}

if (!$frmoubloginstance = $mform->get_data()) {

    $oubloginstance->instance = $oubloginstance->id;
    $mform->set_data($oubloginstance);

/// Print the header
    oublog_build_navigation($oublog, $oubloginstance,$oubloguser);
    $PAGE->navbar->add($strblogoptions);
    $PAGE->set_title(format_string($oublog->name));
    echo $OUTPUT->header();

    echo '<br />';
    $mform->display();

    echo $OUTPUT->footer();

} else {
    /// Handle form submission
    $frmoubloginstance->id = $frmoubloginstance->instance;
    $frmoubloginstance->message = $frmoubloginstance->summary;
    $DB->update_record('oublog_instances', $frmoubloginstance);

    redirect($viewurl);
}