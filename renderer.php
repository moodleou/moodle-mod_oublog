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
     * @param object $post Structure containing all post info and comments
     * @param string $baseurl Base URL of current page
     * @param string $blogtype Blog level ie course or above
     * @param bool $canmanageposts Has capability toggle
     * @param bool $canaudit Has capability toggle
     * @param bool $cancomment Has capability toggle
     * @param bool $forexport Export output rendering toggle
     * @param bool $email Email output rendering toggle
     * @return bool
     */
    public function render_post($cm, $oublog, $post, $baseurl, $blogtype,
            $canmanageposts = false, $canaudit = false, $commentcount = true,
            $forexport = false, $format = false, $email = false) {
        global $CFG, $USER;
        $output = '';
        $modcontext = context_module::instance($cm->id);
        // Get rid of any existing tag from the URL as we only support one at a time.
        $baseurl = preg_replace('~&amp;tag=[^&]*~', '', $baseurl);

        $strcomment = get_string('comment', 'oublog');
        $strtags = get_string('tags', 'oublog');
        $stredit = get_string('edit', 'oublog');
        $strdelete = get_string('delete', 'oublog');
        $strpermalink = get_string('permalink', 'oublog');

        $row = '';
        if (isset($post->row)) {
            $row = ($post->row % 2) ? 'oublog-odd' : 'oublog-even';
        }

        $extraclasses = $post->deletedby ? ' oublog-deleted' : '';
        $extraclasses .= ' oublog-hasuserpic';
        $extraclasses .= ' ' . $row;

        $output .= html_writer::start_tag('div', array('class' => 'oublog-post'. $extraclasses));
        $output .= html_writer::start_tag('div', array('class' => 'oublog-post-top'));
        $fs = get_file_storage();
        if ($files = $fs->get_area_files($modcontext->id, 'mod_oublog', 'attachment', $post->id,
                "timemodified", false)) {
            $output .= html_writer::start_tag('div', array('class'=>'oublog-post-attachments'));
            $output .= get_string('attachments', 'mod_oublog') . ': ';
            foreach ($files as $file) {
                if (!$forexport && !$email) {
                    $filename = $file->get_filename();
                    $mimetype = $file->get_mimetype();
                    $iconimage = html_writer::empty_tag('img',
                            array('src' => $this->output->pix_url(file_mimetype_icon($mimetype)),
                            'alt' => $mimetype, 'class' => 'icon'));
                    if ($post->visibility == OUBLOG_VISIBILITY_PUBLIC) {
                        $fileurlbase = '/mod/oublog/pluginfile.php';
                    } else {
                        $fileurlbase = '/pluginfile.php';
                    }
                    $filepath = '/' . $modcontext->id . '/mod_oublog/attachment/'
                            . $post->id . '/' . $filename;
                    $path = moodle_url::make_file_url($fileurlbase, $filepath, true);
                    $output .= html_writer::start_tag('div', array('class'=>'oublog-post-attachment'));
                    $output .= html_writer::tag('a', $iconimage, array('href' => $path));
                    $output .= html_writer::tag('a', s($filename), array('href' => $path));
                    $output .= html_writer::end_tag('div');
                } else {
                    $filename = $file->get_filename();
                    if (is_object($format)) {
                        $output .= $format->file_output($file) . ' ';
                    } else {
                        $output .= $filename . ' ';
                    }
                }
            }
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::start_tag('div', array('class' => 'oublog-post-top-content'));
        if (!$forexport) {
            $output .= html_writer::start_tag('div', array('class' => 'oublog-userpic'));
            $postuser = new object();
            $postuser->id = $post->userid;
            $postuser->firstname = $post->firstname;
            $postuser->lastname = $post->lastname;
            $postuser->email = $post->email;
            $postuser->imagealt = $post->imagealt;
            $postuser->picture = $post->picture;
            $postuser->firstnamephonetic = $post->firstnamephonetic;
            $postuser->lastnamephonetic = $post->lastnamephonetic;
            $postuser->middlename = $post->middlename;
            $postuser->alternatename = $post->alternatename;
            $output .= $this->output->user_picture($postuser,
                    array('courseid' => $oublog->course, 'size' => 70));
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::start_tag('div', array('class' => 'oublog-post-top-details'));
        $formattedtitle = format_string($post->title);
        if (trim($formattedtitle) !== '') {
            $output .= html_writer::tag('h2',
                    format_string($post->title), array('class' => 'oublog-title'));
        } else if (!$forexport) {
            $posttitle = get_accesshide(get_string('newpost', 'mod_oublog',
                    oublog_get_displayname($oublog)));
            $output .= html_writer::tag('h2', $posttitle, array('class' => 'oublog-title'));
        }

        if ($post->deletedby) {
            $deluser = new stdClass();
        // Get user name fields.
            $delusernamefields = get_all_user_name_fields(false, null, 'del');
            foreach ($delusernamefields as $namefield => $retnamefield) {
                $deluser->$namefield = $post->$retnamefield;
            }

            $a = new stdClass();

            $a->fullname = html_writer::tag('a', fullname($deluser),
                    array('href' => $CFG->wwwroot . '/user/view.php?id=' . $post->deletedby));
            $a->timedeleted = oublog_date($post->timedeleted);
            $output .= html_writer::tag('div', get_string('deletedby', 'oublog', $a),
                    array('class' => 'oublog-post-deletedby'));
        }

        $output .= html_writer::start_tag('div', array('class' => 'oublog-post-date'));
        $output .= oublog_date($post->timeposted);
        $output .= html_writer::empty_tag('br', array());
        $output .= ' ';
        if ($blogtype == 'course' || strpos($_SERVER['REQUEST_URI'], 'allposts.php') != 0) {
            $output .= html_writer::start_tag('div', array('class' => 'oublog-postedby'));
            if (!$forexport) {
                $output .= get_string('postedby', 'oublog', '<a href="' .
                        $CFG->wwwroot.'/user/view.php?id=' . $post->userid . '&amp;course=' .
                        $oublog->course . '">' . fullname($post) . '</a>');
            } else {
                $output .= get_string('postedby', 'oublog',  fullname($post));
            }
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('div');

        if (!$oublog->individual) {
            $output .= html_writer::start_tag('div', array('class' => 'oublog-post-visibility'));
            $output .= oublog_get_visibility_string($post->visibility, $blogtype == 'personal');
            $output .= html_writer::end_tag('div');
        }

        if (isset($post->edits) && ($canaudit || $post->userid == $USER->id)) {
            $output .= html_writer::start_tag('div', array('class' => 'oublog-post-editsummary'));
            foreach ($post->edits as $edit) {
                $a = new stdClass();
                $a->editby = fullname($edit);
                $a->editdate = oublog_date($edit->timeupdated);
                if (!$forexport && !$email) {
                    if ($edit->userid == $post->userid) {
                        $output .= '- '.html_writer::tag('a', get_string('editsummary',
                                'oublog', $a), array('href' =>
                                $CFG->wwwroot . '/mod/oublog/viewedit.php?edit=' . $edit->id));
                    } else {
                        $output .= '- '.html_writer::tag('a', get_string('editonsummary',
                                'oublog', $a), array('href' =>
                                $CFG->wwwroot . '/mod/oublog/viewedit.php?edit=' . $edit->id));
                    }
                } else {
                    if ($edit->userid == $post->userid) {
                        $output .= '- '.  get_string('editsummary', 'oublog', $a);
                    } else {
                        $output .= '- '. get_string('editonsummary', 'oublog', $a);
                    }
                }
                $output .= html_writer::empty_tag('br', array());
            }
            $output .= html_writer::end_tag('div');
        } else if ($post->lasteditedby) {
            $edit = new StdClass;
            // Get user name fields.
            $editusernamefields = get_all_user_name_fields(false, null, 'ed');
            foreach ($editusernamefields as $namefield => $retnamefield) {
                $edit->$namefield = $post->$retnamefield;
            }

            $a = new stdClass();
            $a->editby = fullname($edit);
            $a->editdate = oublog_date($post->timeupdated);
            $output .= html_writer::tag('div', get_string('editsummary', 'oublog', $a),
                    array('class' => 'oublog-post-editsummary'));
        }

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'oublog-post-content'));
        if (!$forexport) {
            if ($post->visibility == OUBLOG_VISIBILITY_PUBLIC || $email) {
                $fileurlbase = 'mod/oublog/pluginfile.php';
            } else {
                $fileurlbase = 'pluginfile.php';
            }
            $post->message = file_rewrite_pluginfile_urls($post->message, $fileurlbase,
                    $modcontext->id, 'mod_oublog', 'message', $post->id);
        } else {
            require_once($CFG->libdir . '/portfoliolib.php');
            $post->message = portfolio_rewrite_pluginfile_urls($post->message, $modcontext->id,
                    'mod_oublog', 'message', $post->id, $format);
        }
        $posttextoptions = new stdClass();
        if (trusttext_active() && has_capability('moodle/site:trustcontent', $modcontext,
                $post->userid)) {
            // Support trusted text when initial author is safe (post editors are not checked!).
            $posttextoptions->trusted = true;
            $posttextoptions->context = $modcontext;
        }
        $output .= format_text($post->message, FORMAT_HTML, $posttextoptions);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'oublog-post-bottom'));

        if (isset($post->tags)) {
            $output .= html_writer::start_tag('div', array('class' => 'oublog-post-tags')) .
                    $strtags . ': ';
            $tagcounter = 1;
            foreach ($post->tags as $taglink) {
                $taglinktext = $taglink;
                if ($tagcounter < count($post->tags)) {
                    $taglinktext .= ',';
                }
                if (!$forexport && !$email) {
                    $output .= html_writer::tag('a', $taglinktext, array('href' => $baseurl .
                            '&tag=' . urlencode($taglink))) . ' ';
                } else {
                    $output .= $taglinktext . ' ';
                }
                $tagcounter++;
            }
            $output .= html_writer::end_tag('div');
        }

        $output .= html_writer::start_tag('div', array('class' => 'oublog-post-links'));
        if (!$forexport && !$email) {
            $output .= html_writer::tag('a', $strpermalink, array('href' => $CFG->wwwroot .
                    '/mod/oublog/viewpost.php?post=' . $post->id)).' ';
        }

        if (!$post->deletedby) {
            if (($post->userid == $USER->id || $canmanageposts)) {
                if (!$forexport && !$email) {
                    $output .= html_writer::tag('a', $stredit, array('href' => $CFG->wwwroot .
                            '/mod/oublog/editpost.php?blog=' . $post->oublogid .
                            '&post=' . $post->id)).' ';
                    if (($post->userid !== $USER->id)) {
                        // Add email and 'oublog_deleteandemail' to delete link.
                        $output .= html_writer::tag('a', $strdelete, array('href' => $CFG->wwwroot .
                                '/mod/oublog/deletepost.php?blog=' . $post->oublogid .
                                '&post=' . $post->id . '&delete=1',
                                'class' => 'oublog_deleteandemail_' . $post->id));
                        self::render_oublog_print_delete_dialog($cm->id, $post->id);
                    } else {
                        $output .= html_writer::tag('a', $strdelete, array('href' => $CFG->wwwroot .
                                '/mod/oublog/deletepost.php?blog=' . $post->oublogid .
                                '&post=' . $post->id . '&delete=1'));
                    }
                }
            }
            // Show portfolio export link.
            if (!empty($CFG->enableportfolios) &&
                    (has_capability('mod/oublog:exportpost', $modcontext) ||
                    ($post->userid == $USER->id &&
                    has_capability('mod/oublog:exportownpost', $modcontext)))) {
                if (!$forexport && !$email) {
                    require_once($CFG->libdir . '/portfoliolib.php');
                    $button = new portfolio_add_button();
                    $button->set_callback_options('oublog_portfolio_caller',
                            array('postid' => $post->id), 'mod_oublog');
                    if (empty($files)) {
                        $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
                    } else {
                        $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
                    }
                    $output .= $button->to_html(PORTFOLIO_ADD_TEXT_LINK).' ';
                }
            }
            // Show OU Alerts reporting link.
            if (isloggedin() && oublog_oualerts_enabled()
                    && oublog_get_reportingemail($oublog) && !($post->userid == $USER->id)
                    && !$post->deletedby) {
                $itemnurl = new moodle_url('/mod/oublog/viewpost.php', array('post' => $post->id));
                $reportlink = oualerts_generate_alert_form_url('oublog', $modcontext->id,
                        'post', $post->id, $itemnurl, $itemnurl, '', false, true);
                if ($reportlink != '' && !$forexport && !$email) {
                    $output .= html_writer::tag('a', get_string('postalert', 'oublog'),
                            array('href' => $reportlink));
                }
            }

            // Show comments.
            if ($post->allowcomments) {
                // If this is the current user's post, show pending comments too.
                $showpendingcomments = $post->userid == $USER->id && !empty($post->pendingcomments);
                if ((isset($post->comments) || $showpendingcomments) && $commentcount) {
                    // Show number of comments.
                    if (isset($post->comments)) {
                        $linktext = get_string(
                                count($post->comments) == 1 ? 'onecomment' : 'ncomments',
                                'oublog', count($post->comments));
                    }
                    // Show number of pending comments.
                    if (isset($post->pendingcomments)) {
                        // Use different string if we already have normal comments too.
                        if (isset($post->comments)) {
                            $linktext .= get_string(
                                    $post->pendingcomments == 1 ? 'onependingafter' :
                                    'npendingafter', 'oublog', $post->pendingcomments);
                        } else {
                            $linktext = get_string(
                                    $post->pendingcomments == 1 ? 'onepending' : 'npending',
                                    'oublog', $post->pendingcomments);
                        }
                    }
                    if (!$forexport) {
                        // Display link.
                        $output .= html_writer::tag('a', $linktext, array('href' =>
                                $CFG->wwwroot . '/mod/oublog/viewpost.php?post=' . $post->id . '#oublogcomments'));
                    } else {
                        $output .= $linktext;
                    }
                    // Display information about most recent comment.
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
                } else if (oublog_can_comment($cm, $oublog, $post)) {
                    if (!$forexport && !$email) {
                        $output .= html_writer::tag('a', $strcomment, array('href' =>
                                $CFG->wwwroot . '/mod/oublog/editcomment.php?blog=' . $post->oublogid .
                                '&post=' . $post->id));
                    }
                }
            }
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Print all user participation records for display
     *
     * @param object $cm current course module object
     * @param object $course current course object
     * @param object $oublog current oublog object
     * @param int $groupid optional group id, no group = 0
     * @param string $download download type (csv only, default '')
     * @param int $page flexible_table pagination page
     * @param array $participation mixed array of user participation values
     * @param object $context current context
     * @param bool $viewfullnames flag for global users fullnames capability
     * @param string groupname group name for display, default ''
     */
    public function render_participation_list($cm, $course, $oublog, $groupid,
        $download, $page, $participation, $context, $viewfullnames, $groupname) {
        global $DB, $CFG, $OUTPUT;

        require_once($CFG->dirroot.'/mod/oublog/participation_table.php');
        $perpage = OUBLOG_PARTICIPATION_PERPAGE;

        // Filename for downloading setup.
        $filename = "$course->shortname-".format_string($oublog->name, true);
        if (!empty($groupname)) {
            $filename .= '-'.format_string($groupname, true);
        }

        $hasgrades = !empty($participation) && isset(reset($participation)->gradeobj);
        $table = new oublog_participation_table($cm, $course, $oublog,
            $groupid, $groupname, $hasgrades);
        $table->setup($download);
        $table->is_downloading($download, $filename, get_string('participation', 'oublog'));

        if (!empty($participation)) {
            if (!$table->is_downloading()) {
                if ($perpage > count($participation)) {
                    $perpage = count($participation);
                }
                $table->pagesize($perpage, count($participation));
                $offset = $page * $perpage;
                $endposition = $offset + $perpage;
            } else {
                // Always export all users.
                $endposition = count($participation);
                $offset = 0;
            }
            $currentposition = 0;
            foreach ($participation as $user) {
                if ($currentposition == $offset && $offset < $endposition) {
                    $fullname = fullname($user, $viewfullnames);

                    // Control details link.
                    $details = false;

                    // Counts.
                    $posts = 0;
                    if (isset($user->posts)) {
                        $posts = $user->posts;
                        $details = true;
                    }
                    $comments = 0;
                    if (isset($user->comments)) {
                        $comments = $user->comments;
                        $details = true;
                    }

                    // User details.
                    if (!$table->is_downloading()) {
                        $picture = $OUTPUT->user_picture($user);
                        $userurl = new moodle_url('/user/view.php?',
                            array('id' => $user->id, 'course' => $course->id));
                        $userdetails = html_writer::link($userurl, $fullname);
                        if ($details) {
                            $detailparams = array('id' => $cm->id,
                                'user' => $user->id, 'group' => $groupid);
                            $detailurl = new moodle_url('/mod/oublog/userparticipation.php',
                                $detailparams);
                            $accesshidetext = get_string('foruser', 'oublog', $fullname);
                            $accesshide = html_writer::tag('span', $accesshidetext,
                                array('class' => 'accesshide'));
                            $detaillink = html_writer::start_tag('small');
                            $detaillink .= ' (';
                            $detaillink .= html_writer::link($detailurl,
                                get_string('details', 'oublog') . $accesshide);
                            $detaillink .= ')';
                            $detaillink .= html_writer::end_tag('small');
                            $userdetails .= $detaillink;
                        }
                    }

                    // Grades.
                    if ($oublog->grade != 0 && isset($user->gradeobj)) {
                        if (!$table->is_downloading()) {
                            $attributes = array('userid' => $user->id);
                            if (empty($user->gradeobj->grade)) {
                                $user->grade = -1;
                            } else {
                                $user->grade = abs($user->gradeobj->grade);
                            }
                            $menu = html_writer::select(make_grades_menu($oublog->grade),
                                'menu['.$user->id.']', $user->grade,
                                array(-1 => get_string('nograde')), $attributes);
                            $gradeitem = '<div id="gradeuser'.$user->id.'">'. $menu .'</div>';
                        } else {
                            if (!isset($user->gradeobj->grade)) {
                                $gradeitem = get_string('nograde');
                            } else {
                                $gradeitem = $user->gradeobj->grade;
                            }
                        }
                    }

                    // Add row.
                    if (!$table->is_downloading()) {
                        $row = array($picture, $userdetails, $posts, $comments);
                    } else {
                        $row = array($fullname, $posts, $comments);
                    }
                    if (isset($gradeitem)) {
                        $row[] = $gradeitem;
                    }
                    $table->add_data($row);
                    $offset++;
                }
                $currentposition++;
            }
        }
        $table->finish_output();
        if (!$table->is_downloading()) {
            // Print the grade form footer if necessary.
            if ($oublog->grade != 0 && !empty($participation)) {
                echo $table->grade_form_footer();
            }
        }
    }

    /**
     * Print single user participation for display
     *
     * @param object $cm current course module object
     * @param object $course current course object
     * @param object $oublog current oublog object
     * @param int $userid user id of user to view participation for
     * @param int $groupid optional group id, no group = 0
     * @param string $download download type (csv only, default '')
     * @param int $page flexible_table pagination page
     * @param array $participation mixed array of user participation values
     * @param object $context current context
     * @param bool $viewfullnames flag for global users fullnames capability
     * @param string groupname group name for display, default ''
     */
    public function render_user_participation_list($cm, $course, $oublog, $participation, $groupid,
        $download, $page, $context, $viewfullnames, $groupname, $start, $end) {
        global $DB, $CFG;

        $user = $participation->user;
        $fullname = fullname($user, $viewfullnames);

        // Setup the table.
        require_once($CFG->dirroot.'/mod/oublog/participation_table.php');
        $filename = "$course->shortname-".format_string($oublog->name, true);
        if ($groupname !== '') {
            $filename .= '-'.format_string($groupname, true);
        }
        $filename .= '-'.format_string($fullname, true);
        $table = new oublog_user_participation_table($cm->id, $course, $oublog,
            $user->id, $fullname, $groupname, $groupid, $start, $end);
        $table->setup($download);
        $table->is_downloading($download, $filename, get_string('participation', 'oublog'));

        // Print standard output.
        $output = '';
        $modcontext = context_module::instance($cm->id);
        if (!$table->is_downloading()) {
            $output .= html_writer::tag('h2', get_string('postsby', 'oublog', $fullname));
            if (!$participation->posts) {
                $output .= html_writer::tag('p', get_string('nouserposts', 'oublog'));
            } else {
                $counter = 0;
                foreach ($participation->posts as $post) {
                    $row = ($counter % 2) ? 'oublog-odd' : 'oublog-even';
                    $counter++;
                    $output .= html_writer::start_tag('div',
                        array('class' => 'oublog-post ' . $row));
                    $output .= html_writer::start_tag('div',
                            array('class' => 'oublog-post-top'));
                    // Post attachments.
                    $fs = get_file_storage();
                    if ($files = $fs->get_area_files($modcontext->id, 'mod_oublog', 'attachment',
                            $post->id, 'timemodified', false)) {
                        $output .= html_writer::start_tag('div',
                                array('class'=>'oublog-post-attachments'));
                        foreach ($files as $file) {
                            $filename = $file->get_filename();
                            $mimetype = $file->get_mimetype();
                            $iconimage = html_writer::empty_tag('img', array(
                                    'src' => $this->output->pix_url(file_mimetype_icon($mimetype)),
                                    'alt' => $mimetype, 'class' => 'icon'
                            ));
                            $fileurlbase = $CFG->wwwroot . '/pluginfile.php';
                            $filepath = '/' . $modcontext->id . '/mod_oublog/attachment/'
                            . $post->id . '/' . $filename;
                            $path = moodle_url::make_file_url($fileurlbase, $filepath);
                            $output .= html_writer::tag('a', $iconimage, array('href' => $path));
                            $output .= html_writer::tag('a', s($filename), array('href' => $path));
                        }
                        $output .= html_writer::end_tag('div');
                    }
                    // Post title and date.
                    if (isset($post->title) && !empty($post->title)) {
                        $viewposturl = new moodle_url('/mod/oublog/viewpost.php',
                            array('post' => $post->id));
                        $viewpost = html_writer::link($viewposturl, s($post->title));
                        $output .= html_writer::tag('h3', $viewpost,
                            array('class' => 'oublog-post-title'));
                        $output .= html_writer::start_tag('div',
                            array('class' => 'oublog-post-date'));
                        $output .= oublog_date($post->timeposted);
                        $output .= html_writer::end_tag('div');
                    } else {
                        $viewposturl = new moodle_url('/mod/oublog/viewpost.php',
                            array('post' => $post->id));
                        $viewpost = html_writer::link($viewposturl,
                            oublog_date($post->timeposted));
                        $output .= html_writer::tag('h3', $viewpost,
                            array('class' => 'oublog-post-title'));
                    }
                    $output .= html_writer::end_tag('div');
                    // Post content.
                    $output .= html_writer::start_tag('div',
                        array('class' => 'oublog-post-content'));
                    $post->message = file_rewrite_pluginfile_urls($post->message,
                        'pluginfile.php', $modcontext->id, 'mod_oublog',
                        'message', $post->id);
                    $output .= format_text($post->message, FORMAT_HTML);
                    $output .= html_writer::end_tag('div');

                    // End display box.
                    $output .= html_writer::end_tag('div');
                }
            }

            $output .= html_writer::tag('h2', get_string('commentsby', 'oublog', $fullname));
            if (!$participation->comments) {
                $output .= html_writer::tag('p', get_string('nousercomments', 'oublog'));
            } else {
                $output .= html_writer::start_tag('div',
                        array('id' => 'oublogcomments', 'class' => 'oublog-post-comments oublogpartcomments'));
                foreach ($participation->comments as $comment) {
                    $output .= html_writer::start_tag('div', array('class'=>'oublog-comment'));

                    $author = new StdClass;
                    $author->id = $comment->authorid;
                    $userfields = get_all_user_name_fields();
                    foreach ($userfields as $field) {
                        $author->$field = $comment->$field;
                    }
                    $authorurl = new moodle_url('/user/view.php', array('id' => $author->id));
                    $authorlink = html_writer::link($authorurl, fullname($author, $viewfullnames));
                    if (isset($comment->posttitle) && !empty($comment->posttitle)) {
                        $viewposturl = new moodle_url('/mod/oublog/viewpost.php',
                            array('post' => $comment->postid));
                        $viewpostlink = html_writer::link($viewposturl, s($comment->posttitle));
                        $strparams = array('title' => $viewpostlink, 'author' => $authorlink);
                        $output .= html_writer::tag('h3', get_string('commentonby', 'oublog',
                            $strparams));
                    } else {
                        $viewposturl = new moodle_url('/mod/oublog/viewpost.php',
                            array('post' => $comment->postid));
                        $viewpostlink = html_writer::link($viewposturl,
                            oublog_date($comment->postdate));
                        $strparams = array('title' => $viewpostlink, 'author' => $authorlink);
                        $output .= html_writer::tag('h3', get_string('commentonby', 'oublog',
                            $strparams));
                    }

                    // Comment title.
                    if (isset($comment->title) && !empty($comment->title)) {
                        $output .= html_writer::tag('h3', s($comment->title),
                            array('class' => 'oublog-comment-title'));
                    }

                    // Comment content and date.
                    $output .= html_writer::start_tag('div',
                        array('class' => 'oublog-comment-date'));
                    $output .= oublog_date($comment->timeposted);
                    $output .= html_writer::end_tag('div');
                    $output .= html_writer::start_tag('div',
                        array('class' => 'oublog-comment-content'));
                    $comment->message = file_rewrite_pluginfile_urls($comment->message,
                            'pluginfile.php', $modcontext->id, 'mod_oublog',
                            'messagecomment', $comment->id);
                    $output .= format_text($comment->message, FORMAT_HTML);
                    $output .= html_writer::end_tag('div');

                    // End display box.
                    $output .= html_writer::end_tag('div');
                }
                $output .= html_writer::end_tag('div');
            }
            // Only printing the download buttons.
            echo $table->download_buttons();

            // Print the actual output.
            echo $output;

            // Grade.
            if (isset($participation->gradeobj)) {
                $this->render_user_grade($course, $cm, $oublog, $participation, $groupid);
            }
        } else {
            // Posts.
            if ($participation->posts) {
                $table->add_data($table->posts);
                $table->add_data($table->postsheader);
                foreach ($participation->posts as $post) {
                    $row = array();
                    $row[] = userdate($post->timeposted, get_string('strftimedate'));
                    $row[] = userdate($post->timeposted, get_string('strftimetime'));
                    $row[] = (isset($post->title) && !empty($post->title)) ? $post->title : '';
                    $post->message = file_rewrite_pluginfile_urls($post->message,
                        'pluginfile.php', $modcontext->id, 'mod_oublog',
                        'message', $post->id);
                    $row[] = format_text($post->message, FORMAT_HTML);
                    $fs = get_file_storage();
                    if ($files = $fs->get_area_files($modcontext->id, 'mod_oublog', 'attachment',
                            $post->id, 'timemodified', false)) {
                        $attachmentstring = '';
                        foreach ($files as $file) {
                            $filename = $file->get_filename();
                            $attachmentstring .= ' ' . $filename . ', ';
                        }
                        $attachmentstring = substr($attachmentstring, 0, -2);
                        $row[] = $attachmentstring;
                    } else {
                        $row[] = '';
                    }
                    $table->add_data($row);
                }
            }

            // Comments.
            if ($participation->comments) {
                $table->add_data($table->comments);
                $table->add_data($table->commentsheader);
                foreach ($participation->comments as $comment) {
                    $author = new StdClass;
                    $author->id = $comment->authorid;
                    $userfields = get_all_user_name_fields();
                    foreach ($userfields as $field) {
                        $author->$field = $comment->$field;
                    }
                    $authorfullname = fullname($author, $viewfullnames);

                    $row = array();
                    $row[] = userdate($comment->timeposted, get_string('strftimedate'));
                    $row[] = userdate($comment->timeposted, get_string('strftimetime'));
                    $row[] = (isset($comment->title)) ? $comment->title : '';
                    $comment->message = file_rewrite_pluginfile_urls($comment->message,
                            'pluginfile.php', $modcontext->id, 'mod_oublog',
                            'messagecomment', $comment->id);
                    $row[] = format_text($comment->message, FORMAT_HTML);
                    $row[] = $authorfullname;
                    $row[] = userdate($comment->postdate, get_string('strftimedate'));
                    $row[] = userdate($comment->postdate, get_string('strftimetime'));
                    $row[] = (isset($comment->posttitle)) ? $comment->posttitle : '';
                    $table->add_data($row);
                }
            }
            if (!$participation->posts && !$participation->comments) {
                $table->add_data(array(''));
            }
            $table->finish_output();
        }

    }

    /**
     * Render single users grading form
     *
     * @param object $course current course object
     * @param object $cm current course module object
     * @param object $oublog current oublog object
     * @param object $user current user participation object
     * @param id $groupid optional group id, no group = 0
     */
    public function render_user_grade($course, $cm, $oublog, $user, $groupid) {
        global $CFG, $USER;

        if (is_null($user->gradeobj->grade)) {
            $user->gradeobj->grade = -1;
        }
        if ($user->gradeobj->grade != -1) {
            $user->grade = abs($user->gradeobj->grade);
        }
        $grademenu = make_grades_menu($oublog->grade);
        $grademenu[-1] = get_string('nograde');

        $formparams = array();
        $formparams['id'] = $cm->id;
        $formparams['user'] = $user->user->id;
        $formparams['group'] = $groupid;
        $formparams['sesskey'] = $USER->sesskey;
        $formaction = new moodle_url('/mod/oublog/savegrades.php', $formparams);
        $mform = new MoodleQuickForm('savegrade', 'post', $formaction,
            '', array('class' => 'savegrade'));
        $mform->addElement('header', 'usergrade', get_string('usergrade', 'oublog'));
        $mform->addElement('select', 'grade', get_string('grade'), $grademenu);
        $mform->setDefault('grade', $user->gradeobj->grade);
        $mform->addElement('submit', 'savechanges', get_string('savechanges'));

        $mform->display();
    }

    /**
     * Print comments which relate to a single blog post
     *
     * @param object $post Structure containing all post info and comments
     * @param object $oublog Blog object
     * @param bool $canmanagecomments Has capability toggle
     * @param bool $canaudit Has capability toggle
     * @param bool $forexport Export output rendering toggle
     * @param object $cm Current course module object
     * @return html
     */
    public function render_comments($post, $oublog, $canaudit, $canmanagecomments, $forexport,
            $cm, $format = false) {
        global $DB, $CFG, $USER, $OUTPUT;
        $viewfullnames = true;
        $strdelete      = get_string('delete', 'oublog');
        $strcomments    = get_string('comments', 'oublog');
        $output = '';
        $modcontext = context_module::instance($cm->id);
        if (!$canmanagecomments) {
            $context = context_module::instance($cm->id);
            $canmanagecomments = has_capability('mod/oublog:managecomments', $context);
        }

        $output .= html_writer::start_tag('div', array('class' => 'oublog-post-comments',
                'id' => 'oublogcomments'));
        $counter = 0;
        foreach ($post->comments as $comment) {
            $extraclasses = $comment->deletedby ? ' oublog-deleted' : '';
            $extraclasses .= ' oublog-hasuserpic';

            $output .= html_writer::start_tag('div', array('class' =>
                    'oublog-comment' . $extraclasses, 'id' => 'cid' . $comment->id));
            if ($counter == 0) {
                $output .= html_writer::tag('h2', format_string($strcomments),
                        array('class' => 'oublog-commentstitle'));
            }
            if ($comment->deletedby) {
                $deluser = new stdClass();
                $fields = get_all_user_name_fields(false, null, 'del');
                foreach ($fields as $field => $dfield) {
                    $deluser->$field = $comment->$dfield;
                }

                $a = new stdClass();
                $a->fullname = '<a href="../../user/view.php?id=' . $comment->deletedby . '">' .
                        fullname($deluser) . '</a>';
                $a->timedeleted = oublog_date($comment->timedeleted);

                $output .= html_writer::tag('div', get_string('deletedby', 'oublog', $a),
                        array('class' => 'oublog-comment-deletedby'));
            }
            if ($comment->userid && !$forexport) {
                $output .= html_writer::start_tag('div', array('class' => 'oublog-userpic'));
                $commentuser = new object();
                $fields = explode(',', user_picture::fields());
                foreach($fields as $field) {
                    if ($field != 'id') {
                        $commentuser->$field = $comment->$field;
                    }
                }
                $commentuser->id = $comment->userid;

                $output .= $OUTPUT->user_picture($commentuser,
                        array('courseid' => $oublog->course, 'size' => 70));
                $output .= html_writer::end_tag('div');
            }
            if (trim(format_string($comment->title))!=='') {
                $output .= html_writer::tag('h2', format_string($comment->title),
                        array('class' => 'oublog-title'));
            } else if (!$forexport) {
                $commenttitle = get_accesshide(get_string('newcomment', 'mod_oublog'));
                $output .= html_writer::tag('h2', $commenttitle, array('class' => 'oublog-title'));
            }
            $output .= html_writer::start_tag('div', array('class' => 'oublog-post-date'));
            $output .= oublog_date($comment->timeposted);
            $output .= html_writer::start_tag('div', array('class' => 'oublog-postedby'));
            if ($comment->userid ) {
                if (!$forexport) {
                    $output .= get_string('postedby', 'oublog',
                            '<a href="../../user/view.php?id=' . $comment->userid .
                            '&amp;course=' . $oublog->course . '">' .
                            fullname($comment) . '</a>');
                } else {
                    $output .= get_string('postedby', 'oublog', fullname($comment) );
                }
            } else {
                $output .= get_string(
                        $canaudit ? 'postedbymoderatedaudit' : 'postedbymoderated',
                        'oublog', (object)array(
                                'commenter' => s($comment->authorname),
                                'approver' => '<a href="../../user/view.php?id=' .
                                $comment->userid . '&amp;course=' . $oublog->course .
                                '">' . fullname($post) . '</a>',
                                'approvedate' => oublog_date($comment->timeapproved),
                                'ip' => s($comment->authorip)));
            }
            $output .= html_writer::end_tag('div');
            $output .= html_writer::end_tag('div');
            $output .= html_writer::start_tag('div',
                    array('class' => 'oublog-comment-content'));
            if (!$forexport) {
                if ($post->visibility == OUBLOG_VISIBILITY_PUBLIC) {
                    $fileurlbase = 'mod/oublog/pluginfile.php';
                } else {
                    $fileurlbase = 'pluginfile.php';
                }
                $comment->message = file_rewrite_pluginfile_urls($comment->message,
                        $fileurlbase, $modcontext->id, 'mod_oublog', 'messagecomment',
                        $comment->id);
            } else {
                $comment->message = portfolio_rewrite_pluginfile_urls($comment->message,
                        $modcontext->id, 'mod_oublog', 'messagecomment', $comment->id, $format);
            }
            $output .= format_text($comment->message, FORMAT_HTML);
            $output .= html_writer::end_tag('div');
            $output .= html_writer::start_tag('div',
                    array('class' => 'oublog-post-links'));
            if (!$comment->deletedby) {
                // You can delete your own comments, or comments on your own
                // personal blog, or if you can manage comments.
                if (($comment->userid && $comment->userid == $USER->id) ||
                        ($oublog->global && $post->userid == $USER->id) ||
                        $canmanagecomments ) {
                    if (!$forexport) {
                        $output .= '<a href="deletecomment.php?comment=' .
                                $comment->id . '">' . $strdelete.'</a>';
                    } else {
                        $output .= $strdelete;
                    }
                }
            }
            // Show OU Alerts reporting link.
            if (isloggedin() && oublog_oualerts_enabled()
                    && oublog_get_reportingemail($oublog) && !($comment->userid == $USER->id)
                    && !$comment->deletedby) {
                $itmurl = new moodle_url('/mod/oublog/viewpost.php',
                         array('post' => $post->id));
                $itemurl = $itmurl->out() . '#cid' . $comment->id;
                $retnurl = new moodle_url('/mod/oublog/viewpost.php',
                         array('post' => $post->id));
                $returnurl = $retnurl->out() . '#cid' . $comment->id;
                $reportlink = oualerts_generate_alert_form_url('oublog', $modcontext->id,
                        'comment', $comment->id, $itemurl, $returnurl, '', false, true);
                if ($reportlink != '') {
                    $output .= html_writer::tag('a', get_string('commentalert', 'oublog'),
                            array('href' => $reportlink));
                }
            }

            $output .= html_writer::end_tag('div');
            $output .= html_writer::end_tag('div');
            $counter++;
        }

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Override this within theme to add content before posts in view.php
     */
    public function render_viewpage_prepost() {
        return;
    }

    /**
     * Output Blog intro if introonpost is set for this blog.
     * @param object $oublog
     * @param object $cm
     */
    function render_pre_postform($oublog, $cm) {
        if (empty($oublog->introonpost) || $oublog->global) {
            return '';
        }
        $context = context_module::instance($cm->id);
        $content = file_rewrite_pluginfile_urls($oublog->intro, 'pluginfile.php',
                $context->id, 'mod_oublog', 'intro', null);
        $content = format_text($content, $oublog->introformat);
        return html_writer::div($content, 'oublog_editpost_intro');
    }

    // Blog stats renderers.

    /**
     * Output an unordered list - for accordion
     * @param string $name
     * @param array $tabs
     * @param int $default Default tab to open
     */
    public function render_stats_container($name, $tabs, $default = 1) {
        global $PAGE;
        $out = html_writer::start_tag('ul', array('class' => "oublog-accordion oublog-accordion-$name"));
        foreach ($tabs as $tab) {
            if (!empty($tab)) {
                $out .= html_writer::tag('li', $tab);
            }
        }
        $out .= html_writer::end_tag('ul');

        $default = get_user_preferences("oublog_accordion_{$name}_open", $default);
        user_preference_allow_ajax_update("oublog_accordion_{$name}_open", PARAM_INT);
        $PAGE->requires->yui_module('moodle-mod_oublog-accordion', 'M.mod_oublog.accordion.init',
                array($name, $default));

        return $out;
    }

    public function render_stats_view($name, $maintitle, $content, $subtitle = '', $info = '', $form = null, $ajax = false) {
        global $PAGE, $OUTPUT;
        if ($ajax) {
            // Don't render - return the data.
            $out = new stdClass();
            $out->name = $name;
            $out->maintitle = $maintitle;
            $out->maintitleclass = 'oublog_statsview_title';
            $out->subtitle = $subtitle;
            $out->subtitleclass = 'oublog_statsview_subtitle';
            $out->content = $content;
            $out->info = $info;
            $out->infoclass = "oublog_{$name}_info";
            $out->containerclass = "oublog_statsview_content_$name";
            $out->contentclass = "oublog_statsview_innercontent_$name";
            return $out;
        }
        $out = '';
        if (!empty($subtitle)) {
            $out .= $OUTPUT->heading($subtitle, 3, 'oublog_statsview_subtitle');
        }
        if (!empty($info)) {
            $out .= html_writer::start_tag('a', array('class' => 'block_action_oublog', 'tabindex' => 0, 'href' => '#'));

            $minushide = '';
            $plushide = ' oublog_displaynone';
            if ($userpref = get_user_preferences("mod_oublog_hidestatsform_$name", false)) {
                $minushide = ' oublog_displaynone';
                $plushide = '';
            }
            // Setup Javascript for stats view.
            user_preference_allow_ajax_update("mod_oublog_hidestatsform_$name", PARAM_BOOL);
            $PAGE->requires->js('/mod/oublog/module.js');
            $module = array ('name' => 'mod_oublog');
            $module['fullpath'] = '/mod/oublog/module.js';
            $module['requires'] = array('node', 'node-event-delegate');
            $module['strings'] = array();
            $PAGE->requires->js_init_call('M.mod_oublog.init_showhide', array($name, $userpref), false, $module);

            $out .= $this->output->pix_icon('t/switch_minus', get_string('timefilter_close', 'oublog'), 'moodle',
                    array('class' => 'oublog_stats_minus' . $minushide));
            $out .= $this->output->pix_icon('t/switch_plus', get_string('timefilter_open', 'oublog'), 'moodle',
                    array('class' => 'oublog_stats_plus' . $plushide));
            $out .= html_writer::end_tag('a');

            // Stats bar - call once per 'view'.
            $PAGE->requires->yui_module('moodle-mod_oublog-statsbar', 'M.mod_oublog.statsbar.init',
                    array("oublog_statsview_content_$name"));
            $out .= html_writer::tag('p', $info, array('class' => "oublog_{$name}_info"));
        }
        if (!empty($form)) {
            $out .= $form->render();
        }
        $out .= html_writer::div($content, "oublog_statsview_innercontent oublog_statsview_innercontent_$name");
        return html_writer::div($this->output->heading($maintitle, 2), 'oublog_statsview_title') .
            $this->output->container($out, "oublog_statsview_content oublog_statsview_content_$name");
    }

    /**
     * Renders the 'statsinfo' widget - info chart on a blog/post
     * @param oublog_statsinfo $info
     * @return string
     */
    public function render_oublog_statsinfo(oublog_statsinfo $info) {
        global $COURSE, $OUTPUT;
        $out = '';
        // Get the avatar picture for user/group.
        if (isset($info->user->courseid)) {
            // Group not user.
            if (!$userpic = print_group_picture($info->user, $info->user->courseid, true, true, false)) {
                // No group pic set, use default user image.
                $userpic = $OUTPUT->pix_icon('u/f2', '');
            }
        } else {
            $userpic = $this->output->user_picture($info->user, array('courseid' => $COURSE->id, 'link' => false));
        }
        $avatar = html_writer::link($info->url, $userpic, array('class' => 'oublog_statsinfo_avatar'));
        $infodiv = html_writer::start_div('oublog_statsinfo_infocol');
        if ($info->stat) {
            $infodiv .= html_writer::start_div('oublog_statsinfo_bar');
            $infodiv .= html_writer::tag('span', $info->stat, array('class' => 'percent_' . $info->percent));
            $infodiv .= html_writer::end_div();
        }
        $infodiv .= html_writer::div($info->label, 'oublog_statsinfo_label');
        $infodiv .= html_writer::end_div();
        $out = $avatar . $infodiv;
        return $this->output->container($out, 'oublog_statsinfo');
    }

    public function render_oublog_print_delete_dialog($cmid, $postid) {
        global $PAGE;
        $PAGE->requires->js('/mod/oublog/module.js');
        $stringlist[] = array('deleteemailpostdescription', 'oublog');
        $stringlist[] = array('delete', 'oublog');
        $stringlist[] = array('deleteandemail', 'oublog');
        $stringlist[] = array('cancel', 'oublog');
        $jsmodule = array(
                'name' => 'mod_oublog.init_deleteandemail',
                'fullpath' => '/mod/oublog/module.js',
                'requires' => array('base', 'event', 'node', 'panel', 'anim', 'moodle-core-notification', 'button'),
                'strings' => $stringlist);
        $PAGE->requires->js_init_call('M.mod_oublog.init_deleteandemail', array($cmid, $postid), true, $jsmodule);
    }

}

class oublog_statsinfo implements renderable {
    public $percent = 0;
    public $url = '';
    public $label = '';
    public $user;
    public $stat = '';

    /**
     *
     * @param stdClass $user user/group for avatar picture
     * @param int $percent Percent bar will go
     * @param string $stat Stat text shown in bar
     * @param moodle_url $url url for user pic link
     * @param string $label Label that appears under bar
     */
    public function __construct(stdClass $user, $percent, $stat, moodle_url $url, $label) {
        $this->percent = round($percent);
        $this->label = $label;
        $this->url = $url;
        $this->user = $user;
        $this->stat = $stat;
    }
}
