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
 * OUBLOG data generator
 *
 * @package    mod_oublog
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * oublog module data generator class
 *
 * @package    mod_oublog
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_oublog_generator extends testing_module_generator {

    private $modcount = 0;

    public function reset() {
        $this->modcount = 0;
        parent::reset();
    }

    public function create_instance($record = null, array $options = null) {
        global $CFG, $SITE;
        require_once($CFG->dirroot . '/mod/oublog/locallib.php');

        $record = (object)(array)$record;

        if (empty($record->course)) {
            mtrace('Called mod_oublog generator create_instance() without $record->course.');
            $record->course = $SITE;
        }

        if (!isset($record->maxvisibility) && !isset($options['maxvisibility'])) {
            $record->maxvisibility = OUBLOG_VISIBILITY_COURSEUSER;
        }
        if (!isset($record->allowcomments) && !isset($options['allowcomments'])) {
            $record->allowcomments = 1;
        }
        if (!isset($record->individual) && !isset($options['individual'])) {
            $record->individual = OUBLOG_NO_INDIVIDUAL_BLOGS;
        }
        $this->modcount++;

        if (!isset($record->name) && !isset($options['name'])) {
            $record->name = 'OUBLOG' . $this->modcount;
        } else if (isset($options['name'])) {
            $record->name = $options['name'];// Name must be in $record.
        }
        if (!isset($record->grade) && !isset($options['grade'])) {
            $record->grade = 0;
        }
        if (!isset($record->scale) && !isset($options['scale'])) {
            $record->scale = 0;
        }
        if (!isset($record->grading) && !isset($options['grading'])) {
            $record->grading = 0;
        }
        return parent::create_instance($record, (array)$options);
    }

    /**
     * Creates or edits a post.
     *
     * @param stdClass $oublog Blog instance
     * @param array|stdClass $record Information about post
     * @return int|bool Post id if adding one, or true/false if editing an existing one
     */
    public function create_post($oublog, $record) {
        return $this->create_content($oublog, ['post' => (object)$record]);
    }

    /**
     * Creates or edits a post.
     *
     * @param array|stdClass $record Information about post
     * @return int|bool Post id if adding one, or true/false if editing an existing one
     */
    public function create_behat_post($record) {
        return $this->create_content((object)['id' => $record['blog']], ['post' => (object)$record]);
    }

    /**
     * Creates a comment.
     *
     * @param stdClass $oublog Blog instance
     * @param array|stdClass $record Information about comment
     * @return int Post id
     */
    public function create_comment($oublog, $record) {
        return $this->create_content($oublog, ['comment' => (object)$record]);
    }

    /**
     * Creates a comment.
     *
     * @param array|stdClass $record Information about comment
     * @return int Post id
     */
    public function create_behat_comment($record) {
        return $this->create_content((object)['id' => $record['blog']], ['comment' => (object)$record]);
    }

    public function create_content($instance, $record = array()) {
        global $USER, $DB, $CFG;
        require_once($CFG->dirroot . '/mod/oublog/locallib.php');
        // Must set either 'post' object or 'comment' (object).
        // Returns id of post/comment created, or true/false for edit.

        $cm = get_coursemodule_from_instance('oublog', $instance->id);
        $context = context_module::instance($cm->id);
        $course = get_course($instance->course ?? $cm->course);

        // Default add a default post if nothing sent.
        if (!isset($record['post']) && !isset($record['comment'])) {
            $record['post'] = new stdClass();
        }

        if (isset($record['post'])) {
            if (!empty($record['post']->id)) {
                $oldpost = oublog_get_post($record['post']->id);
                $record['post']->userid = $oldpost->userid;
                $record['post']->oublogid = $oldpost->oublogid;
            } else {
                if (empty($record['post']->userid)) {
                    $record['post']->userid = $USER->id;
                }
                if (empty($record['post']->oublogid)) {
                    $record['post']->oublogid = $instance->id;
                }
            }
            if (empty($record['post']->message)) {
                $record['post']->message = array('text' => 'Test post');
            } else if (is_string($record['post']->message)) {
                // Support message being string to insert in db not form style.
                $record['post']->message = array('text' => $record['post']->message);
            }
            if (empty($record['post']->message['itemid'])) {
                // Draft files won't work anyway as no editor - so set to 1.
                $record['post']->message['itemid'] = 1;
            }
            if (empty($record['post']->allowcomments)) {
                $record['post']->allowcomments = OUBLOG_COMMENTS_ALLOW;
            }
            if (empty($record['post']->title)) {
                $record['post']->title = '';
            }
            if (empty($record['post']->visibility)) {
                $record['post']->visibility = OUBLOG_VISIBILITY_COURSEUSER;
            }
            if (empty($record['post']->tags)) {
                $record['post']->tags = '';
            }
            if (empty($record['post']->attachments)) {
                $record['post']->attachments = 0;
            }
            if ($USER->id === 0) {
                mtrace('oublog_add_post() will error as you must be a valid user to add a post');
            }
            if (empty($record['post']->id)) {
                return oublog_add_post($record['post'], $cm, $instance, $course);
            } else {
                return oublog_edit_post($record['post'], $cm);
            }
        } else if (isset($record['comment'])) {
            if (empty($record['comment']->postid)) {
                throw new coding_exception('Must pass postid when creating comment');
            }
            if (empty($record['comment']->userid)) {
                $record['comment']->userid = $USER->id;
            }
            if (empty($record['comment']->messagecomment)) {
                if (empty($record['comment']->message)) {
                    $record['comment']->messagecomment = array('text' => 'Test comment');
                } else {
                    // Support message being string to insert in db not form style.
                    $record['comment']->messagecomment = array('text' => $record['comment']->message);
                }
            } else if (is_string($record['comment']->messagecomment)) {
                // Support message being string to insert in db not form style.
                $record['comment']->messagecomment = array('text' => $record['comment']->messagecomment);
            }
            return oublog_add_comment($course, $cm, $instance, $record['comment']);
        }
    }

}
