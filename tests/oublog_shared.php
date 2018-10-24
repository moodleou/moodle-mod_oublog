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

global $CFG;
require_once($CFG->dirroot . '/mod/oublog/tests/oublog_test_lib.php');
require_once($CFG->dirroot . '/mod/oublog/locallib.php');

class oublog_shared extends oublog_test_lib
{
    protected $course1;

    protected function setUp() {
        $this->resetAfterTest(true);

        $this->course1 = $this->getDataGenerator()->create_course([
                'fullname' => 'Course1',
                'shortname' => 'C1'
        ]);
        $user = $this->get_new_user();
        $this->setUser($user);
    }

    /**
     * Test get master blog from ID Shared.
     */
    public function test_oublog_get_master() {
        $masterblog1 = $this->get_new_oublog($this->course1,
                ['name' => 'Master Blog 1', 'idnumber' => 'idmaster']);
        $masterblog2 = $this->get_new_oublog($this->course1,
                ['name' => 'Master Blog 2', 'idnumber' => 'idmaster2']);
        $this->get_new_post($masterblog1);
        $this->get_new_post($masterblog2);

        // Get blog from ID number.
        $result[] = oublog_get_master($masterblog1->cm->idnumber);
        $result[] = oublog_get_master($masterblog2->cm->idnumber);

        $this->assertNotNull($result);
        $this->assertEquals(count($result), 2);
        $this->assertEquals($masterblog1->id, $result[0]->id);
        $this->assertEquals($masterblog2->id, $result[1]->id);
    }

