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

// Dodgy hack to setup the global blog instance (section not created yet on install).
if (!isset($CFG->oublogsetup)) {
    if ($pbcm = get_coursemodule_from_instance('oublog', 1 , SITEID, 1)) {
        $mod = new stdClass();
        $mod->id= $pbcm->id;
        $mod->section = course_add_cm_to_section($pbcm->course, $pbcm->id, 1);
        $DB->update_record('course_modules', $mod);
    }
    set_config('oublogsetup', true);
}

$plugin = new stdClass();
require($CFG->dirroot . '/mod/oublog/version.php');
$settings->add(new admin_setting_heading('oublog_version', '',
    get_string('displayversion', 'oublog', $plugin->release)));

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

$settings->add(new admin_setting_configcheckbox('oublogallpostslogin',
        get_string('oublogallpostslogin', 'oublog'), get_string('oublogallpostslogin_desc', 'oublog'), 1));

$settings->add(new admin_setting_configtext('mod_oublog/globalusageexclude',
        get_string('globalusageexclude', 'oublog'), get_string('globalusageexclude_desc', 'oublog'), ''));

$settings->add(new admin_setting_configtext('mod_oublog/remoteserver',
        get_string('remoteserver', 'oublog'),
        get_string('configremoteserver', 'oublog'), '', PARAM_URL));
$settings->add(new admin_setting_configtext('mod_oublog/remotetoken',
        get_string('remotetoken', 'oublog'),
        get_string('configremotetoken', 'oublog'), '', PARAM_ALPHANUM));
