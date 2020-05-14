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
$username = optional_param('u', '', PARAM_USERNAME);// User login name.
$tag    = optional_param('tag', null, PARAM_TAG);   // Tag to display.
$page = optional_param('page', 0, PARAM_INT);
$tagorder = optional_param('tagorder', '', PARAM_ALPHA);// Tag display order.
$taglimit = optional_param('taglimit', OUBLOG_TAGS_SHOW, PARAM_INT);// Tag display order.

// Set user value if u (username) set.
if ($username != '') {
    if (!$oubloguser = $DB->get_record('user', array('username' => $username))) {
        print_error('invaliduser');
    }
    $user = $oubloguser->id;
}

if (isloggedin()) {
    // Determine tag order to use.
    if ($tagorder != '') {
        set_user_preference('oublog_tagorder', $tagorder);
    } else {
        $tagorder = get_user_preferences('oublog_tagorder', 'alpha');
    }
} else {
    // Use 'alpha'.
    $tagorder = 'alpha';
}

$url = new moodle_url('/mod/oublog/view.php', array('id' => $id, 'user' => $user,
        'page' => $page, 'tag' => $tag, 'tagorder' => $tagorder, 'taglimit' => $taglimit));

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

    if (!$oublog = $DB->get_record('oublog', array('id' => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
    $oubloguser = (object) array('id' => null);
    $oubloginstance = null;
    $oubloginstanceid = null;

} else if ($user) {
    if (!isset($oubloguser)) {
        if (!$oubloguser = $DB->get_record('user', array('id' => $user))) {
            print_error('invaliduserid');
        }
    }
    if (!list($oublog, $oubloginstance) = oublog_get_personal_blog($oubloguser->id)) {
        print_error('invalidcoursemodule');
    }
    if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $oublog->course))) {
        print_error('coursemisconf');
    }
    $oubloginstanceid = $oubloginstance->id;
} else if (isloggedin()) {
    redirect('view.php?user='.$USER->id);
} else {
    redirect('bloglogin.php');
}
$postperpage = $oublog->postperpage;
$offset = $page * $postperpage;

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

$context = context_module::instance($cm->id);
oublog_check_view_permissions($oublog, $context, $cm);
$oublogoutput = $PAGE->get_renderer('mod_oublog');

// Hook for pre-page-display output (if any).
$oublogoutput->pre_display($cm, $oublog, 'view');

// Check security.
$canpost        = oublog_can_post($oublog, $user, $cm);
$canmanageposts = has_capability('mod/oublog:manageposts', $context);
$canaudit       = has_capability('mod/oublog:audit', $context);

// Get strings.
$stroublogs     = get_string('modulenameplural', 'oublog');
$stroublog      = get_string('modulename', 'oublog');
$straddpost = get_string('newpost', 'oublog', oublog_get_displayname($oublog));
$strexportposts = get_string('oublog:exportposts', 'oublog');
$strtags        = get_string('tags', 'oublog');
$stredit        = get_string('edit', 'oublog');
$strdelete      = get_string('delete', 'oublog');
$strnewposts    = get_string('newerposts', 'oublog');
$strolderposts  = get_string('olderposts', 'oublog');
$strcomment     = get_string('comment', 'oublog');
$strviews = get_string('views', 'oublog', oublog_get_displayname($oublog));
$strlinks       = get_string('links', 'oublog');
$strfeeds       = get_string('feeds', 'oublog');
$strblogsearch = get_string('searchthisblog', 'oublog', oublog_get_displayname($oublog));

// Set-up groups.
$groupmode = oublog_get_activity_groupmode($cm, $course);
$currentgroup = oublog_get_activity_group($cm, true);

if (!oublog_is_writable_group($cm)) {
    $canpost = false;
    $canmanageposts = false;
    $cancomment = false;
    $canaudit = false;
}