    /**
     * Test get children of masterblog from ID Number.
     */
    public function test_oublog_get_children() {
        $blogmaster1 = $this->get_new_oublog($this->course1,
                ['name' => 'Master Blog 1', 'idnumber' => 'idmaster']);
        $blogmaster2 = $this->get_new_oublog($this->course1,
                ['name' => 'Master Blog 2', 'idnumber' => 'idmaster2']);
        $this->get_new_post($blogmaster1);
        $this->get_new_post($blogmaster2);

        // Create blog2 has idsharedblog is idnumber of blog1.
        $chilblog1 = $this->get_new_oublog($this->course1,
                ['name' => 'Child Blog 1',
                'idsharedblog' => 'idmaster',
                'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS]);
        $chilblog2 = $this->get_new_oublog($this->course1,
                ['name' => 'Child Blog 2',
                'idsharedblog' => 'idmaster',
                'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS]);
        $chilblog3 = $this->get_new_oublog($this->course1,
                ['name' => 'Child Blog 3',
                'idsharedblog' => 'idmaster2',
                'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS]);
        $chilblog4 = $this->get_new_oublog($this->course1,
                ['name' => 'Child Blog 4',
                'idsharedblog' => 'idmaster2',
                'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS]);

        // Get children blog from ID number.
        $result[] = oublog_get_children($blogmaster1->cm->idnumber);
        $result[] = oublog_get_children($blogmaster2->cm->idnumber);

        $this->assertNotNull($result);
        $this->assertEquals(count($result), 2);

        $this->assertEquals($chilblog1->id, $result[0][$chilblog1->id]->id);
        $this->assertEquals($chilblog2->id, $result[0][$chilblog2->id]->id);
        $this->assertEquals($chilblog3->id, $result[1][$chilblog3->id]->id);
        $this->assertEquals($chilblog4->id, $result[1][$chilblog4->id]->id);

        $this->assertEquals('idmaster', $result[0][$chilblog1->id]->idsharedblog);
        $this->assertEquals('idmaster2', $result[1][$chilblog3->id]->idsharedblog);
    }

    /**
     * Test get data from shared blog base on cmid.
     */
    public function test_oublog_get_blog_data_base_on_cmid_of_childblog() {
        global $DB;
        // Same course.
        $blog1 = $this->get_new_oublog($this->course1,
                ['name' => 'Blog1', 'idnumber' => 'idmaster']);
        // Create blog2 has idsharedblog is idnumber of blog1.
        $blog2 = $this->get_new_oublog($this->course1,
                ['name' => 'Blog2',
                'idsharedblog' => 'idmaster',
                'individual' => OUBLOG_VISIBLE_INDIVIDUAL_BLOGS
                ]
        );
        $result = oublog_get_blog_data_base_on_cmid_of_childblog($blog2->cm->id, $blog1);
        $expected['context'] = context_module::instance($blog2->cm->id);
        list($expected['course'], $expected['cm']) = get_course_and_cm_from_cmid($blog2->cm->id, 'oublog');
        $expected['ousharedblog'] = $DB->get_record('oublog', array('id' => $expected['cm']->instance));
        $this->assertEquals($expected, $result);
    }

    /**
     * Test add cmid to img tags in html.
     */
    public function test_oublog_add_cmid_to_tag_atrribute() {
        global $CFG;
        $link = $CFG->wwwroot . '/abcd/img/test';
        $html = '<p>test html <img src="' . $link . '"> <img src="test.com"></p>';
        $result = oublog_add_cmid_to_tag_atrribute(1, $html, 'img', 'src');
        $html = str_replace('img/test', 'img/test?cmid=1', $html);
        $this->assertEquals($html, $result);

        // Special character case.
        $htmlspecial = '<p>ε Σ £ Â ♠</p> <img src="">';
        $result = oublog_add_cmid_to_tag_atrribute(1, $htmlspecial, 'img', 'src');
        $this->assertEquals($htmlspecial, $result);
    }

    /**
     *Test participation of masterblog and child blog.
     */
    public function test_masterblog_and_childblog_get_participation_details() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create data of masterblog for getting participation.
        $course = $this->get_new_course();
        $masterblog = $this->get_new_oublog($course->id);
        $masterblog->allowcomments = OUBLOG_COMMENTS_ALLOW;
        $masterblog->cm->idnumber = 'idmaster';
        $masterblog->individual = OUBLOG_VISIBLE_INDIVIDUAL_BLOGS;

        $cmmaster = get_coursemodule_from_id('oublog', $masterblog->cmid);
        $student1 = $this->get_new_user('student', $course->id);
        $student2 = $this->get_new_user('student', $course->id);
        // Number of posts and comments to create for whole course tests.
        $postcountwc = 3;
        $titlecheck = 'test_sharedlog_participation';
        // Prepare to make some posts using the posts stub.
        $posthashes = array();
        for ($i = 1; $i <= $postcountwc; $i++) {
            $posthashes[$i] = $this->get_post_stub($masterblog->id);
            $posthashes[$i]->title = 'Test Post ' . $titlecheck;
            // Add the posting student.
            $posthashes[$i]->userid = $student1->id;
        }
        // Create the posts also add student comments to those posts.
        $postids = $commentids = array();
        foreach ($posthashes as $posthash) {
            $postids[] = oublog_add_post($posthash, $cmmaster, $masterblog, $course);
            // Add the commenting student.
            $comment = $this->get_comment_stub($posthash->id, $student2->id);
            $comment->title .= " ".$titlecheck;
            $commentids[] = oublog_add_comment($course, $cmmaster, $masterblog, $comment);
        }
        // Get the participation object with counts of posts and comments.
        $groupmaster = oublog_get_activity_group($cmmaster);
        $individualmaster = 0;
        $getposts = true;
        $getcomments = true;
        $resultparticipationmaster = oublog_get_participation_details($masterblog, $groupmaster, $individualmaster,
            null, null, 0, $getposts, $getcomments, 0, 0);

        // Create childblog get participation from masterblog.
        $childblog = $this->get_new_oublog($course->id);
        $childblog->allowcomments = OUBLOG_COMMENTS_ALLOW;
        $childblog->idsharedblog = 'idmaster';
        $childblog->individual = OUBLOG_VISIBLE_INDIVIDUAL_BLOGS;
        $cmchild = get_coursemodule_from_id('oublog', $childblog->cmid);
        $childgroup = oublog_get_activity_group($cmchild);
        $childindividual = 0;
        $resultparticipationchild = oublog_get_participation_details($childblog, $childgroup, $childindividual,
            null, null, 0, $getposts, $getcomments, 0, 0, $masterblog);

        $this->assertEquals($resultparticipationmaster, $resultparticipationchild);

    }
}