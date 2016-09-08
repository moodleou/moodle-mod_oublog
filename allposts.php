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
 * This page prints all non-private personal oublog posts
 *
 * @author Jenny Gray <j.m.gray@open.ac.uk>
 * @package oublog
 */

require_once('../../config.php');
require_once('locallib.php');

$tag    = optional_param('tag', null, PARAM_TAG);   // Tag to display.
$page = optional_param('page', 0, PARAM_INT);

if (!$oublog = $DB->get_record("oublog", array("global"=>1))) { // The personal blogs module.
    print_error('personalblognotsetup', 'oublog');
}

if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

$offset = $page * OUBLOG_POSTS_PER_PAGE;
$url = new moodle_url('/mod/oublog/allposts.php', array(
        'page' => $page,
        'tag' => $tag));
$PAGE->set_url($url);

$context = context_module::instance($cm->id);
if (!empty($CFG->oublogallpostslogin) && $oublog->maxvisibility == OUBLOG_VISIBILITY_PUBLIC) {
    // Set blog visibility temporarily to loggedin user to force login to this page.
    $oublog->maxvisibility = OUBLOG_VISIBILITY_LOGGEDINUSER;
    oublog_check_view_permissions($oublog, $context, $cm);
    $oublog->maxvisibility = OUBLOG_VISIBILITY_PUBLIC;
} else {
    oublog_check_view_permissions($oublog, $context, $cm);
}

$oublogoutput = $PAGE->get_renderer('mod_oublog');

// Check security.
$blogtype = 'personal';
$returnurl = 'allposts.php?';

if ($tag) {
    $returnurl .= '&amp;tag='.urlencode($tag);
}

$canmanageposts = has_capability('mod/oublog:manageposts', $context);
$canaudit       = has_capability('mod/oublog:audit', $context);

// Log view all site entries event.
$params = array(
        'context' => $context,
        'objectid' => $oublog->id,
        'other' => array(
            'pageid' => $page
    )
);
$event = \mod_oublog\event\site_entries_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->trigger();

// Get strings.
$stroublog      = get_string('modulename', 'oublog');
$strnewposts    = get_string('newerposts', 'oublog');
$strolderposts  = get_string('olderposts', 'oublog');
$strfeeds       = get_string('feeds', 'oublog');

$strblogsearch  = get_string('searchblogs', 'oublog');

// Get Posts.
list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, null, -1, null,
        $tag, $canaudit, true);

$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(format_string($oublog->name), new moodle_url('/mod/oublog/allposts.php'));
$CFG->additionalhtmlhead .= oublog_get_meta_tags($oublog, 'all', '', $cm);

// Generate extra navigation.
if ($offset) {
    $a = new stdClass();
    $a->from = ($offset+1);
    $a->to   = (($recordcount - $offset) > OUBLOG_POSTS_PER_PAGE) ? $offset +
            OUBLOG_POSTS_PER_PAGE : $recordcount;
    $PAGE->navbar->add(get_string('extranavolderposts', 'oublog', $a));
} else if (!empty($tag)) {
    $PAGE->navbar->add(get_string('extranavtag', 'oublog', $tag));
}

if (oublog_search_installed()) {
    $buttontext=<<<EOF
<form action="search.php" method="get"><div>
  <input type="text" name="query" value=""/>
  <input type="hidden" name="id" value="{$cm->id}"/>
  <input type="submit" value="{$strblogsearch}"/>
</div></form>
EOF;
} else {
    $buttontext='';
}
$url = new moodle_url("$CFG->wwwroot/course/mod.php",
        array('update' => $cm->id, 'return' => true, 'sesskey' => sesskey()));
$PAGE->set_button($buttontext);

// The right column, BEFORE the middle-column.
if (isloggedin() and !isguestuser()) {
    list($oublog, $oubloginstance) = oublog_get_personal_blog($USER->id);
    $blogeditlink = "<br /><a href=\"view.php\" class=\"oublog-links\">$oubloginstance->name</a>";
    $bc = new block_contents();
    $bc->attributes['id'] = 'oublog-links';
    $bc->attributes['class'] = 'oublog-sideblock block';
    $bc->title = format_string($oublog->name);
    $bc->content = $blogeditlink;
    $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
}

if ($oublog->statblockon) {
    // 'Discovery' block.
    $stats = array();
    $stats[] = oublog_stats_output_participation($oublog, $cm, $oublogoutput, $course, true);
    $stats[] = oublog_stats_output_commentpoststats($oublog, $cm, $oublogoutput, false, true);
    $stats[] = oublog_stats_output_visitstats($oublog, $cm, $oublogoutput);
    $stats[] = oublog_stats_output_poststats($oublog, $cm, $oublogoutput);
    $stats[] = oublog_stats_output_commentstats($oublog, $cm, $oublogoutput);
    $stats = $oublogoutput->render_stats_container('allposts', $stats);
    $bc = new block_contents();
    $bc->attributes['id'] = 'oublog-discover';
    $bc->attributes['class'] = 'oublog-sideblock block';
    $bc->title = get_string('discovery', 'oublog', oublog_get_displayname($oublog, true));
    $bc->content = $stats;
    if (!empty($stats)) {
        $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
    }
}

if ($feeds = oublog_get_feedblock($oublog, 'all', '', false, $cm)) {
    $bc = new block_contents();
    $bc->attributes['id'] = 'oublog-feeds';
    $bc->attributes['class'] = 'oublog-sideblock block';
    $bc->title = $strfeeds;
    $bc->content = $feeds;
    $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
}
// Must be called after add_fake_blocks.
echo $OUTPUT->header();
// Start main column.
print '<div id="middle-column" class="has-right-column">';

print $OUTPUT->skip_link_target();

// Renderer hook so extra info can be added to global blog pages in theme.
echo $oublogoutput->render_viewpage_prepost();

// Print blog posts.
if ($posts) {
    echo "<div class='oublog-paging'>";
    echo $OUTPUT->paging_bar($recordcount, $page, OUBLOG_POSTS_PER_PAGE, $returnurl);
    echo '</div>';
    echo '<div id="oublog-posts">';
    $rowcounter = 1;
    foreach ($posts as $post) {
        $post->row = $rowcounter;
        echo $oublogoutput->render_post($cm, $oublog, $post, $returnurl, $blogtype,
                $canmanageposts, $canaudit, true, false);
        $rowcounter++;
    }
    echo "<div class='oublog-paging'>";
    echo $OUTPUT->paging_bar($recordcount, $page, OUBLOG_POSTS_PER_PAGE, $returnurl);
    echo '</div>';
}

// Print information allowing the user to log in if necessary, or letting
// them know if there are no posts in the blog.
if (!isloggedin() || isguestuser()) {
    print '<p class="oublog_loginnote">' . get_string('maybehiddenposts', 'oublog',
            (object) array('link' => 'bloglogin.php', 'name' => oublog_get_displayname($oublog))) . '</p>';
} else if (!$posts) {
    print '<p class="oublog_noposts">' . get_string('noposts', 'oublog', oublog_get_displayname($oublog)) . '</p>';
}
print '</div>';
// Finish the page.
echo $OUTPUT->footer();
