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

namespace mod_oublog;

/**
 * Hook callbacks.
 *
 * @package mod_oublog
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Called when the system wants to find out if an activity is searchable, to decide whether to
     * display a search box in the header.
     *
     * @param \local_moodleglobalsearch\hook\activity_search_info $hook
     */
    public static function activity_search_info(\local_moodleglobalsearch\hook\activity_search_info $hook) {
        if ($hook->is_page('mod-oublog-view', 'mod-oublog-viewpost', 'mod-oublog-allposts')) {
            // This is a total hack, but I don't want to waste extra effort retrieving the data
            // that we already got.
            global $oublog;
            if ($oublog) {
                $strblogsearch = get_string('searchthisblog', 'oublog', oublog_get_displayname($oublog));
                $hook->enable_search($strblogsearch);
            }
        }
    }

}
