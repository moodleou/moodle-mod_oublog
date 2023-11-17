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
 * A scheduled task for oublog settings page.
 *
 * @package    mod_oublog
 * @copyright  2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_oublog\task;

class settings_task extends \core\task\adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('oublogsettingstask', 'mod_oublog');
    }

    public function execute() {
        global $DB;

        $pbcm = get_coursemodule_from_instance('oublog', 1 , SITEID, 1);
        $mod = new \stdClass();
        $mod->id= $pbcm->id;
        $mod->section = course_add_cm_to_section($pbcm->course, $pbcm->id, 1);
        $DB->update_record('course_modules', $mod);
    }
}