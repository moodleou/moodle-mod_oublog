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
 * Search results page.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package oublog
 *//** */
require_once('../../config.php');
require_once('locallib.php');
require_once($CFG->dirroot.'/local/ousearch/searchlib.php');

$id     = optional_param('id', 0, PARAM_INT);       // Course Module ID
$user   = optional_param('user', 0, PARAM_INT);     // User ID
$querytext = required_param('query', PARAM_RAW);
$querytexthtml = htmlspecialchars($querytext);

if ($id) {
    if (!$cm = get_coursemodule_from_id('oublog', $id)) {
        print_error('invalidcoursemodule');
    }

    if (!$course = $DB->get_record("course", array("id"=>$cm->course))) {
        print_error('coursemisconf');
    }

    if (!$oublog = $DB->get_record("oublog", array("id"=>$cm->instance))) {
        print_error('invalidcoursemodule');
    }
    $oubloguser = (object) array('id' => null);
    $oubloginstance = null;
    $oubloginstanceid = null;

} else if ($user) {
    if (!$oubloguser = $DB->get_record('user', array('id'=>$user))) {
        print_error('invaliduserid');
    }
    if (!list($oublog, $oubloginstance) = oublog_get_personal_blog($oubloguser->id)) {
        print_error('invalidcoursemodule');
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id"=>$oublog->course))) {
        print_error('coursemisconf');
    }
    $oubloginstanceid = $oubloginstance->id;
} else {
    print_error('missingrequiredfield');
}

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

$url = new moodle_url('/mod/oublog/search.php', array('id'=>$id, 'user'=>$user, 'query'=>$querytext));
$PAGE->set_url($url);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($oublog->name));
oublog_check_view_permissions($oublog, $context, $cm);

if ($oublog->global) {
    // Check this user is allowed to view the user's blog
    if ($oublog->maxvisibility != OUBLOG_VISIBILITY_PUBLIC && isset($oubloguser)) {
        $usercontext = context_user::instance($oubloguser->id);
        require_capability('mod/oublog:view', $usercontext);
    }
    $returnurl = $CFG->wwwroot . '/mod/oublog/view.php?user='.$user;
    $mreturnurl = new moodle_url('/mod/oublog/view.php', array('user'=>$user));
} else {
    $returnurl = $CFG->wwwroot . '/mod/oublog/view.php?id='.$id;
    $mreturnurl = new moodle_url('/mod/oublog/view.php', array('id'=>$id));
}

// Set up groups
$currentgroup = oublog_get_activity_group($cm, true);
$groupmode = oublog_get_activity_groupmode($cm, $course);
// Note I am not sure this check is necessary, maybe it is handled by
// oublog_get_activity_group? Or maybe more checks are needed? Not sure.
if ($currentgroup===0 && $groupmode==SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups', $context);
}

// Print the header
$stroublog      = get_string('modulename', 'oublog');
$strblogsearch = get_string('searchthisblog', 'oublog', oublog_get_displayname($oublog));
$strblogssearch  = get_string('searchblogs', 'oublog');


if ($oublog->global) {
    if (!is_null($oubloginstance)) {
        $name = $oubloginstance->name;
        $buttontext = oublog_get_search_form('user', $oubloguser->id, $strblogsearch,
                $querytexthtml);
    } else {
        $buttontext = oublog_get_search_form('id', $cm->id, $strblogssearch,
                $querytexthtml);
    }

    if (isset($name)) {
        $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id'=>$oubloguser->id)));
        $PAGE->navbar->add(format_string($oubloginstance->name), $mreturnurl);
    } else {
        $PAGE->navbar->add(format_string($oublog->name), new moodle_url('/mod/oublog/allposts.php'));
    }

} else {
    $name = $oublog->name;

    $buttontext = oublog_get_search_form('id', $cm->id, $strblogsearch, $querytexthtml);
}

$PAGE->navbar->add(get_string('searchfor', 'local_ousearch', $querytext));
$PAGE->set_button($buttontext);

echo $OUTPUT->header();

// Print Groups
groups_print_activity_menu($cm, $returnurl);

global $modulecontext, $personalblog;
$modulecontext=$context;
$personalblog=$oublog->global ? true : false;

// FINALLY do the actual query
$query=new local_ousearch_search($querytext);
$query->set_coursemodule($cm);
if ($oublog->global && isset($oubloguser)) {
    $query->set_user_id($oubloguser->id);
}
if ($groupmode && $currentgroup) {
    $query->set_group_id($currentgroup);
}
$query->set_filter('visibility_filter');

$searchurl = 'search.php?'.(empty($id) ? 'user='.$oubloguser->id : 'id='.$cm->id);

$foundsomething=$query->display_results($searchurl);

if (!$foundsomething) {
    add_to_log($COURSE->id, 'oublog', 'view searchfailure',
        $searchurl.'&query='.urlencode($querytext));
}
echo $foundsomething;

// Add link to search the rest of this website if service available.
if (!empty($CFG->block_resources_search_baseurl)) {
    $params = array('course' => $course->id, 'query' => $querytext);
    $restofwebsiteurl = new moodle_url('/blocks/resources_search/search.php', $params);
    $strrestofwebsite = get_string('restofwebsite', 'local_ousearch');
    $altlink = html_writer::start_tag('div', array('class' => 'advanced-search-link'));
    $altlink .= html_writer::link($restofwebsiteurl, $strrestofwebsite);
    $altlink .= html_writer::end_tag('div');
    print $altlink;
}

// Footer
echo $OUTPUT->footer();

/**
 * Function filters search results to exclude ones that don't meet the
 * visibility criterion.
 *
 * @param object $result Search result data
 */
function visibility_filter(&$result) {
    global $USER, $modulecontext, $personalblog;
    return oublog_can_view_post($result->data, $USER, $modulecontext, $personalblog);
}
