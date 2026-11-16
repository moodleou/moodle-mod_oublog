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
 * Creates personal blog instance (on site front page) after install
 *
 * @package    mod
 * @subpackage oublog
 * @copyright  2013 The open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_oublog_install() {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/course/lib.php');

    // Setup the global blog.
    $oublog = new stdClass;
    $oublog->course = SITEID;
    $oublog->name = 'Personal Blogs';
    $oublog->intro = '';
    $oublog->introformat = FORMAT_HTML;
    $oublog->accesstoken = md5(uniqid(rand(), true));
    $oublog->maxvisibility = 300;// OUBLOG_VISIBILITY_PUBLIC.
    $oublog->global = 1;
    $oublog->allowcomments = 2;// OUBLOG_COMMENTS_ALLOWPUBLIC.
    $oublog->timemodified = time();
    if (!$oublog->id = $DB->insert_record('oublog', $oublog)) {
        return false;
    }

    $mod = new stdClass;
    $mod->course = SITEID;
    $mod->module = $DB->get_field('modules', 'id', array('name'=>'oublog'));
    $mod->instance = $oublog->id;
    $mod->visible = 1;
    $mod->visibleold = 0;
    $mod->section = 1;

    if (!$cm = add_course_module($mod)) {
        return true;
    }
    set_config('oublogsetup', null);

    // For unit tests to work, it's necessary to create context now.
    context_module::instance($cm);

    return true;
}
