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

class mod_oublog_link_form extends moodleform {

    public function definition() {

        global $CFG;
        $edit = $this->_customdata['edit'];
        $cmid = $this->_customdata['cmid'];

        $mform =& $this->_form;

        $mform->addElement('header', 'general', '');

        $mform->addElement('text', 'title', get_string('title', 'oublog'), 'size="48"');
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'url', get_string('url', 'oublog'), 'size="48"');
        $mform->setType('url', PARAM_URL);
        $mform->addRule('url', get_string('required'), 'required', null, 'client');

        if ($edit) {
            $submitstring = get_string('savechanges');
        } else {
            $submitstring = get_string('addlink', 'oublog');
        }

        $this->add_action_buttons(true, $submitstring);

        // Hidden form vars.
        $mform->addElement('hidden', 'blog');
        $mform->setType('blog', PARAM_INT);

        $mform->addElement('hidden', 'bloginstance');
        $mform->setType('bloginstance', PARAM_INT);

        $mform->addElement('hidden', 'link');
        $mform->setType('link', PARAM_INT);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

    }
}