<?php
require_once('../../config.php');
global $CFG;
// This script requires login so users have a chance to log into (a) blogs
// that don't let you see anything without a login, (b) blogs that might let
// you see more with a login

$returnurl=optional_param('returnurl','',PARAM_RAW);
// Security check on URL, allow redirect to only php scripts in blog folder
if(!strpos($returnurl, $CFG->wwwroot.'/mod/oublog/') === 0) {
    $returnurl='';
}

if ($CFG->autologinguests) {
    $_SESSION['wantsurl']=$returnurl;
    redirect($CFG->wwwroot.'/login/');
} else {
    require_login();

    // Default returns to blog default view (which will automatically jump to user
    // now they are logged in)
    if($returnurl) {
        redirect($returnurl);
    } else {
        redirect('view.php');
    }
}
