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
 * This page allows a user to delete a blog posts
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */
require_once("../../config.php");
require_once($CFG->dirroot . '/mod/oublog/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

$blog = required_param('blog', PARAM_INT);         // Blog ID.
$postid = required_param('post', PARAM_INT);       // Post ID for editing.
$confirm = optional_param('confirm', 0, PARAM_INT);// Confirm that it is ok to delete post.
$delete = optional_param('delete', 0, PARAM_INT);
$email = optional_param('email', 0, PARAM_INT);    // Email author.
$referurl = optional_param('referurl', 0, PARAM_LOCALURL);
$cmid = optional_param('cmid', null, PARAM_INT);

if (!$oublog = $DB->get_record("oublog", array("id"=>$blog))) {
    print_error('invalidblog', 'oublog');
}
if (!$post = oublog_get_post($postid, false)) {
    print_error('invalidpost', 'oublog');
}
if (!$cm = get_coursemodule_from_instance('oublog', $blog)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record("course", array("id"=>$oublog->course))) {
    print_error('coursemisconf');
}
$url = new moodle_url('/mod/oublog/deletepost.php',
        array('blog' => $blog, 'post' => $postid, 'confirm' => $confirm));
$PAGE->set_url($url);

// Check security.
$context = context_module::instance($cm->id);
$childdata = oublog_get_blog_data_base_on_cmid_of_childblog($cmid, $oublog);
$childoublog = null;
$childcourse = null;
$childcm = null;
if (!empty($childdata)) {
    $context = $childdata['context'];
    $childoublog = $childdata['ousharedblog'];
    $childcourse = $childdata['course'];
    $childcm = $childdata['cm'];
    oublog_check_view_permissions($childdata['ousharedblog'], $childdata['context'], $childdata['cm']);
} else {
    oublog_check_view_permissions($oublog, $context, $cm);
}
$correctglobal = isset($childoublog->global) ? $childoublog->global : $oublog->global;
$postauthor=$DB->get_field_sql("
SELECT
    i.userid
FROM
    {oublog_posts} p
    INNER JOIN {oublog_instances} i on p.oubloginstancesid=i.id
WHERE p.id = ?", array($postid));
if ($postauthor!=$USER->id) {
    require_capability('mod/oublog:manageposts', $context);
}

$oublogoutput = $PAGE->get_renderer('mod_oublog');

if ($correctglobal) {
    $blogtype = 'personal';
    $oubloguser = $USER;
    $viewurl = new moodle_url('/mod/oublog/view.php', array('user' => $postauthor));
    if (isset($referurl)) {
        $viewurl = new moodle_url($referurl);
    }
    // Print the header.
    $PAGE->navbar->add(fullname($oubloguser), new moodle_url('/user/view.php',
            array('id' => $oubloguser->id)));
    $PAGE->navbar->add(format_string(!empty($childoublog->name) ? $childoublog->name : $oublog->name));
} else {
    $blogtype = 'course';
    $viewurl = new moodle_url('/mod/oublog/view.php', array('id' => $childcm ? $childcm->id : $cm->id));
    if (isset($referurl)) {
        $viewurl = new moodle_url($referurl);
    }
}

if ($email) {
    // Then open and process the form.
    require_once($CFG->dirroot . '/mod/oublog/deletepost_form.php');
    $customdata = (object)array('blog' => $blog, 'post' => $postid,
            'delete' => $delete, 'email' => $email, 'referurl' => $viewurl, 'cmid' => $cmid);
    $mform = new mod_oublog_deletepost_form('deletepost.php', $customdata);
    if ($mform->is_cancelled()) {
        // Form is cancelled, redirect back to the blog.
        redirect($viewurl);
    } else if ($submitted = $mform->get_data()) {
        // Mark the post as deleted.
        oublog_do_delete($course, $cm, $oublog, $post);
        // We need these for the call to render post.
        $canaudit = $canmanageposts = false;

        // Store copy of the post for the author.
        // If subject is set in this post, use it.
        if (!isset($post->title) || empty($post->title)) {
            $post->title = get_string('deletedblogpost', 'oublog');
        }
        $messagepost = $oublogoutput->render_post($cm, $oublog, $post, $viewurl, $blogtype,
                $canmanageposts, $canaudit, false, false, false, true, 'top', $cm, $cmid);

        // Set up the email message detail.
        $messagetext = $submitted->message['text'];
        $copyself = (isset($submitted->copyself)) ? true : false;
        $includepost = (isset($submitted->includepost)) ? true : false;
        $from = $SITE->fullname;

        // Use prefered format for author of the post.
        $user = (object)array(
                'email' => $post->email,
                'mailformat' => $post->mailformat,
                'id' => $post->userid
        );

        $messagehtml = text_to_html($messagetext);

        // Include the copy of the post in the email.
        if ($includepost) {
            $messagehtml .= $messagepost;
        }
        // Send an email to the author of the post.
        if (!email_to_user($user, $from, $post->title, html_to_text($messagehtml), $messagehtml)) {
            print_error(get_string('emailerror', 'oublog'));
        }

        // Prepare for copies.
        $emails = array();
        if ($copyself) {
            // Send an email copy to the current user, with prefered format.
            $subject = strtoupper(get_string('copy')) . ' - '. $post->title;
            if (!email_to_user($USER, $from, $subject, html_to_text($messagehtml), $messagehtml)) {
                print_error(get_string('emailerror', 'oublog'));
            }
        }

        // Addition of 'Email address of other recipients'.
        if (!empty($submitted->emailadd)) {
            $emails = preg_split('~[; ]+~', $submitted->emailadd);
        }

        // If there are any recipients listed send them a HTML copy.
        if (!empty($emails[0])) {
            $subject = strtoupper(get_string('copy')) . ' - '. $post->title;
            foreach ($emails as $email) {
                $fakeuser = (object)array(
                        'email' => $email,
                        'mailformat' => 1,
                        'id' => -1
                );
                if (!email_to_user($fakeuser, $from, $subject, '', $messagehtml)) {
                    print_error(get_string('emailerror', 'oublog'));
                }
            }
        }
        redirect($viewurl);
    } else if (($delete && $email) ) {
        // If subject is set in this post, use it.
        if (!isset($post->title) || empty($post->title)) {
            $post->title = get_string('deletedblogpost', 'oublog');
        }
        $displayname = oublog_get_displayname($childoublog ? $childoublog : $oublog, true);
        // Prepare the object for the emailcontenthtml get_string.
        $emailmessage = new stdClass;
        $emailmessage->subject = $post->title;
        $emailmessage->blog = $childoublog ? $childoublog->name : $oublog->name;
        $emailmessage->activityname = $displayname;
        $emailmessage->firstname = $USER->firstname;
        $emailmessage->lastname = $USER->lastname;
        $emailmessage->course = !empty($childcourse->fullname) ? $childcourse->fullname : $COURSE->fullname;
        $emailmessage->deleteurl = $CFG->wwwroot . '/mod/oublog/viewpost.php?&post=' . $post->id;
        $formdata = new stdClass;
        $messagetext = get_string('emailcontenthtml', 'oublog', $emailmessage);
        $formdata->message['text'] = $messagetext;
        // Display the form.
        echo $OUTPUT->header();
        $mform->set_data($formdata);
        $mform->display();
    }
} else {
    if (!$confirm) {
        $PAGE->set_title(format_string(!empty($childoublog->name) ? $childoublog->name : $oublog->name));
        $PAGE->set_heading(format_string(!empty($childcourse->fullname) ? $childcourse->fullname : $course->fullname));
        echo $OUTPUT->header();
        $confirmdeletestring = get_string('confirmdeletepost', 'oublog');
        $confirmstring = get_string('deleteemailpostdescription', 'oublog');

        $deletebutton = new single_button(new moodle_url('/mod/oublog/deletepost.php',
                array('blog' => $blog, 'post' => $postid, 'delete' => '1',
                        'confirm' => '1', 'referurl' => $viewurl, 'cmid' => $cmid)), get_string('delete'), 'post');
        $cancelbutton = new single_button($viewurl, get_string('cancel'), 'get');

        if ($USER->id == $post->userid) {
            print $OUTPUT->confirm($confirmdeletestring, $deletebutton, $cancelbutton);
        } else {
            // Delete - Delete and email || Cancel.
            $deleteemailbutton = new single_button(new moodle_url('/mod/oublog/deletepost.php',
                    array('blog' => $blog, 'post' => $postid, 'email' => '1', 'delete' => '1')),
                    get_string('deleteemailpostbutton', 'oublog'), 'post');
            print oublog_three_button($confirmstring,
                    $deletebutton,
                    $deleteemailbutton,
                    $cancelbutton);
        }
    } else {
        // Mark the post as deleted.
        oublog_do_delete($course, $cm, $oublog, $post);
        redirect($viewurl);
    }
}

echo $OUTPUT->footer();

function oublog_do_delete($course, $cm, $oublog, $post) {
    global $DB, $USER;
    $updatepost = (object)array(
            'id' => $post->id,
            'deletedby' => $USER->id,
            'timedeleted' => time()
    );

    $transaction = $DB->start_delegated_transaction();
    $DB->update_record('oublog_posts', $updatepost);
    if (!oublog_update_item_tags($post->oubloginstancesid, $post->id,
            array(), $post->visibility)) {
        print_error('tagupdatefailed', 'oublog');
    }
    if (oublog_search_installed()) {
        $doc = oublog_get_search_document($updatepost, $cm);
        $doc->delete();
    }
    // Inform completion system, if available.
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && ($oublog->completionposts)) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $post->userid);
    }
    $transaction->allow_commit();

    // Log post deleted event.
    $context = context_module::instance($cm->id);
    $params = array(
        'context' => $context,
        'objectid' => $post->id,
        'other' => array(
            'oublogid' => $oublog->id
        )
    );
    $event = \mod_oublog\event\post_deleted::create($params);
    $event->trigger();
}