if (isset($cm)) {
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

// Print the header.
$hideunusedblog = false;

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
if ($taglimit) {
    $returnurl .= '&amp;taglimit='.urlencode($taglimit);
}

// Set-up individual.
$currentindividual = -1;
$individualdetails = 0;

// Set up whether the group selector should display.
$showgroupselector = true;
$masterblog = null;
$cmmaster = null;
$coursemaster = null;
if ($oublog->individual) {
    // If separate individual and visible group, do not show groupselector
    // unless the current user has permission.
    if ($oublog->individual == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS
        && !has_capability('mod/oublog:viewindividual', $context)) {
        $showgroupselector = false;
    }

    // Get master blog.
    if ($oublog->idsharedblog) {
        $masterblog = oublog_get_master($oublog->idsharedblog);

        // Get cm master.
        if (!$cmmaster = get_coursemodule_from_instance('oublog', $masterblog->id)) {
            throw new moodle_exception('invalidcoursemodule');
        }

        // Get course master.
        if (!$coursemaster = $DB->get_record('course', array('id' => $masterblog->course))) {
            throw new moodle_exception('coursemisconf');
        }
    }

    $canpost = true;
    $individualdetails = oublog_individual_get_activity_details($cmmaster ? $cmmaster : $cm, $returnurl, $oublog,
                $currentgroup, $context);
    if ($individualdetails) {
        $currentindividual = $individualdetails->activeindividual;
        if (!$individualdetails->newblogpost) {
            $canpost = false;
        }
    }

}
// Get current blog.
$postsoublog = !empty($masterblog) ? $masterblog : $oublog;

// Get Posts.
list($posts, $recordcount) = oublog_get_posts($oublog, $context, $offset, $cm, $currentgroup,
        $currentindividual, $oubloguser->id, $tag, $canaudit, null, $masterblog);


$hideunusedblog = !$posts && !$canpost && !$canaudit;

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
    if ($offset > 0) {
        $a = new stdClass();
        $a->from = ($offset + 1);
        $a->to = (($recordcount - $offset) > $postperpage) ? $offset + $postperpage : $recordcount;
        $PAGE->navbar->add(get_string('extranavolderposts', 'oublog', $a));
    }
    if ($tag) {
        $PAGE->navbar->add(get_string('extranavtag', 'oublog', $tag));
    }
}
$blogname = format_string($oublog->name);
$PAGE->set_title($blogname);
$PAGE->set_heading($blogname);

// Initialize $PAGE, compute blocks.
$editing = $PAGE->user_is_editing();

