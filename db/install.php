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

/**
 * Post installation procedure.
 *
 * @return bool True on success
 */
function xmldb_oublog_install() {
    global $CFG;

    require_once($CFG->dirroot . '/mod/oublog/lib.php');

    if (PHPUNIT_TEST) {
        // Unit tests expect a site with no activities on it, so they create the global blog
        // themselves when they need one. See mod_oublog_generator::create_global_blog.
        return true;
    }

    // Setup the global blog.
    return oublog_create_global_blog() ? true : false;
}
