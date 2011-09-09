<?php

require_once($CFG->libdir.'/formslib.php');

class mod_oublog_link_form extends moodleform {

    function definition() {

        global $CFG;

        $edit = $this->_customdata['edit'];

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

    /// Hidden form vars
        $mform->addElement('hidden', 'blog');
        $mform->setType('blog', PARAM_INT);

        $mform->addElement('hidden', 'bloginstance');
        $mform->setType('bloginstance', PARAM_INT);

        $mform->addElement('hidden', 'link');
        $mform->setType('link', PARAM_INT);

    }
}