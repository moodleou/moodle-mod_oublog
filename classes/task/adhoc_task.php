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

namespace mod_oublog\task;

defined('MOODLE_INTERNAL') || die();
/**
 * Class adhoc_task
 *
 * @package    mod_oublog
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adhoc_task extends \core\task\adhoc_task {
    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;
        if (!isset($CFG->oublogsetup)) {
            if ($pbcm = get_coursemodule_from_instance('oublog', 1 , SITEID, 1)) {
                $mod = new \stdClass();
                $mod->id= $pbcm->id;
                $mod->section = course_add_cm_to_section((int) $pbcm->course, (int) $pbcm->id, 1);
                $DB->update_record('course_modules', $mod);
            }
            set_config('oublogsetup', true);
        }
    }
}
