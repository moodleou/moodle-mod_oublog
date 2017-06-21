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
 * Search area for mod_oublog activities.
 *
 * @package mod_oublog
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_oublog\search;

defined('MOODLE_INTERNAL') || die();

/**
 * Search area for mod_oublog activities.
 *
 * @package mod_oublog
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity extends \core_search\base_activity {

    /**
     * File area related to document
     */
    const FILEAREA = [
        'INTRO_AREA' => 'intro'
    ];

    /**
     * Returns the document associated with this activity.
     *
     * Overwriting base_activity method as page contents field is required,
     * description field is not.
     *
     * @param \stdClass $record
     * @param array $options
     * @return \core_search\document
     */
    public function get_document($record, $options = array()) {
        try {
            $cm = $this->get_cm($this->get_module_name(), $record->id, $record->course);
            $context = \context_module::instance($cm->id);
        } catch (\dml_missing_record_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->id .
                    ' document, not all required data is available: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        } catch (\dml_exception $ex) {
            // Notify it as we run here as admin, we should see everything.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->id . ' document: ' . $ex->getMessage(),
                    DEBUG_DEVELOPER);
            return false;
        }

        // Prepare associative array with data from DB.
        $doc = \core_search\document_factory::instance($record->id, $this->componentname, $this->areaname);
        $doc->set('title', content_to_text($record->name, false));

        $intro = file_rewrite_pluginfile_urls($record->intro, 'pluginfile.php', $context->id,
            $this->componentname, self::FILEAREA['INTRO_AREA'], null);
        $intro = content_to_text($intro, $record->introformat);

        $doc->set('content', $intro);

        $doc->set('contextid', $context->id);
        $doc->set('courseid', $record->course);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        $doc->set('modified', $record->timemodified);

        return $doc;
    }

    /**
     * Returns true if this area uses file indexing.
     *
     * @return bool
     */
    public function uses_file_indexing() {
        return true;
    }

    /**
     * Add the attached description files.
     *
     * @param \core_search\document $document The current document
     * @return null
     */
    public function attach_files($document) {
        $fs = get_file_storage();
        $files = array();

        foreach (self::FILEAREA as $area) {
            $files = array_merge($files, $fs->get_area_files($document->get('contextid'), $this->componentname, $area,
                    0, 'sortorder DESC, id ASC', false));
        }

        foreach ($files as $file) {
            $document->add_stored_file($file);
        }
    }
}
