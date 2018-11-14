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
$cmid = optional_param('cmid', null, PARAM_INT);

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
$childdata = oublog_get_blog_data_base_on_cmid_of_childblog($cmid, $oublog);
$childcm = null;
$childoublog = null;
$childcourse = null;
if (!empty($childdata)) {
    $context = $childdata['context'];
    $childcm = $childdata['cm'];
    $childoublog = $childdata['ousharedblog'];
    $childcourse = $childdata['course'];
    oublog_check_view_permissions($childdata['ousharedblog'], $childdata['context'], $childdata['cm']);

} else {
    oublog_check_view_permissions($oublog, $context, $cm);
}
$correctindividual = isset($childoublog->individual) ? $childoublog->individual : $oublog->individual;
$correctglobal = isset($childoublog->global) ? $childoublog->global : $oublog->global;
$PAGE->set_context($context);

$url = new moodle_url('/mod/oublog/search.php', array('id'=>$id, 'user'=>$user, 'query'=>$querytext));
$PAGE->set_url($url);
$PAGE->set_cm($childcm ? $childcm : $cm);
$PAGE->set_title(format_string($childoublog ? $childoublog->name : $oublog->name));

if ($correctglobal) {
    // Check this user is allowed to view the user's blog
    $maxvisibility = isset($childoublog->maxvisibility) ? $childoublog->maxvisibility : $oublog->maxvisibility;
    if ($maxvisibility != OUBLOG_VISIBILITY_PUBLIC && isset($oubloguser)) {
        $usercontext = context_user::instance($oubloguser->id);
        require_capability('mod/oublog:view', $usercontext);
    }
    $returnurl = $CFG->wwwroot . "/mod/oublog/search.php?user=$user&query=$querytext";
    $mreturnurl = new moodle_url('/mod/oublog/view.php', array('user'=>$user));
} else {
    $cmparam = $cmid ? '&cmid=' . $cmid : null;
    $returnurl = $CFG->wwwroot . "/mod/oublog/search.php?id=$id&query=$querytext$cmparam";
    $mreturnurl = new moodle_url('/mod/oublog/view.php', array('id'=>$id));
}

// Set up groups
$currentgroup = oublog_get_activity_group($childcm ? $childcm : $cm, true);
$groupmode = oublog_get_activity_groupmode($childcm ? $childcm : $cm, $childcourse ? $childcourse : $course);
// Note I am not sure this check is necessary, maybe it is handled by
// oublog_get_activity_group? Or maybe more checks are needed? Not sure.
if ($currentgroup===0 && $groupmode==SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups', $context);
}

if ($correctindividual) {
    // Individual selector.
    $individualdetails = oublog_individual_get_activity_details($cm, $returnurl, $childoublog ? $childoublog : $oublog,
            $currentgroup, $context);
}

// Print the header
$stroublog      = get_string('modulename', 'oublog');
$strblogsearch = get_string('searchthisblog', 'oublog', oublog_get_displayname($childoublog ?  $childoublog :$oublog));
$strblogssearch  = get_string('searchblogs', 'oublog');


if ($correctglobal) {
    if (!is_null($oubloginstance)) {
        $name = $oubloginstance->name;
        $buttontext = oublog_get_search_form('user', $oubloguser->id, $strblogsearch,
                $querytexthtml, false, $cmid);
    } else {
        $buttontext = oublog_get_search_form('id', $cm->id, $strblogssearch,
                $querytexthtml, false, $cmid);
    }

    if (isset($name)) {
        $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php', array('id'=>$oubloguser->id)));
        $PAGE->navbar->add(format_string($oubloginstance->name), $mreturnurl);
    } else {
        $PAGE->navbar->add(format_string($oublog->name), new moodle_url('/mod/oublog/allposts.php'));
    }

} else {
    $name = !empty($childoublog->name) ? $childoublog->name : $oublog->name;
    $buttontext = oublog_get_search_form('id', $cm->id, $strblogsearch, $querytexthtml, false, $cmid);
}

$PAGE->navbar->add(get_string('searchfor', 'local_ousearch', $querytext));
$PAGE->set_button($buttontext);

echo $OUTPUT->header();

// Print Groups and individual drop-down menu.
echo html_writer::start_div('oublog-groups-individual-selectors');

// Print Groups
groups_print_activity_menu($childcm ? $childcm : $cm, $returnurl);

if ($correctindividual && $individualdetails) {
    echo $individualdetails->display;
}

echo html_writer::end_div();

$modulecontext = $context;

// FINALLY do the actual query
$query=new local_ousearch_search($querytext);
$query->set_coursemodule($cm);
if ($correctglobal && isset($oubloguser)) {
    $query->set_user_id($oubloguser->id);
} else if ($correctindividual != OUBLOG_NO_INDIVIDUAL_BLOGS) {
    if (!empty($individualdetails->activeindividual)) {
        // Only get results for currently selected user.
        $query->set_user_id($individualdetails->activeindividual, false);
    } else if ($groupmode && $currentgroup) {
        // All individual, get results for all users in current group.
        $sepcontext = $correctindividual == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS ? $context : 0;
        $usersingroup = oublog_individual_get_all_users($course->id, $oublog->id,
                $currentgroup, $sepcontext);
        if ($usersingroup) {
            $query->set_user_ids(array_keys($usersingroup), false);
        } else {
            // Stop groups with no members returning all results.
            $query->set_user_ids(local_ousearch_search::NONE, false);
        }
    }
}

if ($groupmode && $currentgroup && $correctindividual == OUBLOG_NO_INDIVIDUAL_BLOGS) {
    $query->set_group_id($currentgroup);
}
$query->set_filter(function($result) use($modulecontext, $oublog, $childoublog, $cm, $childcm) {
    global $USER;
    return oublog_can_view_post($result->data, $USER, $modulecontext, $cm, $oublog, $childcm, $childoublog);
});
$searchurl = 'search.php?' . (empty($id) ? 'user=' . $oubloguser->id : 'id='. $cm->id) . ($cmid) ? '&cmid=' . $cmid : '';

$foundsomething=$query->display_results($searchurl);
if ($cmid) {
    // We should add cmid for search result if this is shared blog.
    $foundsomething = oublog_add_cmid_to_tag_atrribute($cmid, $foundsomething, 'a', 'href', '&');
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
