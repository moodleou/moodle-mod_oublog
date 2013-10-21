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
 * Subscribe to or unsubscribe from a oublog or manage oublog subscription mode
 *
 * This script can be used by either individual users to subscribe to or
 * unsubscribe from a oublog (no 'mode' param provided), or by oublog managers
 * to control the subscription mode (by 'mode' param).
 * This script can be called from a link in email so the sesskey is not
 * required parameter. However, if sesskey is missing, the user has to go
 * through a confirmation page that redirects the user back with the
 * sesskey.
 *
 * @package    mod
 * @subpackage oublog
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/oublog/lib.php');
require_once('locallib.php');

$id      = required_param('id', PARAM_INT);             // the oublogid of the oublog to subscribe or unsubscribe to
$user    = optional_param('user', 0, PARAM_INT);        // userid of the user to subscribe, defaults to $USER
$sesskey = optional_param('sesskey', null, PARAM_RAW);  // sesskey

$url = new moodle_url('/mod/oublog/subscribe.php', array('id'=>$id));

if ($user !== 0) {
    $url->param('user', $user);
}
if (!is_null($sesskey)) {
    $url->param('sesskey', $sesskey);
}
$PAGE->set_url($url);


$oublog   = $DB->get_record('oublog', array('id' => $id), '*', MUST_EXIST);
$course  = $DB->get_record('course', array('id' => $oublog->course), '*', MUST_EXIST);
$cm      = get_coursemodule_from_instance('oublog', $oublog->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($cm->id);

if ($user) {
    require_sesskey();
    $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
} else {
    $user = $USER;
}

if (isset($cm->groupmode) && empty($course->groupmodeforce)) {
    $groupmode = $cm->groupmode;
} else {
    $groupmode = $course->groupmode;
}
if ($groupmode && !oublog_is_subscribed($user->id, $oublog)) {
    if (!groups_get_all_groups($course->id, $USER->id)) {
        print_error('cannotsubscribe', 'oublog');
    }
}

require_login($course, false, $cm);

if (!is_enrolled($context, $USER, '', true)) {   // Guests and visitors can't subscribe - only enrolled
    $PAGE->set_title($course->shortname);
    $PAGE->set_heading($course->fullname);
    if (isguestuser()) {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('subscribeenrolledonly', 'oublog').'<br /><br />'.get_string('liketologin'),
                     get_login_url(), new moodle_url('/mod/oublog/view.php', array('id'=>$cm->id)));
        echo $OUTPUT->footer();
        exit;
    } else {
        // there should not be any links leading to this place, just redirect
        redirect(new moodle_url('/mod/oublog/view.php', array('id'=>$cm->id)), get_string('subscribeenrolledonly', 'oublog'));
    }
}

$returnto = optional_param('backtoindex',0,PARAM_INT)
    ? "index.php?id=".$course->id
    : "view.php?id=".$cm->id;


$info = new stdClass();
$info->name  = fullname($user);
$info->oublog = format_string($oublog->name);

if (oublog_is_subscribed($user->id, $oublog->id)) {
	if ($oublog->forcesubscribe == OUBLOG_FORCESUBSCRIBE) {
		print_error('forcesubscribe', 'oublog', $_SERVER["HTTP_REFERER"]);
	}
    if (is_null($sesskey)) {    // we came here via link in email
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('confirmunsubscribe', 'oublog', format_string($oublog->name)),
                new moodle_url($PAGE->url, array('sesskey' => sesskey())), new moodle_url('/mod/oublog/view.php', array('id' => $cm->id)));
        echo $OUTPUT->footer();
        exit;
    }
    require_sesskey();
    if (oublog_unsubscribe($user->id, $id)) {
        add_to_log($course->id, "oublog", "unsubscribe", "view.php?id=$cm->id", $oublog->id, $cm->id);
        redirect($returnto, get_string("nownotsubscribed", "oublog", $info), 1);
    } else {
        print_error('cannotunsubscribe', 'oublog', $_SERVER["HTTP_REFERER"]);
    }

} else {  // subscribe
    if ($oublog->forcesubscribe == OUBLOG_DISALLOWSUBSCRIBE) {
        print_error('disallowsubscribe', 'oublog', $_SERVER["HTTP_REFERER"]);
    }
    if (is_null($sesskey)) {    // we came here via link in email
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('confirmsubscribe', 'oublog', format_string($oublog->name)),
                new moodle_url($PAGE->url, array('sesskey' => sesskey())), new moodle_url('/mod/oublog/view.php', array('id' => $cm->id)));
        echo $OUTPUT->footer();
        exit;
    }
    require_sesskey();
    oublog_subscribe($user->id, $id);
    add_to_log($course->id, "oublog", "subscribe", "view.php?id=$cm->id", $oublog->id, $cm->id);
    redirect($returnto, get_string("nowsubscribed", "oublog", $info), 1);
}
