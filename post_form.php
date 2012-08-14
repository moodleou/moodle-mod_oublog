<?php

require_once($CFG->libdir.'/formslib.php');

class mod_oublog_post_form extends moodleform {

    function definition() {

        global $CFG;

        $individualblog = $this->_customdata['individual'];
        $maxvisibility = $this->_customdata['maxvisibility'];
        $allowcomments = $this->_customdata['allowcomments'];
        $edit          = $this->_customdata['edit'];
        $personal      = $this->_customdata['personal'];
        $maxbytes      = $this->_customdata['maxbytes'];

        $mform    =& $this->_form;


        $mform->addElement('header', 'general', '');

        $mform->addElement('text', 'title', get_string('title', 'oublog'), 'size="48"');
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('editor', 'message', get_string('message', 'oublog'), array('cols'=>50, 'rows'=>30),array('maxfiles' => EDITOR_UNLIMITED_FILES));
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

        $mform->addElement('textarea', 'tags', get_string('tagsfield', 'oublog'), array('cols'=>48, 'rows'=>2));
        $mform->setType('tags', PARAM_TAGLIST);
        $mform->addHelpButton('tags', 'tags', 'oublog');

        $options = array();
        if ($allowcomments) {
            $options[OUBLOG_COMMENTS_ALLOW] = get_string('logincomments', 'oublog');
            if ($allowcomments >= OUBLOG_COMMENTS_ALLOWPUBLIC
                && OUBLOG_VISIBILITY_PUBLIC <= $maxvisibility) {
                $maybepubliccomments = true;
                $options[OUBLOG_COMMENTS_ALLOWPUBLIC] = get_string('publiccomments', 'oublog');
            }
            $options[OUBLOG_COMMENTS_PREVENT] = get_string('no', 'oublog');

            $mform->addElement('select', 'allowcomments', get_string('allowcomments', 'oublog'), $options);
            $mform->setType('allowcomments', PARAM_INT);
            $mform->addHelpButton('allowcomments', 'allowcomments', 'oublog');

            if (isset($maybepubliccomments)) {
                // NOTE - oublog.js adds a listener to allowcomments that hides/shows this element as mforms doesn't support this.
                $mform->addElement('static', 'publicwarning', '', '<div id="publicwarningmarker"></div>'. get_string('publiccomments_info', 'oublog'));
            }
        } else {
            $mform->addElement('hidden', 'allowcomments', OUBLOG_COMMENTS_PREVENT);
            $mform->setType('allowcomments', PARAM_INT);
        }

        $options = array();
        if (OUBLOG_VISIBILITY_COURSEUSER <= $maxvisibility) {
            $options[OUBLOG_VISIBILITY_COURSEUSER] = oublog_get_visibility_string(OUBLOG_VISIBILITY_COURSEUSER,$personal);
        }
        if (OUBLOG_VISIBILITY_LOGGEDINUSER <= $maxvisibility) {
            $options[OUBLOG_VISIBILITY_LOGGEDINUSER] = oublog_get_visibility_string(OUBLOG_VISIBILITY_LOGGEDINUSER,$personal);
        }
        if (OUBLOG_VISIBILITY_PUBLIC <= $maxvisibility) {
            $options[OUBLOG_VISIBILITY_PUBLIC] = oublog_get_visibility_string(OUBLOG_VISIBILITY_PUBLIC,$personal);
        }
        if ($individualblog > OUBLOG_NO_INDIVIDUAL_BLOGS) {
            $mform->addElement('hidden', 'visibility', OUBLOG_VISIBILITY_COURSEUSER);
            $mform->setType('visibility', PARAM_INT);
        }elseif (OUBLOG_VISIBILITY_COURSEUSER != $maxvisibility) {
            $mform->addElement('select', 'visibility', get_string('visibility', 'oublog'), $options);
            $mform->setType('visibility', PARAM_INT);
            $mform->addHelpButton('visibility', 'visibility', 'oublog');
        } else {
            $mform->addElement('hidden', 'visibility', OUBLOG_VISIBILITY_COURSEUSER);
            $mform->setType('visibility', PARAM_INT);
        }

        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'oublog'), null,
                array('subdirs' => 0, 'maxbytes' => $maxbytes));
        $mform->addHelpButton('attachments', 'attachments', 'oublog');

        if ($edit) {
            $submitstring = get_string('savechanges');
        } else {
            $submitstring = get_string('addpost', 'oublog');
        }

        $this->add_action_buttons(true, $submitstring);

    /// Hidden form vars
        $mform->addElement('hidden', 'blog');
        $mform->setType('blog', PARAM_INT);

        $mform->addElement('hidden', 'post');
        $mform->setType('postid', PARAM_INT);

    }
}
