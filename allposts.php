<?php
/**
 * This page prints all non-private personal oublog posts
 *
 * @author Jenny Gray <j.m.gray@open.ac.uk>
 * @package oublog
 */

require_once('../../config.php');
require_once('locallib.php');

$offset = optional_param('offset', 0, PARAM_INT);   // Offset for paging
$tag    = optional_param('tag', null, PARAM_TAG);   // Tag to display

if (!$oublog = $DB->get_record("oublog",array("global"=>1))) { // the personal blogs module
    print_error('personalblognotsetup','oublog');
}

if (!$cm = get_coursemodule_from_instance('oublog',$oublog->id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

$url = new moodle_url('/mod/oublog/allposts.php', array('offset'=>$offset, 'tag'=>$tag));
$PAGE->set_url($url);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);

$oublogoutput = $PAGE->get_renderer('mod_oublog');

/// Check security
$blogtype = 'personal';
$returnurl = 'allposts.php?';

if ($tag) {
    $returnurl .= '&amp;tag='.urlencode($tag);
}

$canmanageposts = has_capability('mod/oublog:manageposts', $context);
$canaudit       = has_capability('mod/oublog:audit', $context);

/// Log visit
add_to_log($course->id, "oublog", "allposts", $returnurl, $oublog->id, $cm->id);

/// Get strings
$stroublog      = get_string('modulename', 'oublog');
$strnewposts    = get_string('newerposts', 'oublog');
$strolderposts  = get_string('olderposts', 'oublog');
$strfeeds       = get_string('feeds', 'oublog');

$strfeeds       .= '<img src="'.$OUTPUT->pix_url('i/rss').'" alt="'.get_string('blogfeed', 'oublog').'"  class="feedicon" />';
$strblogsearch  = get_string('searchblogs', 'oublog');

/// Get Posts
list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, null, -1, null, $tag, $canaudit);

$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(format_string($oublog->name), new moodle_url('/mod/oublog/allposts.php'));
$CFG->additionalhtmlhead .= oublog_get_meta_tags($oublog, 'all', '', $cm);

/// Generate extra navigation
if ($offset) {
    $a = new stdClass();
    $a->from = ($offset+1);
    $a->to   = (($recordcount - $offset) > OUBLOG_POSTS_PER_PAGE) ? $offset + OUBLOG_POSTS_PER_PAGE : $recordcount;
    $PAGE->navbar->add(get_string('extranavolderposts', 'oublog', $a));
} elseif (!empty($tag)) {
    $PAGE->navbar->add(get_string('extranavtag', 'oublog', $tag));
}

if(oublog_search_installed()) {
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
$url = new moodle_url("$CFG->wwwroot/course/mod.php", array('update' => $cm->id, 'return' => true, 'sesskey' => sesskey()));
$buttontext .= $OUTPUT->single_button($url, $stroublog);
$PAGE->set_button($buttontext);

$PAGEWILLCALLSKIPMAINDESTINATION = true; // OU accessibility feature

echo $OUTPUT->header();


print '<div class="oublog-topofpage"></div>';

// The left column ...
if($hasleft=!empty($CFG->showblocksonmodpages) && blocks_have_content($pageblocks, BLOCK_POS_LEFT) ) {
    print '<div id="left-column">';
    blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
    print '</div>';
}

// The right column, BEFORE the middle-column.
print '<div id="right-column">';
if (isloggedin() and !isguestuser()) {
    list($oublog, $oubloginstance) = oublog_get_personal_blog($USER->id);
    $blogeditlink = "<br /><a href=\"view.php\" class=\"oublog-links\">$oubloginstance->name</a>";
    print_side_block(format_string($oublog->name), $blogeditlink, NULL, NULL, NULL, array('id' => 'oublog-summary'),get_string('bloginfo','oublog'));
}

if ($feeds = oublog_get_feedblock($oublog, 'all', '', false, $cm)) {
    print_side_block($strfeeds, $feeds, NULL, NULL, NULL, array('id' => 'oublog-feeds'),$strfeeds);
}

print '</div>';

// Start main column
$classes='';
$classes.=$hasleft ? 'has-left-column ' : '';
$classes.='has-right-column ';
$classes=trim($classes);
if($classes) {
    print '<div id="middle-column" class="'.$classes.'">';
} else {
    print '<div id="middle-column">';
}
print skip_main_destination();

// Print blog posts
if ($posts) {
    echo '<div id="oublog-posts">';
    if ($offset > 0) {
        if ($offset-OUBLOG_POSTS_PER_PAGE == 0) {
            echo "<a href=\"$returnurl\">$strnewposts</a>";
        } else {
            echo "<a href=\"$returnurl&amp;offset=".($offset-OUBLOG_POSTS_PER_PAGE)."\">$strnewposts</a>";
        }
    }

    foreach ($posts as $post) {
        echo $oublogoutput->oublog_print_post($cm, $oublog, $post, $returnurl, $blogtype, $canmanageposts, $canaudit);
    }

    if ($recordcount - $offset > OUBLOG_POSTS_PER_PAGE) {
        echo "<a href=\"$returnurl&amp;offset=".($offset+OUBLOG_POSTS_PER_PAGE)."\">$strolderposts</a>";
    }
    echo '</div>';
}

// Print information allowing the user to log in if necessary, or letting
// them know if there are no posts in the blog
if(!isloggedin() || isguestuser()) {
    print '<p class="oublog_loginnote">'.
        get_string('maybehiddenposts','oublog',
            'bloglogin.php').'</p>';
} else if(!$posts) {
    print '<p class="oublog_noposts">'.
        get_string('noposts','oublog').'</p>';
}

/// Finish the page
echo $OUTPUT->footer();
