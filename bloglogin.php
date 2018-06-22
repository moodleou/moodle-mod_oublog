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
require_once('../../config.php');
global $CFG;
// This script requires login so users have a chance to log into (a) blogs
// that don't let you see anything without a login, (b) blogs that might let
// you see more with a login

$returnurl = optional_param('returnurl', $CFG->wwwroot . '/mod/oublog/view.php', PARAM_RAW);
// Security check on URL, allow redirect to only php scripts in blog folder
if (!strpos($returnurl, $CFG->wwwroot . '/mod/oublog/') === 0) {
    $returnurl='';
}

if ($CFG->autologinguests) {
    $SESSION->wantsurl = $returnurl;
    redirect($CFG->wwwroot.'/login/');
} else {
    require_login();

    // Default returns to blog default view (which will automatically jump to user
    // now they are logged in)
    redirect($returnurl);
}
