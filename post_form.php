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

require_once($CFG->libdir.'/formslib.php');

class mod_oublog_post_form extends moodleform {

    private $restricttags = 0;
    private $requiretags = 0;
    private $availtags = array();

    public function definition() {

        global $CFG;

        $individualblog = $this->_customdata['individual'];
        $maxvisibility = $this->_customdata['maxvisibility'];
        $allowcomments = $this->_customdata['allowcomments'];
        $edit          = $this->_customdata['edit'];
        $personal      = $this->_customdata['personal'];
        $maxbytes      = $this->_customdata['maxbytes'];
        $maxattachments = $this->_customdata['maxattachments'];
        $tagslist = $this->_customdata['tagslist'];
        $referurl = $this->_customdata['referurl'];
        $cmid = $this->_customdata['cmid'];
        $this->restricttags = false;
        $this->requiretags = false;

        if ($this->_customdata['restricttags'] == 1 || $this->_customdata['restricttags'] == 3) {
            $this->restricttags = true;
        }
        if ($this->_customdata['restricttags'] == 2 || $this->_customdata['restricttags'] == 3) {
            $this->requiretags = true;
        }
        $atags = array();
        // Get list of tags from 'availtags' customdata.
        if ($this->_customdata['availtags'] != false && $this->restricttags == true) {
            $this->availtags = $this->_customdata['availtags'];
            foreach ($this->availtags as $tag) {
                $atags[] = $tag->tag;
            }
        }

        $mform    =& $this->_form;

        $mform->addElement('header', 'general', '');

        $mform->addElement('text', 'title', get_string('title', 'oublog'), 'size="48"');
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('editor', 'message', get_string('message', 'oublog'),
                array('cols' => 50, 'rows' => 30),
                array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $maxbytes));
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

        if ($this->restricttags) {
            $mform->addElement('static', 'restricttagswarning', '', get_string('restricttagslist', 'oublog', implode(',', $atags)));
        }

        $mform->addElement('textarea', 'tags', get_string('tagsfield', 'oublog'), array('cols'=>48, 'rows'=>2));
        $mform->setType('tags', PARAM_TAGLIST);
        $mform->addHelpButton('tags', 'tags', 'oublog');
        if ($this->_customdata['restricttags'] == 4) {
            $mform->setDefault('tags', $tagslist);
        }
        if ($this->requiretags) {
            $mform->addRule('tags', get_string('required'), 'required', null, 'client');
        }

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
                // NOTE - module.js adds a listener to allowcomments that hides/shows this element as mforms doesn't support this.
                $mform->addElement('static', 'publicwarning', '', '<div id="publicwarningmarker"></div>'. get_string('publiccomments_info', 'oublog'));
            }
        } else {
            $mform->addElement('hidden', 'allowcomments', OUBLOG_COMMENTS_PREVENT);
            $mform->setType('allowcomments', PARAM_INT);
        }

        $options = array();
        if (OUBLOG_VISIBILITY_COURSEUSER <= $maxvisibility) {
            $options[OUBLOG_VISIBILITY_COURSEUSER] = oublog_get_visibility_string(OUBLOG_VISIBILITY_COURSEUSER, $personal);
        }
        if (OUBLOG_VISIBILITY_LOGGEDINUSER <= $maxvisibility) {
            $options[OUBLOG_VISIBILITY_LOGGEDINUSER] = oublog_get_visibility_string(OUBLOG_VISIBILITY_LOGGEDINUSER, $personal);
        }
        if (OUBLOG_VISIBILITY_PUBLIC <= $maxvisibility) {
            $options[OUBLOG_VISIBILITY_PUBLIC] = oublog_get_visibility_string(OUBLOG_VISIBILITY_PUBLIC, $personal);
        }
        if ($individualblog > OUBLOG_NO_INDIVIDUAL_BLOGS) {
            $mform->addElement('hidden', 'visibility', OUBLOG_VISIBILITY_COURSEUSER);
            $mform->setType('visibility', PARAM_INT);
        } else if (OUBLOG_VISIBILITY_COURSEUSER != $maxvisibility) {
            $mform->addElement('select', 'visibility', get_string('visibility', 'oublog'), $options);
            $mform->setType('visibility', PARAM_INT);
            $mform->addHelpButton('visibility', 'visibility', 'oublog');
        } else {
            $mform->addElement('hidden', 'visibility', OUBLOG_VISIBILITY_COURSEUSER);
            $mform->setType('visibility', PARAM_INT);
        }
        if ($maxattachments > 0) {
            $mform->addElement('filemanager', 'attachments', get_string('attachments', 'oublog'), null,
                    array('subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => $maxattachments));
            $mform->addHelpButton('attachments', 'attachments', 'oublog');
        }

        if ($edit) {
            $submitstring = get_string('savechanges');
        } else {
            $submitstring = get_string('addpost', 'oublog');
        }

        $this->add_action_buttons(true, $submitstring);

        // Hidden form vars.
        $mform->addElement('hidden', 'blog');
        $mform->setType('blog', PARAM_INT);

        $mform->addElement('hidden', 'post');
        $mform->setType('post', PARAM_INT);

        $mform->addElement('hidden', 'referurl', $referurl);
        $mform->setType('referurl', PARAM_LOCALURL);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $testtags = 0;
        $atags = $formtags = array();
        if ($this->restricttags) {
            foreach ($this->availtags as $tag) {
                $atags[] = $tag->tag;
            }
            if (!empty($data['tags'])) {
                $formtags = explode(",", $data['tags']);
                $testtags = count(array_diff($formtags, $atags));
            }
        }
        if ($this->restricttags && $testtags > 0) {
            $errors['tags'] = get_string('restricttagsvalidation', 'oublog');
        }
        return $errors;
    }
}
