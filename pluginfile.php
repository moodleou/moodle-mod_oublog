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

/**
 * A script to serve files from OU Blog client ONLY
 * Used so can apply restrictions on core pluginfile, but leave this open to world
 *
 * @package    mod
 * @subpackage oublog
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/lib/filelib.php');

$relativepath = get_file_argument();

// Relative path must start with '/'.
if (!$relativepath) {
    print_error('invalidargorconf');
} else if ($relativepath{0} != '/') {
    print_error('pathdoesnotstartslash');
}

// Extract relative path components.
$args = explode('/', ltrim($relativepath, '/'));

if (count($args) == 0) { // Always at least user id.
    print_error('invalidarguments');
}
$contextid = (int)array_shift($args);
$component = array_shift($args);
$filearea = array_shift($args);
$draftid = (int)array_shift($args);

if ($component !== 'mod_oublog' && ($filearea !== 'message' || $filearea !== 'attachment'
        || $filearea !== 'messagecomment' || $filearea !== 'summary')) {
    send_file_not_found();
}
// Following code must match root pluginfile.php (can't include, so must duplicate).
$forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);
$preview = optional_param('preview', null, PARAM_ALPHANUM);

file_pluginfile($relativepath, $forcedownload, $preview);
