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
 * Unit tests testing locallib functions inc access etc.
 * To test all unit tests in this folder use cmd:
 * find mod/oublog/tests/*_test.php -type f -print | xargs -n1 vendor/bin/phpunit --debug
 *
 * @package    mod_oublog
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');// It must be included from a Moodle page.
}
global $CFG;
require_once($CFG->dirroot . '/mod/oublog/tests/oublog_test_lib.php');
require_once($CFG->dirroot . '/mod/oublog/locallib.php');

class cron_task_test extends oublog_test_lib {
    public function test_cron_task() {
        global $USER, $DB;
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $bloggenerator = $generator->get_plugin_generator('mod_oublog');

        // Personal blog.
        if (!$oublog = $DB->get_record('oublog', ['global' => 1])) {
            $oublog = $this->get_new_oublog($course->id, ['global' => 1, 'maxvisibility' => OUBLOG_VISIBILITY_PUBLIC]);
        }

        $users[1] = $generator->create_user();
        $users[2] = $generator->create_user();

        $generator->enrol_user($users[1]->id, $course->id, 'teacher');
        $generator->enrol_user($users[2]->id, $course->id, 'student');

        $this->setAdminUser();

        $postid[1] = $bloggenerator->create_post($oublog,
            ['title' => 'OtherTitle', 'message' => 'OtherMessage', 'tags' => 'frogs',
                'attachments' => self::create_file_area_with_frog_picture()]);

        $bloggenerator->create_post($oublog,
            ['id' => $postid[1], 'title' => 'Change', 'message' => ['text' => 'Different',
                'itemid' => self::create_file_area_with_frog_picture()],
                'tags' => 'frogs,amphibians']);

        $bloggenerator->create_comment($oublog,
            ['postid' => $postid[1], 'title' => 'Comment',
                'messagecomment' => ['text' => 'CommentMessage',
                    'itemid' => self::create_file_area_with_frog_picture()]]);

        $bloggenerator->create_comment($oublog,
            ['postid' => $postid[1], 'title' => 'Greeting', 'message' => 'Hello frogs!']);


        $this->setUser($users[1]);

        $postid[2] = $bloggenerator->create_post($oublog,
            ['title' => 'OtherTitle', 'message' => 'OtherMessage', 'tags' => 'frogs',
                'attachments' => self::create_file_area_with_frog_picture()]);

        $bloggenerator->create_post($oublog,
            ['id' => $postid[2], 'title' => 'Change', 'message' => ['text' => 'Different',
                'itemid' => self::create_file_area_with_frog_picture()],
                'tags' => 'frogs,amphibians']);

        $bloggenerator->create_comment($oublog,
            ['postid' => $postid[2], 'title' => 'Comment', 'message' => 'Hello frogs1!']);

        $bloggenerator->create_comment($oublog,
            ['postid' => $postid[2], 'title' => 'Comment2', 'message' => 'Hello frogs2!']);

        $this->setUser($users[2]);

        $postid[3] = $bloggenerator->create_post($oublog,
            ['title' => 'OtherTitle', 'message' => 'OtherMessage', 'tags' => 'frogs',
                'attachments' => self::create_file_area_with_frog_picture()]);

        $bloggenerator->create_post($oublog,
            ['id' => $postid[3], 'title' => 'Change', 'message' => ['text' => 'Different',
                'itemid' => self::create_file_area_with_frog_picture()],
                'tags' => 'frogs,amphibians']);

        $bloggenerator->create_comment($oublog,
            ['postid' => $postid[3], 'title' => 'Comment', 'message' => 'Hello frogs1!']);

        $bloggenerator->create_comment($oublog,
            ['postid' => $postid[3], 'title' => 'Comment2', 'message' => 'Hello frogs2!']);


        $posts = array_values($DB->get_records('oublog_posts'));
        $comments = array_values($DB->get_records('oublog_comments'));
        $tagintances = array_values($DB->get_records('oublog_taginstances'));
        $editedpost = array_values($DB->get_records('oublog_edits'));

        $this->assertCount(3, $posts);
        $this->assertCount(6, $comments);
        $this->assertCount(6, $tagintances);
        $this->assertCount(3, $editedpost);

        // Delete $postid[1] and $postid[3] with timedeleted exceeding 3 months (90 days)
        $this->setAdminUser();
        $DB->update_record('oublog_posts',
            ['id' => $postid[1], 'deletedby' => $USER->id, 'timedeleted' => strtotime('-100 days')]);
        $DB->update_record('oublog_posts',
            ['id' => $postid[3], 'deletedby' => $USER->id, 'timedeleted' => strtotime('-100 days')]);

        $task = new \mod_oublog\task\cron_task();
        $task->execute();

        $posts = array_values($DB->get_records('oublog_posts'));
        $comments = array_values($DB->get_records('oublog_comments'));
        $tagintances = array_values($DB->get_records('oublog_taginstances'));
        $editedpost = array_values($DB->get_records('oublog_edits'));

        $this->assertCount(1, $posts);
        $this->assertCount(2, $comments);
        $this->assertCount(2, $tagintances);
        $this->assertCount(1, $editedpost);
        $this->assertEquals($postid[2], $posts[0]->id);
    }

    /**
     * Creates a file area with a frog picture in, for use when attaching a file.
     *
     * @return int File area draft id
     */
    protected static function create_file_area_with_frog_picture() {
        global $USER;

        $fs = get_file_storage();
        $usercontextid = \context_user::instance($USER->id)->id;
        $draftid = file_get_unused_draft_itemid();
        $fs->create_file_from_pathname(['component' => 'user', 'filearea' => 'draft',
            'contextid' => $usercontextid, 'itemid' => $draftid, 'filepath' => '/',
            'filename' => 'frog.jpg'], __DIR__ . '/fixtures/pd-frog.jpg');

        return $draftid;
    }


    public function test_cron_with_many_data() {
        global $DB;
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $bloggenerator = $generator->get_plugin_generator('mod_oublog');

        // Personal blog.
        if (!$oublog = $DB->get_record('oublog', ['global' => 1])) {
            $oublog = $this->get_new_oublog($course->id, ['global' => 1, 'maxvisibility' => OUBLOG_VISIBILITY_PUBLIC]);
        }
        for ($i = 0; $i <= 500; $i++) {
            $user = $generator->create_user();
            $generator->enrol_user($user->id, $course->id, 'student');
            $this->setUser($user);

            $postid = $bloggenerator->create_post($oublog,
                    ['title' => 'OtherTitle', 'message' => 'OtherMessage', 'tags' => 'frogs',
                            'attachments' => self::create_file_area_with_frog_picture()]);
            $DB->update_record('oublog_posts',
                    ['id' => $postid, 'deletedby' => $user->id, 'timedeleted' => strtotime('-100 days')]);
        }
        $posts = array_values($DB->get_records('oublog_posts'));
        $binstances = array_values($DB->get_records('oublog_instances'));

        $this->assertCount(501, $posts);
        $this->assertCount(501, $binstances);
        $task = new \mod_oublog\task\cron_task();
        $task->execute();
        $posts = array_values($DB->get_records('oublog_posts'));
        $binstances = array_values($DB->get_records('oublog_instances'));
        $this->assertCount(0, $posts);
        $this->assertCount(501, $binstances);
    }

    public function test_cron_with_zero_data() {
        global $DB;
        $this->resetAfterTest(true);

        $posts = array_values($DB->get_records('oublog_posts'));
        $binstances = array_values($DB->get_records('oublog_instances'));

        $this->assertCount(0, $posts);
        $this->assertCount(0, $binstances);
        $task = new \mod_oublog\task\cron_task();
        $task->execute();
        $posts = array_values($DB->get_records('oublog_posts'));
        $binstances = array_values($DB->get_records('oublog_instances'));
        $this->assertCount(0, $posts);
        $this->assertCount(0, $binstances);
    }
}