// The left column ...
$hasleft = !empty($CFG->showblocksonmodpages);
// The right column, BEFORE the middle-column.
if (!$hideunusedblog) {
    global $USER, $CFG;
    $links = '';
    // Search block.
    if ($oublog->global && strtolower($CFG->theme) == 'osep') {
        $buttontext = oublog_get_search_form('user', $oubloguser->id, $strblogsearch, '', true);
        $bc = new block_contents();
        $bc->attributes['class'] = 'oublog-sideblock block';
        $bc->attributes['id'] = 'oublog_info_block_search';
        $bc->title = format_string(get_string('searchblogs', 'mod_oublog'));
        $bc->content = $buttontext;
        if (!empty($bc->content)) {
            $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
        }
    }

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
        if (empty($CFG->oublogallpostslogin) || isloggedin()) {
            $allpostsurl = new moodle_url('/mod/oublog/allposts.php');
            $strallposts = get_string('siteentries', 'oublog');
            $links .= html_writer::start_tag('div', array('class' => 'oublog-links'));
            $links .= html_writer::link($allpostsurl, $strallposts);
            $links .= html_writer::end_tag('div');
        }
        $format = FORMAT_HTML;
    } else {
        $summary = $oublog->intro;
        $title = $oublog->name;
        $format = $oublog->introformat;
    }

    // Name, summary, related links.
    $bc = new block_contents();
    $bc->attributes['class'] = 'oublog-sideblock block';
    $bc->attributes['id'] = 'oublog_info_block';
    $bc->title = format_string($title);
    if ($oublog->global) {
        $bc->content = file_rewrite_pluginfile_urls($summary, 'mod/oublog/pluginfile.php',
                $context->id, 'mod_oublog', 'summary', $oubloginstance->id);
        $bc->content = format_text($bc->content, $format);
        $bc->content = $oublogoutput->render_summary($bc->content, $oubloguser);
    } else {
        $bc->content = file_rewrite_pluginfile_urls($summary, 'pluginfile.php',
                $context->id, 'mod_oublog', 'intro', null);
        $bc->content = format_text($bc->content, $format);
    }
    $bc->content = $bc->content . $links;
    if (!empty($bc->content)) {
        $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
    }

    // Tag Cloud.
    list ($tags, $currentfiltertag) = oublog_get_tag_cloud($returnurl, $oublog, $currentgroup, $cm,
            $oubloginstanceid, $currentindividual, $tagorder, $masterblog, $taglimit);
    if ($tags) {
        $bc = new block_contents();
        $bc->attributes['id'] = 'oublog-tags';
        $bc->attributes['class'] = 'oublog-sideblock block';
        $bc->attributes['data-osepid'] = $id . '_oublog_blocktags';
        $bc->title = $strtags;
        $bc->content = $oublogoutput->render_tag_order($tagorder);
        if ($currentfiltertag) {
            $bc->content .= $oublogoutput->render_current_filter($currentfiltertag, $returnurl);
        }
        $bc->content .= $tags;
        $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
    }

    // Links.
    $links = oublog_get_links($postsoublog, $oubloginstance, $context, $cm->id);
    if ($links) {
        $bc = new block_contents();
        $bc->attributes['id'] = 'oublog-links';
        $bc->attributes['class'] = 'oublog-sideblock block';
        $bc->attributes['data-osepid'] = $id . '_oublog_blocklinks';
        $bc->title = $strlinks;
        $bc->content = $links;
        $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
    }

    // Discovery block.
    $stats = array();
    $stats[] = oublog_stats_output_myparticipation($oublog, $cm, $oublogoutput, $course, $currentindividual, $oubloguser->id,
            $masterblog, $cmmaster, $coursemaster);
    $stats[] = oublog_stats_output_participation($oublog, $cm, $oublogoutput, $course, false, $currentindividual, $oubloguser->id,
            $masterblog);
    $stats[] = oublog_stats_output_commentpoststats($oublog, $cm, $oublogoutput, false, $masterblog, $cmmaster,
            false, $currentindividual, $oubloguser->id);
    if ($oublog->statblockon) {
        // Add to 'Discovery' block when enabled only.
        $stats[] = oublog_stats_output_visitstats($oublog, $cm, $oublogoutput);
        $stats[] = oublog_stats_output_poststats($oublog, $cm, $oublogoutput, false, $masterblog, $cmmaster);
        $stats[] = oublog_stats_output_commentstats($oublog, $cm, $oublogoutput, false, $masterblog, $cmmaster);
    }
    $stats = array_filter($stats);
    if (!empty($stats)) {
        // Open the first block by default.
        $stats = $oublogoutput->render_stats_container('view', $stats, 1);
        $bc = new block_contents();
        $bc->attributes['id'] = 'oublog-discover';
        $bc->attributes['class'] = 'oublog-sideblock block';
        $bc->attributes['data-osepid'] = $id . '_oublog_blockdiscovery';
        $bc->title = get_string('discovery', 'oublog', oublog_get_displayname($oublog, true));
        $bc->content = $stats;
        $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
    }

    // Feeds.
    if ($feeds = oublog_get_feedblock($oublog, $oubloginstance, $currentgroup, false, $cm, $currentindividual,
                $masterblog)) {
        $feedicon = ' <img src="'.$OUTPUT->image_url('i/rss').'" alt="'.get_string('blogfeed', 'oublog').'"  class="feedicon" />';
        $bc = new block_contents();
        $bc->attributes['id'] = 'oublog-feeds';
        $bc->attributes['class'] = 'oublog-sideblock block';
        $bc->attributes['data-osepid'] = $id . '_oublog_blockfeeds';
        $bc->title = $strfeeds;
        $bc->content = $feeds;
        $PAGE->blocks->add_fake_block($bc, BLOCK_POS_RIGHT);
    }
}

