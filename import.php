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
 * Import pages into the current blog
 * Supports imports from Individual blog to Individual blog (same user)
 *
 * @package    mod
 * @subpackage oublog
 * @copyright  2013 The open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/oublog/locallib.php');

$id = required_param('id', PARAM_INT);// Blog cm ID.

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

$context = context_module::instance($cm->id);
$tempoublog = clone $oublog;
if ($tempoublog->global) {
    $tempoublog->maxvisibility = OUBLOG_VISIBILITY_LOGGEDINUSER;// Force login regardless of setting.
} else {
    $tempoublog->maxvisibility = OUBLOG_VISIBILITY_COURSEUSER;// Force login regardless of setting.
}
oublog_check_view_permissions($tempoublog, $context, $cm);
$blogname = oublog_get_displayname($oublog);

// Is able to import check for current blog.
if (!$oublog->allowimport ||
        (!$oublog->global && $oublog->individual == OUBLOG_NO_INDIVIDUAL_BLOGS)) {
    // Must have import enabled. Individual blog mode only.
    print_error('import_notallowed', 'oublog', null, $blogname);
}
// Check if group mode set - need to check user is in selected group etc.
$groupmode = oublog_get_activity_groupmode($cm, $course);
$currentgroup = 0;
if ($groupmode != NOGROUPS) {
    $currentgroup = oublog_get_activity_group($cm);
    $ingroup = groups_is_member($currentgroup);
    if ($oublog->individual != OUBLOG_NO_INDIVIDUAL_BLOGS && ($currentgroup && !$ingroup)) {
        // Must be group memeber for individual blog with group mode on.
        print_error('import_notallowed', 'oublog', null, $blogname);
    }
}

$step = optional_param('step', 0, PARAM_INT);
if (optional_param('cancel', '', PARAM_ALPHA) == get_string('cancel')) {
    $step -= 2;// Go back 2 steps if cancel.
}
$oublogoutput = $PAGE->get_renderer('mod_oublog');

