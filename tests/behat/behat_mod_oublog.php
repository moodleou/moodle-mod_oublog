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
 * Steps definitions related with the oublog activity.
 *
 * @package mod_oublog
 * @category test
 * @copyright 2015 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * oublog-related steps definitions.
 *
 * @package    mod_oublog
 * @category   test
 * @copyright  2015 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_oublog extends behat_base {

    /**
     * Simulates a user adding a personal oublog username to a URL entered into their browser address bar.
     *
     * @Given /^I visit the personal blog for "(?P<user_string>(?:[^"]|\\")*)"$/
     * @param string $user the user name
     */
    public function i_visit_the_personal_blog_for($user) {
        global $CFG;
        $this->getSession()->visit($CFG->wwwroot .'/mod/oublog/view.php?u='. $user);
    }
}
