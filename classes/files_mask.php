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
 * Implementation of file data masking for this plugin.
 *
 * The corresponding test script tool_datamasking_test.php checks every masked field.
 *
 * @package mod_oublog
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class files_mask extends \tool_datamasking\files_mask {
    protected function replace_filename(\stdClass $rec, string $newfilename): void {
        global $DB;
        if ($rec->filearea == 'message') {
            // Update posts.
            $sql = "UPDATE {oublog_posts}
                       SET message = REPLACE(message, ?, ?)
                     WHERE id = ?";
            $DB->execute($sql, [$rec->filename, $newfilename, $rec->itemid]);
            if ($rec->filename != rawurlencode($rec->filename)) {
                $DB->execute($sql, [rawurlencode($rec->filename), rawurlencode($newfilename), $rec->itemid]);
            }
            // Update edits.
            $sql = "UPDATE {oublog_edits}
                       SET oldmessage = REPLACE(oldmessage, ?, ?)
                     WHERE postid = ?";
            $DB->execute($sql, [$rec->filename, $newfilename, $rec->itemid]);
            if ($rec->filename != rawurlencode($rec->filename)) {
                $DB->execute($sql, [rawurlencode($rec->filename), rawurlencode($newfilename), $rec->itemid]);
            }
        }
    }
}