// Page header.
$params = array('id' => $id);
$PAGE->set_url('/mod/oublog/import.php', $params);
$errlink = new moodle_url('/mod/oublog/import.php', $params);
$PAGE->set_title(get_string('import', 'oublog'));
$PAGE->navbar->add(get_string('import', 'oublog'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('import', 'oublog'));

echo html_writer::start_div('oublog_import_step oublog_import_step' . $step);

if ($step == 0) {
    // Show list of oublog activities user has access to import from.
    echo html_writer::tag('p', get_string('import_step0_inst', 'oublog'));
    $curcourse = -1;
    $blogs = oublog_import_getblogs($USER->id, $cm->id);
    try {
        if ($remoteblogs = oublog_import_remote_call('mod_oublog_get_user_blogs',
                array('username' => $USER->username))) {
            $blogs = array_merge($blogs, $remoteblogs);
        }
    } catch (moodle_exception $e) {
        // Ignore fail when contacting external server, keep message for debugging.
        debugging($e->getMessage());
    }
    $personalblogout = '';
    $blogout = '';
    foreach ($blogs as $bloginfo) {
        if ($bloginfo->coursename != '' && $curcourse != $bloginfo->coursename) {
            if ($curcourse != -1) {
                $blogout .= html_writer::end_tag('ul');
            }
            $blogout .= $OUTPUT->heading($bloginfo->coursename, 3);
            $blogout .= html_writer::start_tag('ul');
            $curcourse = $bloginfo->coursename;
        }
        // Use this activity icon for all blogs (in case from another server).
        $img = html_writer::empty_tag('img', array('src' => $cm->get_icon_url($oublogoutput),
                'alt' => ''));
        $bloglink = '';
        if ($bloginfo->numposts) {
            $url = new moodle_url('/mod/oublog/import.php', $params +
                    array('step' => 1, 'bid' => $bloginfo->cmid));
            if (isset($bloginfo->remote)) {
                $url->param('remote', true);
            }
            $link = html_writer::link($url, $bloginfo->name);
            $bloglink = html_writer::tag('li', $img . ' ' . $link . ' ' .
                    get_string('import_step0_numposts', 'oublog', $bloginfo->numposts));
        } else {
            $bloglink = html_writer::tag('li', $img . ' ' . $bloginfo->name . ' ' .
                    get_string('import_step0_numposts', 'oublog', 0));
        }
        if ($bloginfo->coursename != '') {
            $blogout .= $bloglink;
        } else {
            $personalblogout .= $bloglink;
        }
    }
    if ($personalblogout != '') {
        echo html_writer::tag('ul', $personalblogout);
    }
    echo $blogout;
    if ($curcourse != -1) {
        echo html_writer::end_tag('ul');
    }
    if (empty($blogs)) {
        echo $OUTPUT->error_text(get_string('import_step0_nonefound', 'oublog'));
    }
} else if ($step == 1) {
    // Get available posts, first get selected blog info + check access.
    $bid = required_param('bid', PARAM_INT);
    $stepinfo = array('step' => 1, 'bid' => $bid);
    if ($remote = optional_param('remote', false, PARAM_BOOL)) {
        $stepinfo['remote'] = true;
        // Blog on remote server, use WS to get info.
        if (!$result = oublog_import_remote_call('mod_oublog_get_blog_info',
                array('username' => $USER->username, 'cmid' => $bid))) {
            throw new moodle_exception('invalidcoursemodule', 'error');
        }
        $boublogid = $result->boublogid;
        $bcontextid = $result->bcontextid;
        $boublogname = $result->boublogname;
        $bcoursename = $result->bcoursename;
    } else {
        list($bid, $boublogid, $bcontextid, $boublogname, $bcoursename) = oublog_import_getbloginfo($bid);
    }
    echo html_writer::start_tag('p', array('class' => 'oublog_import_step1_from'));
    echo get_string('import_step1_from', 'oublog') . '<br />' . html_writer::tag('span', $boublogname);
    echo html_writer::end_tag('p');
    // Setup table early so sort can be determined (needs setup to be called first).
    $table = new flexible_table($cm->id * $bid);
    $url = new moodle_url('/mod/oublog/import.php', $params + $stepinfo);
    $table->define_baseurl($url);
    $table->define_columns(array('title', 'timeposted', 'tags', 'include'));
    $table->column_style('include', 'text-align', 'center');
    $table->sortable(true, 'timeposted', SORT_DESC);
    $table->maxsortkeys = 1;
    $table->no_sorting('tags');
    $table->no_sorting('include');
    $table->setup();
    $sort = flexible_table::get_sort_for_table($cm->id * $bid);
    if (empty($sort)) {
        $sort = 'timeposted DESC';
    }
    if ($tags = optional_param('tags', null, PARAM_SEQUENCE)) {
        // Filter by joining tag instances.
        $stepinfo['tags'] = $tags;
    }
    $perpage = 100;// Must match value in oublog_import_getallposts.
    $page = optional_param('page', 0, PARAM_INT);
    $stepinfo['page'] = $page;
    $preselected = optional_param('preselected', '', PARAM_SEQUENCE);
    $stepinfo['preselected'] = $preselected;
    $preselected = array_filter(array_unique(explode(',', $preselected)));
    if ($remote) {
        $result = oublog_import_remote_call('mod_oublog_get_blog_allposts', array(
                'blogid' => $boublogid, 'username' => $USER->username, 'sort' => $sort,
                'page' => $page, 'tags' => $tags));
        $posts = $result->posts;
        $total = $result->total;
        $tagnames = $result->tagnames;
        // Fix up post tags to required format as passed differently from WS.
        foreach ($posts as &$post) {
            if (isset($post->outags)) {
                $newtagarr = array();
                foreach ($post->outags as $tag) {
                    $newtagarr[$tag->id] = $tag->tag;
                }
                $post->outags = $newtagarr;
            }
        }
    } else {
        list($posts, $total, $tagnames) = oublog_import_getallposts($boublogid, $sort, $USER->id,
                $page, $tags);
    }
    if ($posts) {
        // Finish seting up table vars.
        $url = new moodle_url('/mod/oublog/import.php', $params + $stepinfo);
        $table->define_baseurl($url);
        $perpage = $total < $perpage ? $total : $perpage;
        $table->pagesize($perpage, $total);
        $taghead = get_string('import_step1_table_tags', 'oublog');
        if (!empty($tagnames)) {
            // Add tag filter removal links.
            $taghead .= '<br />';
            foreach ($tagnames as $tagid => $tag) {
                $tagaarcopy = explode(',', $tags);
                unset($tagaarcopy[array_search($tagid, $tagaarcopy)]);
                $turl = new moodle_url('/mod/oublog/import.php',
                        array_merge($params, $stepinfo, array('tags' => implode(',', $tagaarcopy))));
                $taghead .= ' ' . html_writer::link($turl, $OUTPUT->pix_icon('t/delete',
                        get_string('import_step1_removetag', 'oublog', $tag->tag))) .
                        ' ' . format_text($tag->tag, FORMAT_HTML);
            }
        }
        $table->define_headers(array(get_string('import_step1_table_title', 'oublog'),
                get_string('import_step1_table_posted', 'oublog'),
                $taghead,
                get_string('import_step1_table_include', 'oublog')));
        echo html_writer::start_tag('form', array('method' => 'post', 'action' => qualified_me()));
        $untitledcount = 1;
        foreach ($posts as &$post) {
            $tagcol = '';
            if (isset($post->outags)) {
                // Create tag column for post.
                foreach ($post->outags as $tagid => $tag) {
                    $newtagval = empty($tags) ? $tagid : $tags . ",$tagid";
                    $turl = new moodle_url('/mod/oublog/import.php',
                            array_merge($params, $stepinfo, array('tags' => $newtagval)));
                    $tagcol .= html_writer::link($turl, format_text($tag, FORMAT_HTML),
                            array('class' => 'oublog_import_tag'));
                }
            }
            if (empty($post->title)) {
                $post->title = get_string('untitledpost', 'oublog') . ' ' . $untitledcount;
                $untitledcount++;
            }
            $importcol = html_writer::checkbox('post_' . $post->id, $post->id, in_array($post->id, $preselected),
                    get_string('import_step1_include_label', 'oublog', format_string($post->title)));
            $table->add_data(array(
                    format_string($post->title),
                    oublog_date($post->timeposted),
                    $tagcol, $importcol));
        }
        $module = array ('name' => 'mod_oublog');
        $module['fullpath'] = '/mod/oublog/module.js';
        $module['requires'] = array('node', 'node-event-delegate', 'querystring');
        $PAGE->requires->strings_for_js(array('import_step1_all', 'import_step1_none'), 'oublog');
        $PAGE->requires->js_init_call('M.mod_oublog.init_posttable', null, false, $module);
        $table->finish_output();
        echo html_writer::start_div();
        foreach (array_merge($params, $stepinfo, array('step' => 2, 'sesskey' => sesskey())) as $param => $value) {
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $param, 'value' => $value));
        }
        echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit',
                'value' => get_string('import_step1_submit', 'oublog')));
        echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'cancel',
                'value' => get_string('cancel')));
        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }
} else if ($step == 2) {
    // Do the import, show feedback. First check access.
    echo html_writer::tag('p', get_string('import_step2_inst', 'oublog'));
    flush();
    $bid = required_param('bid', PARAM_INT);
    if ($remote = optional_param('remote', false, PARAM_BOOL)) {
        // Blog on remote server, use WS to get info.
        if (!$result = oublog_import_remote_call('mod_oublog_get_blog_info',
                array('username' => $USER->username, 'cmid' => $bid))) {
            throw new moodle_exception('invalidcoursemodule', 'error');
        }
        $boublogid = $result->boublogid;
        $bcontextid = $result->bcontextid;
        $boublogname = $result->boublogname;
        $bcoursename = $result->bcoursename;
    } else {
        list($bid, $boublogid, $bcontextid, $boublogname, $bcoursename) = oublog_import_getbloginfo($bid);
    }
    require_sesskey();
    // Get selected and pre-selected posts.
    $preselected = explode(',', optional_param('preselected', '', PARAM_SEQUENCE));
    $selected = array();
    foreach ($_POST as $name => $val) {
        if (strpos($name, 'post_') === 0) {
            $selected[] = $val;
        }
    }
    $selected = array_filter(array_unique(array_merge($selected, $preselected), SORT_NUMERIC));
    $stepinfo = array('step' => 2, 'bid' => $bid, 'preselected' => implode(',', $selected), 'remote' => $remote);
    if (empty($selected)) {
        echo html_writer::tag('p', get_string('import_step2_none', 'oublog'));
        echo $OUTPUT->continue_button(new moodle_url('/mod/oublog/import.php',
                array_merge($params, $stepinfo, array('step' => 1))));
        echo $OUTPUT->footer();
        exit;
    }

    if ($remote) {
        $posts = oublog_import_remote_call('mod_oublog_get_blog_posts',
                array('username' => $USER->username, 'blogid' => $boublogid, 'selected' => implode(',', $selected),
                        'inccomments' => $oublog->allowcomments != OUBLOG_COMMENTS_PREVENT, 'bcontextid' => $bcontextid));
    } else {
        $posts = oublog_import_getposts($boublogid, $bcontextid, $selected,
                $oublog->allowcomments != OUBLOG_COMMENTS_PREVENT, $USER->id);
    }

    if (empty($posts)) {
        print_error('import_step2_none', 'oublog');
    }

    // Get/create user blog instance for this activity.
    if ($oublog->global) {
        list($notused, $oubloginstance) = oublog_get_personal_blog($USER->id);
    } else {
        if (!$oubloginstance = $DB->get_record('oublog_instances', array('oublogid' => $oublog->id, 'userid' => $USER->id))) {
            if (!$oubloginstance = oublog_add_bloginstance($oublog->id, $USER->id)) {
                print_error('Failed to create blog instance');
            }
            $oubloginstance = (object) array('id' => $oubloginstance);
        }
    }
    // Copy all posts (updating group), checking for conflicts first.
    $bar = new progress_bar('oublog_import_step2_prog', 500, true);
    $conflicts = array();
    $ignoreconflicts = optional_param('ignoreconflicts', false, PARAM_BOOL);
    $cur = 0;
    $files = get_file_storage();
    foreach ($posts as $post) {
        $cur++;
        // Is there a conflict, if so add to our list so we can re-do these later.
        if (!$ignoreconflicts && $DB->get_records('oublog_posts', array('title' => $post->title,
                'timeposted' => $post->timeposted, 'oubloginstancesid' => $oubloginstance->id))) {
                $conflicts[] = $post->id;
                $bar->update($cur, count($posts), get_string('import_step2_prog', 'oublog'));
                continue;
        }
        $trans = $DB->start_delegated_transaction();
        $newpost = new stdClass();
        $newpost->oubloginstancesid = $oubloginstance->id;
        $newpost->groupid = 0;// Force 0 as individual mode.
        $newpost->title = $post->title;
        $newpost->message = $post->message;
        $newpost->timeposted = $post->timeposted;
        $newpost->allowcomments = $post->allowcomments;
        $newpost->timeupdated = time();
        $newpost->visibility = $post->visibility;
        if ($oublog->maxvisibility < $newpost->visibility) {
            $newpost->visibility = $oublog->maxvisibility;
        }
        if ($oublog->allowcomments == OUBLOG_COMMENTS_PREVENT) {
            $newpost->allowcomments = OUBLOG_COMMENTS_PREVENT;
        }
        $newid = $DB->insert_record('oublog_posts', $newpost);
        // Add tags copied from original + new short code tag.
        if ($bcoursename) {
            $tagname = core_text::strtolower($bcoursename);
            if (!$bctag = $DB->get_field('oublog_tags', 'id',
                    array('tag' => $tagname))) {
                $bctag = $DB->insert_record('oublog_tags',
                        (object) array('tag' => $tagname));
            }
            if (!isset($post->outags)) {
                $post->outags = array((object) array('id' => $bctag, 'tag' => $tagname));
            } else {
                $post->outags[] = (object) array('id' => $bctag, 'tag' => $tagname);
            }
        }
        if (isset($post->outags)) {
            foreach ($post->outags as $tagval) {
                if (!$remote || ($bcoursename && $tagval == $tagname)) {
                    $DB->insert_record('oublog_taginstances', (object) array(
                            'oubloginstancesid' => $oubloginstance->id, 'postid' => $newid, 'tagid' => $tagval->id));
                } else {
                    // Find/create tag.
                    if (!$tagid = $DB->get_field('oublog_tags', 'id', array('tag' => $tagval->tag))) {
                        $tagid = $DB->insert_record('oublog_tags', (object) array('tag' => $tagval->tag));
                    }
                    $DB->insert_record('oublog_taginstances', (object) array(
                            'oubloginstancesid' => $oubloginstance->id, 'postid' => $newid, 'tagid' => $tagid));
                }
            }
        }
        // Copy across images/attachments (no maximum check).
        if ($remote) {
            // Download remote files and add to new post.
            oublog_import_remotefiles($post->images, $context->id, $newid);
            oublog_import_remotefiles($post->attachments, $context->id, $newid);
        } else {
            foreach ($post->images as $image) {
                $files->create_file_from_storedfile(array('itemid' => $newid, 'contextid' => $context->id), $image);
            }
            foreach ($post->attachments as $attach) {
                $files->create_file_from_storedfile(array('itemid' => $newid, 'contextid' => $context->id), $attach);
            }
        }
        // Copy own comments (if enabled on this blog).
        if (!empty($post->comments)) {
            foreach ($post->comments as $comment) {
                $oldcid = $comment->id;
                unset($comment->id);
                $comment->postid = $newid;
                $comment->userid = $USER->id;
                $newcid = $DB->insert_record('oublog_comments', $comment);
                // Copy comment images.
                if (!$remote) {
                    foreach ($comment->images as $image) {
                        $files->create_file_from_storedfile(array('itemid' => $newcid,
                                'contextid' => $context->id), $image);
                    }
                } else {
                    oublog_import_remotefiles($comment->images, $context->id, $newcid);
                }
            }
            // Inform completion system, if available.
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) && ($oublog->completioncomments)) {
                $completion->update_state($cm, COMPLETION_COMPLETE);
            }
        }
        // Update search (add required properties to newpost).
        $newpost->id = $newid;
        $newpost->userid = $USER->id;
        $newpost->outags = array();
        if (isset($post->outags)) {
            foreach ($post->outags as $tag) {
                $newpost->outags[$tag->id] = $tag->tag;
            }
        }
        oublog_search_update($newpost, $cm);
        $trans->allow_commit();
        $bar->update($cur, count($posts), get_string('import_step2_prog', 'oublog'));
    }
    if (count($conflicts) != count($posts)) {
        // Inform completion system, if available.
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && ($oublog->completionposts)) {
            $completion->update_state($cm, COMPLETION_COMPLETE);
        }
    }

    // Log post imported event.
    $params = array(
        'context' => $context,
        'objectid' => $oublog->id,
        'other' => array(
            'info' => count($posts),
        )
    );
    $event = \mod_oublog\event\post_imported::create($params);
    $event->trigger();

    echo html_writer::tag('p', get_string('import_step2_total', 'oublog',
            (count($posts) - count($conflicts))));
    $continueurl = '/mod/oublog/view.php?id=' . $cm->id;
    if ($oublog->global) {
        $continueurl = '/mod/oublog/view.php?user=' . $USER->id;
    }
    if (count($conflicts)) {
        // Enable conflicts to be ignored by resending only these.
        $stepinfo['ignoreconflicts'] = true;
        $stepinfo['preselected'] = implode(',', $conflicts);
        $url = new moodle_url('/mod/oublog/import.php', array_merge($params, $stepinfo));
        $conflictimport = new single_button($url, get_string('import_step2_conflicts_submit', 'oublog'));
        echo $OUTPUT->confirm(get_string('import_step2_conflicts', 'oublog', count($conflicts)),
                $conflictimport, $continueurl);
    } else {
        echo $OUTPUT->continue_button($continueurl);
    }
}

echo html_writer::end_div();
echo $OUTPUT->footer();