/**
 * Print a message along with three buttons buttoneone/buttontwo/Cancel
 *
 * If a string or moodle_url is given instead of a single_button, method defaults to post.
 *
 * @param string $message The question to ask the user.
 * @param single_button $buttonone The single_button component representing the buttontwo response.
 * @param single_button $buttontwo The single_button component representing the buttontwo response.
 * @param single_button $cancel The single_button component representing the Cancel response.
 * @return string HTML fragment
 */
function oublog_three_button($message, $buttonone, $buttontwo, $cancel) {
    global $OUTPUT;
    if (!($buttonone instanceof single_button)) {
        throw new coding_exception('The buttonone param must be an instance of a single_button.');
    }

    if (!($buttontwo instanceof single_button)) {
        throw new coding_exception('The buttontwo param must be an instance of a single_button.');
    }

    if (!($cancel instanceof single_button)) {
        throw new coding_exception('The cancel param must be an instance of a single_button.');
    }

    $output = $OUTPUT->box_start('generalbox', 'notice');
    $output .= html_writer::tag('p', $message);
    $buttons = $OUTPUT->render($buttonone) . $OUTPUT->render($buttontwo) . $OUTPUT->render($cancel);
    $output .= html_writer::tag('div', $buttons, array('class' => 'buttons'));
    $output .= $OUTPUT->box_end();
    return $output;
}
