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

/**
 * Form for sending an email to the author of a post when deleting
 * @package mod
 * @subpackage oublog
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_oublog_deletepost_form extends moodleform {

    public function definition() {
        $mform =& $this->_form;
        $cmid = $this->_customdata->cmid;

        // Header.
        $mform->addElement('header', 'general', get_string('deleteandemail', 'oublog'));

        // Message box.
        $mform->addElement('editor', 'message',
                get_string('emailmessage', 'oublog'), array('size'=>'64', 'id' => 'id_oublog_delete_msg'));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', null, 'required', null, 'client');

        // Send a copy to self.
        $mform->addElement('checkbox', 'copyself', get_string('copytoself', 'oublog'));

        // Adding optional text field 'Email address of other recipients'.
        $mform->addElement('text', 'emailadd', get_string('extra_emails', 'oublog'),
                array('size' => '48'));
        $mform->addHelpButton('emailadd', 'extra_emails', 'oublog');
        $mform->setType('emailadd', PARAM_RAW);

        // Include a copy of the post.
        $mform->addElement('checkbox', 'includepost', get_string('includepost', 'oublog'));

        // Hidden fields for return url.
        $mform->addElement('hidden', 'blog', $this->_customdata->blog);
        $mform->setType('blog', PARAM_INT);

        $mform->addElement('hidden', 'post', $this->_customdata->post);
        $mform->setType('post', PARAM_INT);

        $mform->addElement('hidden', 'email', $this->_customdata->email);
        $mform->setType('email', PARAM_INT);

        $mform->addElement('hidden', 'delete', $this->_customdata->delete);
        $mform->setType('delete', PARAM_INT);

        $mform->addElement('hidden', 'referurl', $this->_customdata->referurl);
        $mform->setType('referurl', PARAM_LOCALURL);

        $mform->addElement('hidden', 'confirm', 1);
        $mform->setType('confirm', PARAM_INT);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);
        // Add some buttons.
        $this->add_action_buttons(true, get_string('sendanddelete', 'oublog'));

    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['emailadd'])) {
            $emails = preg_split('~[; ]+~', $data['emailadd']);
            if (count($emails) < 1) {
                $errors['emailadd'] = get_string('invalidemails', 'oublog');
            } else {
                foreach ($emails as $email) {
                    if (!validate_email($email)) {
                        $errors['emailadd'] = get_string('invalidemails', 'oublog');
                        break;
                    }
                }
            }
        }
        return $errors;
    }

}
