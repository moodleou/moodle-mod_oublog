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
 * The mod_oublog comment approved event.
 *
 * @package    mod_oublog
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_oublog\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_oublog comment approved event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int blogid: The oublog the post is part of.
 *
 * }
 *
 * @package    mod_oublog
 * @since      Moodle 2.7
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_approved extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'oublog_comments';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has approved the moderated comment
            '{$this->other['mcommentid']}' upon post with id '{$this->other['postid']}'
            to be added with id '$this->objectid' to the oublog with the course module id
            '$this->contextinstanceid'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:commentapproved', 'mod_oublog');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        $url = new \moodle_url('/mod/oublog/viewpost.php', array('post' => $this->other['postid']));
        $url->set_anchor('cid' . $this->objectid);
        return $url;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'commentid\' value must be set in the object.');
        }

        if (!isset($this->other['postid'])) {
            throw new \coding_exception('The \'postid\' value must be set in other.');
        }

        if (!isset($this->other['mcommentid'])) {
            throw new \coding_exception('The \'mcommentid\' value must be set in other.');
        }

        if (!isset($this->other['oublogid'])) {
            throw new \coding_exception('The \'oublogid\' value must be set in other.');
        }

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}
