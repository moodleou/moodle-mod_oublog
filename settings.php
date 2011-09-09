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