// Show portfolio export link.
// Will need to be passed enough details on the blog so it can accurately work out what
// posts are displayed (as oublog_get_posts above).
if (!empty($CFG->enableportfolios) && (has_capability('mod/oublog:exportpost', $context))) {
    require_once($CFG->libdir . '/portfoliolib.php');

    if ($canaudit) {
        $canaudit = 1;
    } else {
        $canaudit = 0;
    }
    if (empty($oubloguser->id)) {
        $oubloguserid = 0;
    } else {
        $oubloguserid = $oubloguser->id;
    }
    $tagid = null;
    if (!is_null($tag)) {
        // Make tag work with portfolio param cleaning by looking up id.
        if ($tagrec = $DB->get_record('oublog_tags', array('tag' => $tag), 'id')) {
            $tagid = $tagrec->id;
        }
    }

    // Note: render_export_button_top and render_export_button_bottom are added to
    // support the OSEP design which includes the export button differently from the old OU theme.
    if (!empty($posts)) {
        $oublogoutput->render_export_button_top($context, $postsoublog, null, $oubloguserid,
                $canaudit, $offset, $currentgroup, $currentindividual, $tagid, $cm, $course->id, $masterblog ? 1 : 0);
    }
}

// Must be called after add_fake_blocks.
echo $OUTPUT->header();

// Start main column.
print '<div id="middle-column" class="has-right-column">';

echo $OUTPUT->skip_link_target();

echo $oublogoutput->render_header($cm, $oublog, 'view');

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

if (!$hideunusedblog) {
    // Renderer hook so extra info can be added to global blog pages in theme.
    echo $oublogoutput->render_viewpage_prepost();
}
// Print the main part of the page.

// New post button - in group blog, you can only post if a group is selected.
if ($oublog->individual && $individualdetails) {
    $showpostbutton = $canpost;
} else {
    $showpostbutton = $canpost && ($currentgroup || !$groupmode );
}

// If timed blog posts show info.
$capable = has_capability('mod/oublog:ignorepostperiod',
        $oublog->global ? context_system::instance() : $context);

if (($showpostbutton || $capable) && $oublog->postfrom != 0 && $oublog->postfrom > time()) {
    echo $oublogoutput->render_time_limit_msg('beforestartpost', $oublog->postfrom, $capable);
}
if (($showpostbutton || $capable) && $oublog->postuntil != 0) {
    if ($oublog->postuntil > time()) {
        echo $oublogoutput->render_time_limit_msg('beforeendpost', $oublog->postuntil, $capable);
    } else {
        echo $oublogoutput->render_time_limit_msg('afterendpost', $oublog->postuntil, $capable);
    }
}
// If timed comments show info.
if ($posts) {
    $maxpost = (object) array('allowcomments' => false, 'visibility' => OUBLOG_VISIBILITY_COURSEUSER);
    foreach ($posts as $apost) {
        // Work out if any posts on page allow commenting + max visibility.
        if ($apost->allowcomments) {
            $maxpost->allowcomments = true;
        }
        if ($apost->visibility > $maxpost->visibility) {
            $maxpost->visibility = $apost->visibility;
        }
    }
    if (oublog_can_comment($cm, $oublog, $maxpost, true)) {
        $ccapable = has_capability('mod/oublog:ignorecommentperiod',
                $oublog->global ? context_system::instance() : $context);
        if ($oublog->commentfrom != 0 && $oublog->commentfrom > time()) {
            echo $oublogoutput->render_time_limit_msg('beforestartcomment', $oublog->commentfrom, $capable, 'comment');
        }
        if ($oublog->commentuntil != 0) {
            if ($oublog->commentuntil > time()) {
                echo $oublogoutput->render_time_limit_msg('beforeendcomment', $oublog->commentuntil, $capable, 'comment');
            } else {
                echo $oublogoutput->render_time_limit_msg('afterendcomment', $oublog->commentuntil, $capable, 'comment');
            }
        }
    }
}

echo '<div id="oublogbuttons">';

