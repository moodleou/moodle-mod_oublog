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
 * Moodle renderer used to display special elements of the lesson module
 *
 * @package    mod
 * @subpackage oublog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

class mod_oublog_renderer extends plugin_renderer_base {
    /**
     * Print a single blog post
     *
     * @param object $oublog Blog object
     * @param object $post
     * @param string $baseurl
     * @param string $blogtype
     * @param bool $canmanageposts
     * @param bool $canaudit
     * @param bool $cancomment
     * @return bool
     */
    public function oublog_print_post($cm, $oublog, $post, $baseurl, $blogtype, $canmanageposts = false, $canaudit = false, $commentcount = true) {
        global $CFG, $USER;
        $output = '';
        $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
        // Get rid of any existing tag from the URL as we only support one at a time
        $baseurl = preg_replace('~&amp;tag=[^&]*~', '', $baseurl);

        $strcomment = get_string('comment', 'oublog');
        $strtags = get_string('tags', 'oublog');
        $stredit = get_string('edit', 'oublog');
        $strdelete = get_string('delete', 'oublog');

        $extraclasses = $post->deletedby ? ' oublog-deleted' : '';
        $extraclasses .= ' oublog-hasuserpic';

        $output .= html_writer::start_tag('div', array('class'=>'oublog-post'. $extraclasses));

        $output .= html_writer::start_tag('div', array('class'=>'oublog-userpic'));
        $postuser = new object();
        $postuser->id = $post->userid;
        $postuser->firstname = $post->firstname;
        $postuser->lastname = $post->lastname;
        $postuser->email = $post->email;
        $postuser->imagealt = $post->imagealt;
        $postuser->picture = $post->picture;
        $output .= $this->output->user_picture($postuser, array('courseid' => $oublog->course));
        $output .= html_writer::end_tag('div');

        $formattedtitle = format_string($post->title);
        if (trim($formattedtitle) !== '') {
            $output .= html_writer::tag('h2', format_string($post->title), array('class'=>'oublog-title'));
        }

        if ($post->deletedby) {
            $deluser = new stdClass();
            $deluser->firstname = $post->delfirstname;
            $deluser->lastname = $post->dellastname;

            $a = new stdClass();

            $a->fullname = html_writer::tag('a', fullname($deluser), array('href'=>$CFG->wwwroot.'/user/view.php?id=' . $post->deletedby));
            $a->timedeleted = oublog_date($post->timedeleted);

            $output .= html_writer::tag('div', get_string('deletedby', 'oublog', $a), array('class'=>'oublog-post-deletedby'));
        }

        $output .= html_writer::start_tag('div', array('class'=>'oublog-post-date'));
        $output .= oublog_date($post->timeposted);
        $output .= ' ';
        if ($blogtype == 'course' || strpos($_SERVER['REQUEST_URI'], 'allposts.php') != 0) {
            $output .= html_writer::start_tag('div', array('class'=>'oublog-postedby'));
            $output .= get_string('postedby', 'oublog', '<a href="'.$CFG->wwwroot.'/user/view.php?id=' . $post->userid . '&amp;course=' . $oublog->course . '">' . fullname($post) . '</a>');
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('div');

        if (isset($post->edits) && ($canaudit || $post->userid == $USER->id)) {
            $output .= html_writer::start_tag('div', array('class'=>'oublog-post-editsummary'));
            foreach ($post->edits as $edit) {
                $a = new stdClass();
                $a->editby = fullname($edit);
                $a->editdate = oublog_date($edit->timeupdated);
                if ($edit->userid == $post->userid) {
                    $output .= '- '.html_writer::tag('a', get_string('editsummary', 'oublog', $a), array('href'=>$CFG->wwwroot.'/mod/oublog/viewedit.php?edit=' . $edit->id));
                } else {
                    $output .= '- '.html_writer::tag('a', get_string('editonsummary', 'oublog', $a), array('href'=>$CFG->wwwroot.'/mod/oublog/viewedit.php?edit=' . $edit->id));

                }
                $output .= html_writer::empty_tag('br', array());
            }
            $output .= html_writer::end_tag('div');
        } else if ($post->lasteditedby) {
            $edit = new StdClass;
            $edit->firstname = $post->edfirstname;
            $edit->lastname = $post->edlastname;

            $a = new stdClass();
            $a->editby = fullname($edit);
            $a->editdate = oublog_date($post->timeupdated);
            $output .= html_writer::tag('div', get_string('editsummary', 'oublog', $a), array('class'=>'oublog-post-editsummary'));
        }

        if (!$oublog->individual) {
            $output .= html_writer::start_tag('div', array('class'=>'oublog-post-visibility'));
            $output .= oublog_get_visibility_string($post->visibility, $blogtype == 'personal');
            $output .= html_writer::end_tag('div');
        }

        $output .= html_writer::start_tag('div', array('class'=>'oublog-post-content'));
        $post->message = file_rewrite_pluginfile_urls($post->message, 'pluginfile.php', $modcontext->id, 'mod_oublog', 'message', $post->id);
        $output .= format_text($post->message, FORMAT_HTML);
        $output .= html_writer::end_tag('div');;

        $output .= html_writer::start_tag('div', array('class'=>'oublog-post-attachments'));
        $fs = get_file_storage();
        if ($files = $fs->get_area_files($modcontext->id, 'mod_oublog', 'attachment', $post->id, "timemodified", false)) {
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $mimetype = $file->get_mimetype();
                $iconimage = html_writer::empty_tag('img', array('src'=>$this->output->pix_url(file_mimetype_icon($mimetype)), 'alt'=>$mimetype, 'class'=>'icon'));
                $path = file_encode_url($CFG->wwwroot . '/pluginfile.php', '/' . $modcontext->id . '/mod_oublog/attachment/' . $post->id . '/' . $filename);
                $output .= html_writer::tag('a', $iconimage, array('href'=>$path));
                $output .= html_writer::tag('a', s($filename), array('href'=>$path));
            }
        }
        $output .= html_writer::end_tag('div');;

        if (isset($post->tags)) {
            $output .= html_writer::start_tag('div', array('class'=>'oublog-post-tags')) . $strtags . ': ';
            foreach ($post->tags as $taglink) {
                $output .= html_writer::tag('a', $taglink, array('href'=>$baseurl . '&tag=' . urlencode($taglink))).' ';
            }
            $output .= html_writer::end_tag('div');;
        }

        $output .= html_writer::start_tag('div', array('class'=>'oublog-post-links'));
        if (!$post->deletedby) {
            if (($post->userid == $USER->id || $canmanageposts)) {
                $output .= html_writer::tag('a', $stredit, array('href'=>$CFG->wwwroot . '/mod/oublog/editpost.php?blog=' . $post->oublogid . '&post=' . $post->id)).' ';
                $output .= html_writer::tag('a', $strdelete, array('href'=>$CFG->wwwroot . '/mod/oublog/deletepost.php?blog=' . $post->oublogid . '&post=' . $post->id)).' ';
            }
            //show portfolio export link
            if (!empty($CFG->enableportfolios) &&
                    (has_capability('mod/oublog:exportpost', $modcontext) ||
                    ($post->userid == $USER->id &&
                        has_capability('mod/oublog:exportownpost', $modcontext)))) {
                require_once($CFG->libdir . '/portfoliolib.php');
                $button = new portfolio_add_button();
                $button->set_callback_options('oublog_portfolio_caller', array('postid' => $post->id), '/mod/oublog/locallib.php');
                if (empty($files)) {
                    $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
                } else {
                    $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
                }
                $output .= $button->to_html(PORTFOLIO_ADD_TEXT_LINK).' ';
            }

            //show comments
            if ($post->allowcomments) {
                // If this is the current user's post, show pending comments too
                $showpendingcomments =
                        $post->userid == $USER->id && !empty($post->pendingcomments);
                if ((isset($post->comments) || $showpendingcomments) && $commentcount) { #
                    // Show number of comments
                    if (isset($post->comments)) {
                        $linktext = get_string(
                            count($post->comments) == 1 ? 'onecomment' : 'ncomments',
                            'oublog', count($post->comments));
                    }
                    // Show number of pending comments
                    if (isset($post->pendingcomments)) {
                        // Use different string if we already have normal comments too
                        if (isset($post->comments)) {
                            $linktext .= get_string(
                                $post->pendingcomments == 1 ? 'onependingafter' : 'npendingafter',
                                'oublog', $post->pendingcomments);
                        } else {
                            $linktext = get_string(
                                $post->pendingcomments == 1 ? 'onepending' : 'npending',
                                'oublog', $post->pendingcomments);
                        }
                    }

                    // Display link
                    $output .= html_writer::tag('a', $linktext, array('href'=>$CFG->wwwroot . '/mod/oublog/viewpost.php?post=' .$post->id));

                    // Display information about most recent comment
                    if (isset($post->comments)) {
                        $last = array_pop($post->comments);
                        array_push($post->comments, $last);
                        $a = new stdClass();
                        if ($last->userid) {
                            $a->fullname = fullname($last);
                        } else {
                            $a->fullname = s($last->authorname);
                        }
                        $a->timeposted = oublog_date($last->timeposted, true);
                        $output .= ' ' . get_string('lastcomment', 'oublog', $a);
                    }
                } elseif (oublog_can_comment($cm, $oublog, $post)) {
                    $output .= html_writer::tag('a', $strcomment, array('href'=>$CFG->wwwroot.'/mod/oublog/editcomment.php?blog='.$post->oublogid.'&post='.$post->id));
                }
            }
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Prints the summary block. This includes the blog summary
     * and possibly links to change it, depending on the type of blog and user
     * permissions.
     * @param object $oublog Blog object
     * @param object $oubloginstance Blog instance object
     * @param bool $canmanageposts True if they're allowed to edit the blog
     */
    public function oublog_print_summary_block($oublog, $oubloginstance, $canmanageposts) {
        global $USER, $CFG;
        $links = '';
        if ($oublog->global) {
            $title = $oubloginstance->name;
            $summary = $oubloginstance->summary;
            if (($oubloginstance->userid == $USER->id) || $canmanageposts ) {
                $links .= html_writer::empty_tag('br', array());
                $links .= html_writer::tag('a', get_string('blogoptions','oublog'), array('href'=>$CFG->wwwroot.'/mod/oublog/editinstance.php?instance='.$oubloginstance->id, 'class'=>'oublog-links'));
            }
            $links .= html_writer::empty_tag('br', array());
            $links .= html_writer::tag('a', get_string('siteentries','oublog'), array('href'=>$CFG->wwwroot.'/mod/oublog/allposts.php', 'class'=>'oublog-links'));
        } else {
            $summary = $oublog->summary;
            $title = $oublog->name;
        }

        $bc = new block_contents();
        $bc->id = 'oublog-summary';
        $bc->content = format_text($summary, FORMAT_HTML) . $links;
        $bc->footer = '';
        $bc->title = format_string($title);
        return $this->output->block($bc, BLOCK_POS_LEFT);
    }
}
