<?php

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
