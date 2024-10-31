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
 * Behat data generator for mod_oublog.
 *
 * @package   mod_oublog
 * @category  test
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_oublog_generator extends behat_generator_base {

    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
                'posts' => [
                        'singular' => 'post',
                        'datagenerator' => 'behat_post',
                        'required' => ['blog', 'user'],
                        'switchids' => ['blog' => 'blog', 'user' => 'userid', 'time' => 'timeposted'],
                ],
                'comments' => [
                        'singular' => 'comment',
                        'datagenerator' => 'behat_comment',
                        'required' => ['blog', 'post'],
                        'switchids' => ['blog' => 'blog', 'post' => 'postid', 'user' => 'userid', 'time' => 'timeposted'],
                ],
        ];
    }

    /**
     * Get the blog id using an activity idnumber or name.
     *
     * @param string $idnumberorname The blog activity idnumber or name.
     * @return int The blog id
     */
    protected function get_blog_id(string $idnumberorname): int {
        return $this->get_cm_by_activity_name('oublog', $idnumberorname)->instance;
    }

    protected function get_post_id(string $title): int {
        global $DB;
        $result = $DB->get_record('oublog_posts', ['title' => $title], 'id');
        if ($result) {
            return $result->id;
        } else {
            throw new \coding_exception('Cannot find comment with subject text');
        }
    }

    protected function get_time_id(string $time): int {
        // Convert string time to unix timestamp - take care as timezone not considered.
        if (empty($time)) {
            return time();
        }
        return strtotime($time);
    }
}
