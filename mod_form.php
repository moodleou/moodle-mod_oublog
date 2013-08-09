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

        if (!defined('OUBLOG_EDIT_INSTANCE')) {
            $this->add_intro_editor(false, get_string('oublogintro', 'oublog'));
            // Adding the "allowcomments" field.
            $options = array(OUBLOG_COMMENTS_ALLOW   => get_string('logincomments', 'oublog'),
                    OUBLOG_COMMENTS_ALLOWPUBLIC => get_string('publiccomments', 'oublog'),
                    OUBLOG_COMMENTS_PREVENT => get_string('nocomments', 'oublog'));

            $mform->addElement('select', 'allowcomments', get_string('allowcommentsmax', 'oublog'), $options);
            $mform->setType('allowcomments', PARAM_INT);
            $mform->addHelpButton('allowcomments', 'allowcomments', 'oublog');

            // Adding the "individual" field.
            $options = array(OUBLOG_NO_INDIVIDUAL_BLOGS       => get_string('no_blogtogetheroringroups', 'oublog'),
                             OUBLOG_SEPARATE_INDIVIDUAL_BLOGS => get_string('separateindividualblogs', 'oublog'),
                             OUBLOG_VISIBLE_INDIVIDUAL_BLOGS  => get_string('visibleindividualblogs', 'oublog'));
            $mform->addElement('select', 'individual', get_string('individualblogs', 'oublog'), $options);
            $mform->setType('individual', PARAM_INT);
            $mform->setDefault('individual', OUBLOG_NO_INDIVIDUAL_BLOGS);
            $mform->addHelpButton('individual', 'individualblogs', 'oublog');

            // Disable "maxvisibility" field when "individual" field is set (not default).
            $mform->disabledIf('maxvisibility', 'individual', OUBLOG_NO_INDIVIDUAL_BLOGS, OUBLOG_NO_INDIVIDUAL_BLOGS);

            // Adding the "maxvisibility" field.
            $options = array(OUBLOG_VISIBILITY_COURSEUSER   => get_string('visiblecourseusers', 'oublog'),
                             OUBLOG_VISIBILITY_LOGGEDINUSER => get_string('visibleloggedinusers', 'oublog'),
                             OUBLOG_VISIBILITY_PUBLIC       => get_string('visiblepublic', 'oublog'));

            $mform->addElement('select', 'maxvisibility', get_string('maxvisibility', 'oublog'), $options);
            $mform->setType('maxvisibility', PARAM_INT);
            $mform->addHelpButton('maxvisibility', 'maxvisibility', 'oublog');

            // Max size of attachments.
            $modulesettings = get_config('mod_oublog');
            $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
            $choices[-1] = get_string('uploadnotallowed');
            $mform->addElement('select', 'maxbytes',
                    get_string('maxattachmentsize', 'oublog'), $choices);
            $mform->addHelpButton('maxbytes', 'maxattachmentsize', 'oublog');
            $mform->setDefault('maxbytes', $modulesettings->maxbytes);

            // Max number of attachments.
            $choices = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 50, 100);
            $mform->addElement('select', 'maxattachments',
                    get_string('maxattachments', 'oublog'), $choices);
            $mform->addHelpButton('maxattachments', 'maxattachments', 'oublog');
            $mform->setDefault('maxattachments', $modulesettings->maxattachments);

            $this->standard_grading_coursemodule_elements();
            $mform->setDefault('grade', 0);

            // Add standard elements, common to all modules.
            $features = new stdClass;
            $features->groupings = true;
            $features->groupmembersonly = true;
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
        $autocompletion=!empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;
        if (empty($data->completionpostsenabled) || !$autocompletion) {
            $data->completionposts=0;
        }
        if (empty($data->completioncommentsenabled) || !$autocompletion) {
            $data->completioncomments=0;
        }
        // If maxvisibility is disabled by individual mode, ensure it's limited to course.
        if (isset($data->individual) && ($data->individual == OUBLOG_SEPARATE_INDIVIDUAL_BLOGS
                || $data->individual == OUBLOG_VISIBLE_INDIVIDUAL_BLOGS)) {
            $data->maxvisibility = OUBLOG_VISIBILITY_COURSEUSER;
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
        $errors = parent::validation($data, $files);
        if (!empty($data['groupmode']) && isset($data['allowcomments']) &&
                $data['allowcomments'] == OUBLOG_COMMENTS_ALLOWPUBLIC) {
            $errors['allowcomments'] = get_string('error_grouppubliccomments', 'oublog');
        }
        return $errors;
    }
}