if ($showpostbutton && oublog_can_post_now($oublog, $context)) {
    echo '<div id="addpostbutton">';
    echo $OUTPUT->single_button(new moodle_url('/mod/oublog/editpost.php',
        array('blog' => $cmmaster ? $cmmaster->instance : $cm->instance,
                'cmid' => $masterblog ? $cm->id : 0)), $straddpost, 'get');
    echo '</div>';
    if ($oublog->allowimport && ($oublog->global ||
            $oublog->individual != OUBLOG_NO_INDIVIDUAL_BLOGS)) {
        echo '<div class="oublog_importpostbutton">';
        $importparams = $cmmaster ? ['id' => $cmmaster->id, 'cmid' => $cm->id] : ['id' => $cm->id];
        echo $OUTPUT->single_button(new moodle_url('/mod/oublog/import.php',
                $importparams), get_string('import', 'oublog'), 'get');
        echo '</div>';
    }
}

// View participation button.
$canview = oublog_can_view_participation($course, $oublog, $cm, $currentgroup);
if ($canview) {
    if ($canview == OUBLOG_USER_PARTICIPATION) {
        $strparticipation = get_string('participationbyuser', 'oublog');
        $participationurl = new moodle_url('participation.php', array('id' => $cm->id,
                'group' => $currentgroup));
        echo '<div class="participationbutton">';
        echo $OUTPUT->single_button($participationurl, $strparticipation, 'get');
        echo '</div>';
    }
}

echo '</div>';

// Print blog posts.
if ($posts) {
    if ($recordcount > $postperpage) {
        echo "<div class='oublog-paging'>";
        echo $OUTPUT->paging_bar($recordcount, $page, $postperpage, $returnurl);
        echo '</div>';
    }
    echo '<div id="oublog-posts">';
    $rowcounter = 1;
    // Only add page onto returnurl within call to render post.
    $retnurl = $returnurl . '&page=' . $page;
    foreach ($posts as $post) {
        $post->row = $rowcounter;
        echo $oublogoutput->render_post($cm, $oublog, $post, $retnurl, $blogtype,
                $canmanageposts, $canaudit, true, false, false, false, 'top', $cmmaster, $masterblog ? $cm->id : null);
        $rowcounter++;
    }
    if ($recordcount > $postperpage) {
        echo "<div class='oublog-paging'>";
        echo $OUTPUT->paging_bar($recordcount, $page, $postperpage, $returnurl);
        echo '</div>';
    }
    echo '</div>';

    // Show portfolio export link.
    // Will need to be passed enough details on the blog so it can accurately work out what
    // posts are displayed (as oublog_get_posts above).
    if (!empty($CFG->enableportfolios) && (has_capability('mod/oublog:exportpost', $context))) {
        echo $oublogoutput->render_export_button_bottom($context, $postsoublog, null, $oubloguserid,
                $canaudit, $offset, $currentgroup, $currentindividual, $tagid, $cm, $masterblog ? 1 : 0);
    }
}
// Print information allowing the user to log in if necessary, or letting
// them know if there are no posts in the blog.
if (isguestuser() && $USER->id == $user) {
    print '<p class="oublog_loginnote">'.
            get_string('guestblog', 'oublog',
                    'bloglogin.php?returnurl='.urlencode($returnurl)).'</p>';
} else if (!isloggedin() || isguestuser()) {
    print '<p class="oublog_loginnote">'.
            get_string('maybehiddenposts', 'oublog',
                    (object) array('link' => 'bloglogin.php?returnurl='.urlencode($returnurl),
                            'name' => oublog_get_displayname($oublog))).'</p>';
} else if (!$posts) {
    if (!$tag) {
        $errormessage = get_string('noposts', 'oublog', oublog_get_displayname($oublog));
    } else {
        $a = array('blog' => oublog_get_displayname($oublog), 'tag' => $tag);
        $errormessage = get_string('nopostsnotags', 'oublog', $a);
    }
    print '<p class="oublog_noposts">' . $errormessage . ' </p>';
}

// Log oublog page view.
$params = array(
    'context' => $context,
    'objectid' => $oublog->id,
);
$event = \mod_oublog\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->trigger();

$views = oublog_update_views($postsoublog, $oubloginstance, $currentindividual, $currentgroup);

// Finish the page.
echo "<div class=\"clearer\"></div><div class=\"oublog-views\">$strviews $views</div></div>";


echo $OUTPUT->footer();
