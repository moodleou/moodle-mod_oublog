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

class mod_oublog_comment_form extends moodleform {

    public function definition() {

        global $CFG;

        $maxvisibility = $this->_customdata['maxvisibility'];
        $edit = $this->_customdata['edit'];
        $moderated = $this->_customdata['moderated'];
        $confirmed = $this->_customdata['confirmed'];
        $blogid = $this->_customdata['blogid'];
        $postid = $this->_customdata['postid'];
        $maxbytes = $this->_customdata['maxbytes'];
        $postrender = $this->_customdata['postrender'];
        $referurl =  $this->_customdata['referurl'];
        $cmid = $this->_customdata['cmid'];
        $mform    =& $this->_form;

        if (!$edit) {
            $mform->addElement('header', 'posttext', get_string('postmessage', 'oublog'));
            $mform->setExpanded('posttext', false);
            $mform->addElement('html', $postrender);
        }

        $mform->addElement('header', 'general', '');
        $mform->setExpanded('general', true);

        if ($moderated) {
            $mform->addElement('static', '', '',
                    get_string('moderated_info', 'oublog', $CFG->wwwroot .
                        '/mod/oublog/bloglogin.php?returnurl=editcomment.php?blog=' .
                        $blogid . '%26post=' . $postid));

            $mform->addElement('text', 'authorname',
                    get_string('moderated_authorname', 'oublog'), 'size="48"');
            $mform->setType('authorname', PARAM_TEXT);
            $mform->addRule('authorname', get_string('required'), 'required', null, 'client');
        }

        $mform->addElement('text', 'title', get_string('title', 'oublog'), 'size="48"');
        $mform->setType('title', PARAM_TEXT);

        $messagetype = 'editor';
        if ($moderated) {
            $messagetype = 'textarea';
        }

        $mform->addElement($messagetype, 'messagecomment', get_string('comment', 'oublog'),
                array('cols' => 50, 'rows' => 30),
                array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $maxbytes));
        $mform->setType('messagecomment', PARAM_CLEANHTML);
        $mform->addRule('messagecomment', get_string('required'), 'required', null, 'server');

        // When using moderation, we include the world's dumbest capcha (the
        // user is told to type 'yes' into the box). Because there is moderation
        // we do not really need a capcha; this is only meant to exclude the
        // stupidest spam robots and reduce the quantity of email sent to
        // moderators. A cookie can skip this step.
        if ($moderated && !$confirmed) {
            $mform->addElement('static', '', '',
                    get_string('moderated_confirminfo', 'oublog'));
            $mform->addElement('text', 'confirm', get_string('moderated_confirm', 'oublog'));
            $mform->setType('confirm', PARAM_TEXT);
        }

        if ($edit) {
            $submitstring = get_string('savechanges');
        } else {
            $submitstring = get_string('addcomment', 'oublog');
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
        $moderated = $this->_customdata['moderated'];
        $confirmed = $this->_customdata['confirmed'];

        $errors = array();
        if ($moderated && !$confirmed && (empty($data['confirm']) ||
                $data['confirm'] !== get_string('moderated_confirmvalue', 'oublog'))) {
            $errors['confirm'] = get_string('error_noconfirm', 'oublog');
        }
        return $errors;
    }
}
