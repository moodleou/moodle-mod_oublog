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
 * Search area class for document comments
 *
 * @package mod_oublog
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_oublog\search;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/oublog/locallib.php');

/**
 * Search area class for document comments
 *
 * @package mod_oublog
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comments extends \core_search\base_mod {

    /**
     * File area relate to Moodle file table.
     */
    const FILEAREA = [
        'MESSAGE' => 'messagecomment'
    ];

    /** @var array Relevant context levels (module context) */
    protected static $levels = [CONTEXT_MODULE];

    /**
     * Calls get_document_recordset which returns required data for indexing blog post comments.
     *
     * @param int $modifiedfrom
     * @return \moodle_recordset|null
     */
    public function get_recordset_by_timestamp($modifiedfrom = 0) {
        return $this->get_document_recordset($modifiedfrom);
    }

    /**
     * Returns recordset containing required data for indexing comments of posts.
     *
     * @param int $modifiedfrom
     * @param \context|null $context
     * @return \moodle_recordset|null
     */
    public function get_document_recordset($modifiedfrom = 0, \context $context = null) {
        global $DB;

        list ($contextjoin, $contextparams) = $this->get_context_restriction_sql(
                $context, 'oublog', 'ob');
        if ($contextjoin === null) {
            return null;
        }

        $sql = "SELECT ob.id, ob.course, op.id as postid,
                   obcm.id as commentid, obcm.postid, obcm.userid, obcm.title,
                   obcm.message, obcm.timeposted, obcm.deletedby, obcm.timedeleted,
                   obcm.authorname, obcm.authorip, obcm.timeapproved
                  FROM {oublog_comments} obcm
                  JOIN {oublog_posts} op ON op.id = obcm.postid
                  JOIN {oublog_instances} oi ON oi.id = op.oubloginstancesid
                  JOIN {oublog} ob ON ob.id = oi.oublogid
          $contextjoin
                 WHERE obcm.timeposted >= ?
                    AND obcm.timedeleted IS NULL
                    AND op.timedeleted IS NULL
              ORDER BY obcm.timeposted ASC";
        return $DB->get_recordset_sql($sql, array_merge($contextparams, [$modifiedfrom]));
    }

    /**
     * Returns the document associated with this comment id.
     *
     * @param \stdClass $record
     * @param array $options
     * @return bool|\core_search\document
     */
    public function get_document($record, $options = array()) {

        try {
            $cm = get_coursemodule_from_instance($this->get_module_name(), $record->id, $record->course);
            $context = \context_module::instance($cm->id);
        } catch (\dml_exception $ex) {
            // Don't throw an exception, apparently it might upset the search process.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->oublogid .
                    ' document: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // Construct the document instance to return.
        $doc = \core_search\document_factory::instance(
                $record->commentid, $this->componentname, $this->areaname);

        // Set document title.
        // Document title will be comment title.
        $title = $record->title;
        $doc->set('title', content_to_text($title, false));

        // Set document content.
        $content = $record->message;
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, $this->componentname,
                self::FILEAREA['MESSAGE'], $record->commentid);
        $doc->set('content', content_to_text($content, FORMAT_HTML));

        // Set other search metadata.
        $doc->set('contextid', $context->id);
        $doc->set('type', \core_search\manager::TYPE_TEXT);
        $doc->set('courseid', $record->course);

        $doc->set('modified', $record->timeposted);
        $doc->set('itemid', $record->commentid);

        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);
        if (!empty($record->userid)) {
            $doc->set('userid', $record->userid);
        }

        // Set optional 'new' flag.
        if (isset($options['lastindexedtime']) && ($options['lastindexedtime'] < $record->timeposted)) {
            // If the document was created after the last index time, it must be new.
            $doc->set_is_new(true);
        }

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @param int $id Comment ID
     * @return int
     */
    public function check_access($id) {
        global $USER, $DB;
        // Get post instance and oublogid instance in an unique record.
        $comment = $DB->get_record('oublog_comments', array('id' => $id));
        $postinstance = null;
        if ($comment) {
            $postinstance = \oublog_get_post($comment->postid, 0);
        }
        if ($postinstance) {
            $oublog = \oublog_get_blog_from_postid($comment->postid);
            $cm = \get_coursemodule_from_instance($this->get_module_name(), $oublog->id,
                  $oublog->course);
            $context = \context_module::instance($cm->id);
            if ($postinstance->timedeleted && $postinstance->deletedby) {
                // This activity instance was deleted.
                return \core_search\manager::ACCESS_DELETED;
            }

            // Determine if a user can view a post.
            if (! \oublog_can_view_post($postinstance, $USER, $context, $cm, $oublog)) {
                return \core_search\manager::ACCESS_DENIED;
            }

            return \core_search\manager::ACCESS_GRANTED;
        }
        return \core_search\manager::ACCESS_DELETED;
    }

    /**
     * Link to the oublog viewpost page
     *
     * @param \core_search\document $doc Document instance returned by get_document function
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        global $DB;
        $comment = $DB->get_record('oublog_comments', array('id' => $doc->get('itemid')));
        $post = \oublog_get_post($comment->postid, 0);
        return new \moodle_url('/mod/oublog/viewpost.php', array('post' => $post->id), 'cid' . $comment->id);
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
                    $document->get('itemid'), 'sortorder DESC, id ASC'));
        }

        foreach ($files as $file) {
            $document->add_stored_file($file);
        }
    }

    /**
     * Link to the oublog viewpost page
     *
     * @param \core_search\document $doc Document instance returned by get_document function
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        return $this->get_doc_url($doc);
    }

    /**
     * Returns the module name.
     *
     * @return string
     */
    protected function get_module_name() {
        return substr($this->componentname, 4);
    }
}
