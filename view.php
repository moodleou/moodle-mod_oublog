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
 * This page prints a particular instance of oublog
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 */

require_once('../../config.php');
require_once('locallib.php');

$id     = optional_param('id', 0, PARAM_INT);       // Course Module ID.
$user   = optional_param('user', 0, PARAM_INT);     // User ID.
$offset = optional_param('offset', 0, PARAM_INT);   // Offset fo paging.
$tag    = optional_param('tag', null, PARAM_TAG);   // Tag to display.

$url = new moodle_url('/mod/oublog/view.php', array('id'=>$id, 'user'=>$user, 'offset'=>$offset,
        'tag'=>$tag));
$PAGE->set_url($url);

if ($id) {
    // Load efficiently (and with full $cm data) using get_fast_modinfo.
    $course = $DB->get_record_select('course',
            'id = (SELECT course FROM {course_modules} WHERE id = ?)', array($id),
            '*', MUST_EXIST);
    $modinfo = get_fast_modinfo($course);
    $cm = $modinfo->get_cm($id);
    if ($cm->modname !== 'oublog') {
        print_error('invalidcoursemodule');
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
} else if (isloggedin()) {
    redirect('view.php?user='.$USER->id);
} else {
    redirect('bloglogin.php');
}

// The mod_edit page gets it wrong when redirecting to a personal blog.
// Since there's no way to know what personal blog was being updated
// this redirects to the users own blog.
if ($oublog->global && empty($user)) {
    redirect('view.php?user='.$USER->id);
    exit;
}

// If viewing a course blog that requires login, but you're not logged in,
// this causes odd behaviour in OU systems, so redirect to bloglogin.php.
if ($oublog->maxvisibility != OUBLOG_VISIBILITY_PUBLIC && !isloggedin()) {
    redirect('bloglogin.php?returnurl=' .
            substr($FULLME, strpos($FULLME, 'view.php')));
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
oublog_check_view_permissions($oublog, $context, $cm);
$oublogoutput = $PAGE->get_renderer('mod_oublog');

// Check security.
$canpost        = oublog_can_post($oublog, $user, $cm);
$canmanageposts = has_capability('mod/oublog:manageposts', $context);
$canaudit       = has_capability('mod/oublog:audit', $context);

// Get strings.
$stroublogs     = get_string('modulenameplural', 'oublog');
$stroublog      = get_string('modulename', 'oublog');
$straddpost     = get_string('newpost', 'oublog');
$strtags        = get_string('tags', 'oublog');
$stredit        = get_string('edit', 'oublog');
$strdelete      = get_string('delete', 'oublog');
$strnewposts    = get_string('newerposts', 'oublog');
$strolderposts  = get_string('olderposts', 'oublog');
$strcomment     = get_string('comment', 'oublog');
$strviews       = get_string('views', 'oublog');
$strlinks       = get_string('links', 'oublog');
$strfeeds       = get_string('feeds', 'oublog');
$strblogsearch  = get_string('searchthisblog', 'oublog');

// Set-up groups.
$groupmode = oublog_get_activity_groupmode($cm, $course);
$currentgroup = oublog_get_activity_group($cm, true);

if (!oublog_is_writable_group($cm)) {
    $canpost=false;
    $canmanageposts=false;
    $cancomment=false;
    $canaudit=false;
}

if (isset($cm)) {
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

// Print the header.
$PAGEWILLCALLSKIPMAINDESTINATION = true;
$hideunusedblog=false;

if ($oublog->global) {
    $blogtype = 'personal';
    $returnurl = $CFG->wwwroot . '/mod/oublog/view.php?user='.$user;

    $name = $oubloginstance->name;

    $buttontext = oublog_get_search_form('user', $oubloguser->id, $strblogsearch);
} else {
    $blogtype = 'course';
    $returnurl = $CFG->wwwroot . '/mod/oublog/view.php?id='.$id;

    $name = $oublog->name;

    $buttontext = oublog_get_search_form('id', $cm->id, $strblogsearch);
}

if ($tag) {
    $returnurl .= '&amp;tag='.urlencode($tag);
}

// Set-up individual.
$currentindividual = -1;
$individualdetails = 0;

// Set up whether the group selector should display.
$showgroupselector = true;
if ($oublog->individual) {
    // If separate individual and visible group, do not show groupselector
    // unless the current user has permission.
    if ($oublog->individual == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS
        && !has_capability('mod/oublog:viewindividual', $context)) {
        $showgroupselector = false;
    }

    $canpost=true;
    $individualdetails = oublog_individual_get_activity_details($cm, $returnurl, $oublog,
            $currentgroup, $context);
    if ($individualdetails) {
        $currentindividual = $individualdetails->activeindividual;
        if (!$individualdetails->newblogpost) {
            $canpost=false;
        }
    }
}

// Get Posts.
list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, $currentgroup,
        $currentindividual, $oubloguser->id, $tag, $canaudit);



$hideunusedblog=!$posts && !$canpost && !$canaudit;

if ($oublog->global && !$hideunusedblog) {
    // Bit about hidden with if global then $posts
    // In order to prevent people from looping through numbers to get the
    // name of every user in the site (in case these names are considered
    // private), don't display the header when not displaying posts, except
    // to users who can post.
    oublog_build_navigation($oublog, $oubloginstance, $oubloguser);
} else {
    oublog_build_navigation($oublog, $oubloginstance, null);

}
if (!$hideunusedblog) {
    // Generate extra navigation.
    $CFG->additionalhtmlhead .= oublog_get_meta_tags($oublog, $oubloginstance, $currentgroup, $cm);
    $PAGE->set_button($buttontext);
    if ($offset) {
        $a = new stdClass();
        $a->from = ($offset+1);
        $a->to   = (($recordcount - $offset) > OUBLOG_POSTS_PER_PAGE) ? $offset +
                OUBLOG_POSTS_PER_PAGE : $recordcount;
        $PAGE->navbar->add(get_string('extranavolderposts', 'oublog', $a));
    }
    if ($tag) {
        $PAGE->navbar->add(get_string('extranavtag', 'oublog', $tag));
    }
}
$PAGE->set_title(format_string($oublog->name));
$PAGE->set_heading(format_string($oublog->name));


// Initialize $PAGE, compute blocks.
$editing = $PAGE->user_is_editing();

// The left column ...
$hasleft = !empty($CFG->showblocksonmodpages) || $editing;
// The right column, BEFORE the middle-column.
if (!$hideunusedblog) {
    global $USER, $CFG;
    $links = '';
    if ($oublog->global) {
        $title = $oubloginstance->name;
        $summary = $oubloginstance->summary;
        if (($oubloginstance->userid == $USER->id) || $canmanageposts ) {
            $params = array('instance' => $oubloginstance->id);
            $editinstanceurl = new moodle_url('/mod/oublog/editinstance.php', $params);
            $streditinstance = get_string('blogoptions', 'oublog');
            $links .= html_writer::start_tag('div', array('class' => 'oublog-links'));
            $links .= html_writer::link($editinstanceurl, $streditinstance);
            $links .= html_writer::end_tag('div');
        }
        $allpostsurl = new moodle_url('/mod/oublog/allposts.php');
        $strallposts = get_string('siteentries', 'oublog');
        $links .= html_writer::start_tag('div', array('class' => 'oublog-links'));
        $links .= html_writer::link($allpostsurl, $strallposts);
        $links .= html_writer::end_tag('div');
    } else {
        $summary = $oublog->summary;
        $title = $oublog->name;
    }
    // Name, summary, related links.
    $bc = new block_contents();
    $bc->attributes['class'] = 'oublog-sideblock block';
    $bc->title = format_string($title);
    $bc->content = format_text($summary . $links);
    $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);

    // Tag Cloud.
    if ($tags = oublog_get_tag_cloud($returnurl, $oublog, $currentgroup, $cm, $oubloginstanceid, $currentindividual)) {
        $bc = new block_contents();
        $bc->attributes['id'] = 'oublog-tags';
        $bc->attributes['class'] = 'oublog-sideblock block';
        $bc->title = $strtags;
        $bc->content = $tags;
        $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
    }

    // Links.
    $links = oublog_get_links($oublog, $oubloginstance, $context);
    if ($links) {
        $bc = new block_contents();
        $bc->attributes['id'] = 'oublog-links';
        $bc->attributes['class'] = 'oublog-sideblock block';
        $bc->title = $strlinks;
        $bc->content = $links;
        $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
    }

    // Feeds.
    if ($feeds = oublog_get_feedblock($oublog, $oubloginstance, $currentgroup, false, $cm, $currentindividual)) {
        $feedicon = ' <img src="'.$OUTPUT->pix_url('i/rss').'" alt="'.get_string('blogfeed', 'oublog').'"  class="feedicon" />';
        $bc = new block_contents();
        $bc->attributes['class'] = 'oublog-sideblock block';
        $bc->title = $strfeeds . $feedicon;
        $bc->content = $feeds;
        $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
    }
}
// Must be called after add_fake_blocks.
echo $OUTPUT->header();

// Start main column.
$classes='';

$classes.=$hasleft ? 'has-left-column ' : '';
$classes.='has-right-column ';

$classes=trim($classes);
if ($classes) {
    print '<div id="middle-column" class="'.$classes.'">';
} else {
    print '<div id="middle-column">';
}

echo $OUTPUT->skip_link_target();

// Print Groups and individual drop-down menu.
echo '<div class="oublog-groups-individual-selectors">';

// Print Groups.
if ($showgroupselector) {
    groups_print_activity_menu($cm, $returnurl);
}
// Print Individual.
if ($oublog->individual) {
    if ($individualdetails) {
        echo $individualdetails->display;
        $individualmode = $individualdetails->mode;
        $currentindividual = $individualdetails->activeindividual;
    }
}
echo '</div>';

// Print the main part of the page.

echo '<div id="oublogbuttons">';

// New post button - in group blog, you can only post if a group is selected.
if ($oublog->individual && $individualdetails) {
    $showpostbutton = $canpost;
} else {
    $showpostbutton = $canpost && ($currentgroup || !$groupmode );
}
if ($showpostbutton) {
    echo '<div id="addpostbutton">';
    echo $OUTPUT->single_button(new moodle_url('/mod/oublog/editpost.php', array('blog' =>
            $cm->instance)), $straddpost, 'get');
    echo '</div>';
}

// View participation button.
$canview = oublog_can_view_participation($course, $oublog, $cm, $currentgroup);
if ($canview) {
    if ($canview == OUBLOG_MY_PARTICIPATION) {
        if (groups_is_member($currentgroup, $USER->id) || !$currentgroup) {
            $strparticipation = get_string('myparticipation', 'oublog');
            $participationurl = new moodle_url('userparticipation.php', array('id' => $cm->id,
                    'group' => $currentgroup, 'user' => $USER->id));
        }
    } else {
        $strparticipation = get_string('participationbyuser', 'oublog');
        $participationurl = new moodle_url('participation.php', array('id' => $cm->id,
                'group' => $currentgroup));
    }
    if (isset($participationurl)) {
        echo '<div class="participationbutton">';
        echo $OUTPUT->single_button($participationurl, $strparticipation, 'get');
        echo '</div>';
    }
}

echo '</div>';

// Print blog posts.
if ($posts) {
    echo '<div id="oublog-posts">';
    if ($offset > 0) {
        if ($offset-OUBLOG_POSTS_PER_PAGE == 0) {
            print "<div class='oublog-newerposts'><a href=\"$returnurl\">$strnewposts</a></div>";
        } else {
            print "<div class='oublog-newerposts'><a href=\"$returnurl&amp;offset=" .
                    ($offset-OUBLOG_POSTS_PER_PAGE) . "\">$strnewposts</a></div>";
        }
    }

    foreach ($posts as $post) {
        echo $oublogoutput->render_post($cm, $oublog, $post, $returnurl, $blogtype,
                $canmanageposts, $canaudit, true, false);
    }

    if ($recordcount - $offset > OUBLOG_POSTS_PER_PAGE) {
        print "<div class='oublog-olderposts'><a href=\"$returnurl&amp;offset=" .
                ($offset+OUBLOG_POSTS_PER_PAGE) . "\">$strolderposts</a></div>";
    }
    echo '</div>';
}

// Print information allowing the user to log in if necessary, or letting
// them know if there are no posts in the blog.
if (isguestuser() && $USER->id==$user) {
    print '<p class="oublog_loginnote">'.
        get_string('guestblog', 'oublog',
            'bloglogin.php?returnurl='.urlencode($returnurl)).'</p>';
} else if (!isloggedin() || isguestuser()) {
    print '<p class="oublog_loginnote">'.
        get_string('maybehiddenposts', 'oublog',
            'bloglogin.php?returnurl='.urlencode($returnurl)).'</p>';
} else if (!$posts) {
    print '<p class="oublog_noposts">'.
        get_string('noposts', 'oublog').'</p>';
}

// Log visit and bump view count.
add_to_log($course->id, "oublog", "view", $returnurl, $oublog->id, $cm->id);
$views = oublog_update_views($oublog, $oubloginstance);

// Finish the page.
echo "<div class=\"clearer\"></div><div class=\"oublog-views\">$strviews $views</div></div>";

echo $OUTPUT->footer();
