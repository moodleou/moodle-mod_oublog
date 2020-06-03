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
 * Define the OU Blog module creation form
 *
 * @access Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */
if (defined('OUBLOG_EDIT_INSTANCE')) {

    require_once($CFG->libdir.'/formslib.php');
    abstract class moodleform_mod extends moodleform {
    } // Fake that we are using the moodleform_mod base class.

} else {
    require_once('moodleform_mod.php');
}
require_once('locallib.php');

class mod_oublog_mod_form extends moodleform_mod {

    public function definition() {

        global $COURSE, $CFG;
        $mform    = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('blogname', 'oublog'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        if (!defined('OUBLOG_EDIT_INSTANCE')) {
            $this->standard_intro_elements(get_string('oublogintro', 'oublog'));
            // Adding the "allowcomments" field.
            $options = array(OUBLOG_COMMENTS_ALLOW => get_string('logincomments', 'oublog'),
                    OUBLOG_COMMENTS_ALLOWPUBLIC => get_string('publiccomments', 'oublog'),
                    OUBLOG_COMMENTS_PREVENT => get_string('nocomments', 'oublog'));

            $mform->addElement('select', 'allowcomments', get_string('allowcommentsmax', 'oublog'), $options);
            $mform->setType('allowcomments', PARAM_INT);
            $mform->addHelpButton('allowcomments', 'allowcomments', 'oublog');

            // Adding the "individual" field.
            $options = array(OUBLOG_NO_INDIVIDUAL_BLOGS => get_string('no_blogtogetheroringroups', 'oublog'),
                    OUBLOG_SEPARATE_INDIVIDUAL_BLOGS => get_string('separateindividualblogs', 'oublog'),
                    OUBLOG_VISIBLE_INDIVIDUAL_BLOGS => get_string('visibleindividualblogs', 'oublog'));
            $mform->addElement('select', 'individual', get_string('individualblogs', 'oublog'), $options);
            $mform->setType('individual', PARAM_INT);
            $mform->setDefault('individual', OUBLOG_NO_INDIVIDUAL_BLOGS);
            $mform->addHelpButton('individual', 'individualblogs', 'oublog');

            // Disable "maxvisibility" field when "individual" field is set (not default).
            $mform->disabledIf('maxvisibility', 'individual', OUBLOG_NO_INDIVIDUAL_BLOGS, OUBLOG_NO_INDIVIDUAL_BLOGS);

            // Adding the "maxvisibility" field.
            $options = array(OUBLOG_VISIBILITY_COURSEUSER => get_string('visiblecourseusers', 'oublog'),
                    OUBLOG_VISIBILITY_LOGGEDINUSER => get_string('visibleloggedinusers', 'oublog'),
                    OUBLOG_VISIBILITY_PUBLIC => get_string('visiblepublic', 'oublog'));

            $haschild = false;
            if (!empty($this->_cm->idnumber)) {
                $haschild = !empty(oublog_get_children($this->_cm->idnumber)) ? true : false;
            }
            if ($haschild) {
                $mform->addElement('text', 'idsharedblog', get_string('sharedblog', 'oublog'), array('disabled'));
            } else {
                // Enable "sharedblog" field when "individual" field is set.
                $mform->disabledIf('idsharedblog', 'individual', 'eq', OUBLOG_NO_INDIVIDUAL_BLOGS);
                $mform->addElement('text', 'idsharedblog', get_string('sharedblog', 'oublog'));
            }
            $mform->setType('idsharedblog', PARAM_TEXT);
            // Have to add content later.
            $mform->addHelpButton('idsharedblog', 'sharedblog', 'oublog');

            $mform->addElement('select', 'maxvisibility', get_string('maxvisibility', 'oublog'), $options);
            $mform->setType('maxvisibility', PARAM_INT);
            $mform->addHelpButton('maxvisibility', 'maxvisibility', 'oublog');

            // Whether intro text shows on post form pages.
            $mform->addElement('checkbox', 'introonpost', get_string('introonpost', 'oublog'), '', 0);

            // Max size of attachments.
            $modulesettings = get_config('mod_oublog');
            $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
            $mform->addElement('select', 'maxbytes',
                    get_string('maxattachmentsize', 'oublog'), $choices);
            $mform->addHelpButton('maxbytes', 'maxattachmentsize', 'oublog');
            $mform->setDefault('maxbytes', $modulesettings->maxbytes);

            // Max number of attachments.
            $choices = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20 => 20, 50 => 50, 100 => 100);
            $mform->addElement('select', 'maxattachments',
                    get_string('maxattachments', 'oublog'), $choices);
            $mform->addHelpButton('maxattachments', 'maxattachments', 'oublog');
            $mform->setDefault('maxattachments', $modulesettings->maxattachments);

            // Number of post per page.
            $choices = array(25 => 25, 50 => 50, 75 => 75, 100 => 100);
            $mform->addElement('select', 'postperpage',
                get_string('numberofposts', 'oublog'), $choices);
            $mform->addHelpButton('postperpage', 'numberofposts', 'oublog');
            $mform->setDefault('postperpage', 25);

            // Show OU Alerts reporting link.
            if (oublog_oualerts_enabled()) {
                $mform->addElement('text', 'reportingemail', get_string('reportingemail', 'oublog'),
                        array('size'=>'48'));
                $mform->addHelpButton('reportingemail', 'reportingemail', 'oublog');
                $mform->setType('reportingemail', PARAM_NOTAGS);
                $mform->addRule('reportingemail', get_string('maximumchars', '', 255),
                        'maxlength', 255, 'client');
            }

            $mform->addElement('header', 'advanced', get_string('advancedoptions', 'oublog'));

            // Enable the stats block.
            $mform->addElement('checkbox', 'statblockon', get_string('statblockon', 'oublog'), '', 0);
            $mform->addHelpButton('statblockon', 'statblockon', 'oublog');

            $mform->addElement('text', 'displayname', get_string('displayname', 'oublog'),
                    array('size'=>'48'));
            $mform->addHelpButton('displayname', 'displayname', 'oublog');
            $mform->setType('displayname', PARAM_NOTAGS);
            $mform->addRule('displayname', get_string('maximumchars', '', 255),
                    'maxlength', 255, 'client');

            $mform->addElement('checkbox', 'allowimport', get_string('allowimport', 'oublog'), '', 0);
            $mform->addHelpButton('allowimport', 'allowimport', 'oublog');

            $mform->addElement('header', 'tagheading', get_string('tags', 'oublog'));

            $mform->addElement('text', 'tagslist', get_string('tags', 'oublog'),
                            array('size'=>'48'));
            $mform->addHelpButton('tagslist', 'predefinedtags', 'oublog');
            $mform->setType('tagslist', PARAM_TAGLIST);
            $mform->addRule('tagslist', get_string('maximumchars', '', 255),
                            'maxlength', 255, 'client');

            $tagopts = array(
                    '0' => get_string('none'),
                    '1' => get_string('restricttags_set', 'oublog'),
                    '2' => get_string('restricttags_req', 'oublog'),
                    '3' => get_string('restricttags_req_set', 'oublog'),
                    '4' => get_string('restricttags_default', 'oublog')
            );
            $mform->addElement('select', 'restricttags', get_string('restricttags', 'oublog'), $tagopts);
            $mform->addHelpButton('restricttags', 'restricttags', 'oublog');

            $mform->addElement('header', 'limits', get_string('limits', 'oublog'));

            // Limiting post/comments dates.
            $mform->addElement('date_time_selector', 'postfrom',
                    get_string('postfrom', 'oublog'), array('optional' => true));
            $mform->addElement('date_time_selector', 'postuntil',
                    get_string('postuntil', 'oublog'), array('optional' => true));
            $mform->addElement('date_time_selector', 'commentfrom',
                    get_string('commentfrom', 'oublog'), array('optional' => true));
            $mform->addElement('date_time_selector', 'commentuntil',
                    get_string('commentuntil', 'oublog'), array('optional' => true));

            $mform->disabledIf('commentfrom', 'allowcomments', 'eq', 0);
            $mform->disabledIf('commentuntil', 'allowcomments', 'eq', 0);

            $mform->addElement('header', 'modstandardgrade', get_string('grade'));
            // Adding the "grading" field.
            $options = array(OUBLOG_NO_GRADING => get_string('nograde', 'oublog'),
                    OUBLOG_TEACHER_GRADING => get_string('teachergrading', 'oublog'),
                    OUBLOG_USE_RATING => get_string('userrating', 'oublog'));
            $mform->addElement('select', 'grading', get_string('grading', 'oublog'), $options);
            $mform->setType('grading', PARAM_INT);
            $mform->addHelpButton('grading', 'grading', 'oublog');

            $mform->addElement('modgrade', 'grade', get_string('grade'));
            $mform->addHelpButton('grade', 'modgrade', 'grades');
            $mform->setDefault('grade', $CFG->gradepointdefault);
            $mform->disabledIf('grade', 'grading', 'ne', OUBLOG_TEACHER_GRADING);

            // Add standard elements, common to all modules.
            $features = new stdClass;
            $features->groupings = true;
            $this->standard_coursemodule_elements($features);
        } else {
            // Adding the "summary" field.
            $mform->addElement('editor', 'summary_editor', get_string('summary', 'oublog'), null,
                    array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes));
            $mform->setType('summary', PARAM_RAW);
            $mform->addElement('hidden', 'instance');
            $mform->setType('instance', PARAM_INT);
        }

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

    }

    public function add_completion_rules() {
        $mform =& $this->_form;

        $group=array();
        $group[] =& $mform->createElement('checkbox', 'completionpostsenabled', ' ', get_string('completionposts', 'oublog'));
        $group[] =& $mform->createElement('text', 'completionposts', ' ', array('size'=>3));
        $mform->setType('completionposts', PARAM_INT);
        $mform->addGroup($group, 'completionpostsgroup', get_string('completionpostsgroup', 'oublog'), array(' '), false);
        $mform->addHelpButton('completionpostsgroup', 'completionpostsgroup', 'oublog');
        $mform->disabledIf('completionposts', 'completionpostsenabled', 'notchecked');

        $group=array();
        $group[] =& $mform->createElement('checkbox', 'completioncommentsenabled', ' ', get_string('completioncomments', 'oublog'));
        $group[] =& $mform->createElement('text', 'completioncomments', ' ', array('size'=>3));
        $mform->setType('completioncomments', PARAM_INT);
        $mform->addGroup($group, 'completioncommentsgroup', get_string('completioncommentsgroup', 'oublog'), array(' '), false);
        $mform->addHelpButton('completioncommentsgroup', 'completioncommentsgroup', 'oublog');
        $mform->disabledIf('completioncomments', 'completioncommentsenabled', 'notchecked');

        // Restriction for grade completion
        $mform->disabledIf('completionusegrade', 'grade', 'eq', 0);

        return array('completionpostsgroup', 'completioncommentsgroup');
    }

    public function completion_rule_enabled($data) {
        return ((!empty($data['completionpostsenabled']) && $data['completionposts']!=0)) ||
            ((!empty($data['completioncommentsenabled']) && $data['completioncomments']!=0));
    }

    public function get_data() {
        $data=parent::get_data();
        if (!$data) {
            return false;
        }
        // Turn off completion settings if the checkboxes aren't ticked
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionpostsenabled) || !$autocompletion) {
                $data->completionposts = 0;
            }
            if (empty($data->completioncommentsenabled) || !$autocompletion) {
                $data->completioncomments = 0;
            }
        }
        // If maxvisibility is disabled by individual mode, ensure it's limited to course.
        if (isset($data->individual) && ($data->individual == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS
                || $data->individual == OUBLOG_VISIBLE_INDIVIDUAL_BLOGS)) {
            $data->maxvisibility = OUBLOG_VISIBILITY_COURSEUSER;
        }
        // Set the reportingemail to null if empty so that we have consistency.
        if (empty($data->reportingemail)) {
            $data->reportingemail = null;
        }
        // Set statblockon to null if empty so that we have consistency.
        if (empty($data->statblockon)) {
            $data->statblockon = 0;
        }
        if (empty($data->displayname)) {
            $data->displayname = null;
        }
        if (empty($data->allowimport)) {
            $data->allowimport = 0;
        }
        if (empty($data->introonpost)) {
            $data->introonpost = 0;
        }
        if (!empty($data->tagslist)) {
            $data->tagslist = core_text::strtolower(trim($data->tagslist));
        }
        if (empty($data->restricttags)) {
            $data->restricttags = 0;
        }
        if (empty($data->postfrom)) {
            $data->postfrom = 0;
        }
        if (empty($data->postuntil)) {
            $data->postuntil = 0;
        }
        if (empty($data->commentfrom)) {
            $data->commentfrom = 0;
        }
        if (empty($data->commentuntil)) {
            $data->commentuntil = 0;
        }
        if (isset($data->grading) && $data->grading == OUBLOG_NO_GRADING) {
            // Unset grade if grading turned off.
            $data->grade = 0;
        }
        return $data;
    }

    public function data_preprocessing(&$default_values) {
        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $default_values['completionpostsenabled']=
            !empty($default_values['completionposts']) ? 1 : 0;
        if (empty($default_values['completionposts'])) {
            $default_values['completionposts']=1;
        }
        $default_values['completioncommentsenabled']=
            !empty($default_values['completioncomments']) ? 1 : 0;
        if (empty($default_values['completioncomments'])) {
            $default_values['completioncomments']=1;
        }
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        if (!empty($data['groupmode']) && isset($data['allowcomments']) &&
                $data['allowcomments'] == OUBLOG_COMMENTS_ALLOWPUBLIC) {
            $errors['allowcomments'] = get_string('error_grouppubliccomments', 'oublog');
        }
        if (!empty($data['reportingemail'])) {
            $emails = explode(',', trim($data['reportingemail']));
            foreach ($emails as $email) {
                if (!validate_email($email)) {
                    $errors['reportingemail'] = get_string('invalidemail', 'forumng');
                }
            }
        }
        if (!empty($data['allowimport']) && $data['individual'] == OUBLOG_NO_INDIVIDUAL_BLOGS) {
            // Can only import on individual or global blogs.
            if (!empty($data['instance'])) {
                if (!$DB->get_field('oublog', 'global', array('id' => $data['instance']))) {
                    $errors['allowimport'] = get_string('allowimport_invalid', 'oublog');
                }
            } else {
                $errors['allowimport'] = get_string('allowimport_invalid', 'oublog');
            }
        }
        // If form is on blog edit page rather than the blog options edit instance page.
        if (isset($data['grading'])) {
           if (($data['grading'] == OUBLOG_TEACHER_GRADING && empty($data['grade'])) ||
                    ($data['grading'] == OUBLOG_USE_RATING && empty($data['assessed']))) {
                $errors['grading'] = get_string('grading_invalid', 'oublog');
            }
        }
        if (isset($data['restricttags']) && empty($data['tagslist'])
                && ($data['restricttags'] == 1 || $data['restricttags'] == 3)) {
            // When forcing use of pre-defined tags must define some.
            $errors['tagslist'] = get_string('required');
        }
        if (!empty($data['idsharedblog'])) {
            $masterblog = oublog_get_master($data['idsharedblog'], false);

            // Cannot get master if it doesn't have ID number or has more than 1.
            if (empty($masterblog)) {
                $errors['idsharedblog'] = get_string('sharedblog_invalid', 'oublog');
            }
            if (count($masterblog) > 1) {
                $errors['idsharedblog'] = get_string('sharedblog_invalid_morethan1', 'oublog');
            }
            // Cannot be child if it is master.
            if (!empty($data['cmidnumber'])) {
                $validatemasterblog = oublog_get_children($data['cmidnumber']);
                if (!empty($validatemasterblog)) {
                    $errors['idsharedblog'] = get_string('sharedblog_mastered', 'oublog');
                }
            }

            $masterblog = reset($masterblog);
            // Cannot be master blog if it already child of the other blog.
            if (!empty($masterblog->idsharedblog)) {
                $errors['idsharedblog'] = get_string('sharedblog_existed', 'oublog');
            }
        }
        return $errors;
    }
}
