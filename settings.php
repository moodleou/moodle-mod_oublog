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

// Dodgy hack to setup the global blog instance - see MDL-13808 for the proposed solution

include_once($CFG->dirroot.'/mod/oublog/lib.php');

if (!isset($CFG->oublogsetup)) {
    oublog_post_install();
}

$module = new stdClass;
require($CFG->dirroot . '/mod/oublog/version.php');
$settings->add(new admin_setting_heading('oublog_version', '',
    get_string('displayversion', 'oublog', $module->displayversion)));

if (isset($CFG->maxbytes)) {
    // Default maximum size for attachments allowed per post per oublog.
    $settings->add(new admin_setting_configselect('mod_oublog/maxbytes',
            get_string('maxattachmentsize', 'oublog'),
            get_string('configmaxbytes', 'oublog'), 512000, get_max_upload_sizes($CFG->maxbytes)));
}

// Default number of attachments allowed per post in all oublogs.
$settings->add(new admin_setting_configtext('mod_oublog/maxattachments',
        get_string('maxattachments', 'oublog'),
        get_string('configmaxattachments', 'oublog'), 9, PARAM_INT));
