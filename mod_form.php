<?php

/**
 * Define the OU Blog module creation form
 *
 * @access Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */
if (defined('OUBLOG_EDIT_INSTANCE')) {

    require_once($CFG->libdir.'/formslib.php');
    abstract class moodleform_mod extends moodleform {} // fake that we are using the moodleform_mod base class

} else {
    require_once ('moodleform_mod.php');
}
require_once ('locallib.php');

class mod_oublog_mod_form extends moodleform_mod {

    function definition() {

        global $COURSE;
        $mform    =& $this->_form;

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('blogname', 'oublog'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

    /// Adding the "summary" field
        $mform->addElement('htmleditor', 'summary', get_string('summary', 'oublog'));
        $mform->setType('summary', PARAM_RAW);

        if (!defined('OUBLOG_EDIT_INSTANCE')) {
        /// Adding the "allowcomments" field
            $options = array(OUBLOG_COMMENTS_ALLOW   => get_string('logincomments', 'oublog'),
                 OUBLOG_COMMENTS_ALLOWPUBLIC => get_string('publiccomments', 'oublog'),
                             OUBLOG_COMMENTS_PREVENT => get_string('nocomments', 'oublog'));

            $mform->addElement('select', 'allowcomments', get_string('allowcommentsmax', 'oublog'), $options);
            $mform->setType('allowcomments', PARAM_INT);
            $mform->addHelpButton('allowcomments', 'allowcomments', 'oublog');

            /// Adding the "individual" field
            $options = array(OUBLOG_NO_INDIVIDUAL_BLOGS       => get_string('no_blogtogetheroringroups', 'oublog'),
                             OUBLOG_SEPARATE_INDIVIDUAL_BLOGS => get_string('separateindividualblogs', 'oublog'),
                             OUBLOG_VISIBLE_INDIVIDUAL_BLOGS  => get_string('visibleindividualblogs', 'oublog'));
            $mform->addElement('select', 'individual', get_string('individualblogs', 'oublog'), $options);
            $mform->setType('individual', PARAM_INT);
            $mform->setDefault('individual', OUBLOG_NO_INDIVIDUAL_BLOGS);
            $mform->addHelpButton('individual', 'individualblogs', 'oublog');

            //disable "maxvisibility" field when "individual" field is set (not default)
            $mform->disabledIf('maxvisibility', 'individual', OUBLOG_NO_INDIVIDUAL_BLOGS, OUBLOG_NO_INDIVIDUAL_BLOGS);

       /// Adding the "maxvisibility" field
            $options = array(OUBLOG_VISIBILITY_COURSEUSER   => get_string('visiblecourseusers', 'oublog'),
                             OUBLOG_VISIBILITY_LOGGEDINUSER => get_string('visibleloggedinusers', 'oublog'),
                             OUBLOG_VISIBILITY_PUBLIC       => get_string('visiblepublic', 'oublog'));

            $mform->addElement('select', 'maxvisibility', get_string('maxvisibility', 'oublog'), $options);
            $mform->setType('maxvisibility', PARAM_INT);
            $mform->addHelpButton('maxvisibility', 'maxvisibility', 'oublog');


    //-------------------------------------------------------------------------------
            // add standard elements, common to all modules
            $features = new stdClass;
            $features->groupings = true;
            $features->groupmembersonly = true;
            $this->standard_coursemodule_elements($features);
    //-------------------------------------------------------------------------------
        } else {
            $mform->addElement('hidden', 'instance');
            $mform->setType('instance', PARAM_INT);
        }

        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }

    function add_completion_rules() {
        $mform =& $this->_form;

        $group=array();
        $group[] =& $mform->createElement('checkbox', 'completionpostsenabled', ' ', get_string('completionposts','oublog'));
        $group[] =& $mform->createElement('text', 'completionposts', ' ', array('size'=>3));
        $mform->setType('completionposts',PARAM_INT);
        $mform->addGroup($group, 'completionpostsgroup', get_string('completionpostsgroup','oublog'), array(' '), false);
        $mform->addHelpButton('completionpostsgroup', 'completionpostsgroup', 'oublog');
        $mform->disabledIf('completionposts','completionpostsenabled','notchecked');

        $group=array();
        $group[] =& $mform->createElement('checkbox', 'completioncommentsenabled', ' ', get_string('completioncomments','oublog'));
        $group[] =& $mform->createElement('text', 'completioncomments', ' ', array('size'=>3));
        $mform->setType('completioncomments',PARAM_INT);
        $mform->addGroup($group, 'completioncommentsgroup', get_string('completioncommentsgroup','oublog'), array(' '), false);
        $mform->addHelpButton('completioncommentsgroup', 'completioncommentsgroup', 'oublog');
        $mform->disabledIf('completioncomments','completioncommentsenabled','notchecked');

        return array('completionpostsgroup','completioncommentsgroup');
    }

    function completion_rule_enabled($data) {
        return ((!empty($data['completionpostsenabled']) && $data['completionposts']!=0)) ||
            ((!empty($data['completioncommentsenabled']) && $data['completioncomments']!=0));
    }

    function get_data() {
        $data=parent::get_data();
        if(!$data) {
            return false;
        }
        // Turn off completion settings if the checkboxes aren't ticked
        $autocompletion=!empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;
        if(empty($data->completionpostsenabled) || !$autocompletion) {
            $data->completionposts=0;
        }
        if(empty($data->completioncommentsenabled) || !$autocompletion) {
            $data->completioncomments=0;
        }
        return $data;
    }

    function data_preprocessing(&$default_values){
        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $default_values['completionpostsenabled']=
            !empty($default_values['completionposts']) ? 1 : 0;
        if(empty($default_values['completionposts'])) {
            $default_values['completionposts']=1;
        }
        $default_values['completioncommentsenabled']=
            !empty($default_values['completioncomments']) ? 1 : 0;
        if(empty($default_values['completioncomments'])) {
            $default_values['completioncomments']=1;
        }
    }

    function validation($data, $files) {
        // $errors = array();
        $errors = parent::validation($data, $files);
        if (!empty($data['groupmode']) && isset($data['allowcomments']) &&
                $data['allowcomments'] == OUBLOG_COMMENTS_ALLOWPUBLIC) {
            $errors['allowcomments'] = get_string('error_grouppubliccomments', 'oublog');
        }
        return $errors;
    }
}
