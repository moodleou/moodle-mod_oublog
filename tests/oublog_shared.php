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

    protected function setUp(): void {
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

    /**
     * Creates a course and an OUBlog module with default or custom data.
     *
     * @param array $oublogdata Optional. Overrides for default blog data (e.g., 'name', 'intro', 'scale', 'assessed').
     * @return array An array with the created course and blog module objects.
     */
    protected function create_oublog_setup( array $oublogdata = []): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $defaultoublogdata = [
                'course' => $course->id,
                'name' => 'Test Blog',
                'intro' => 'My intro',
                'scale' => 100,
                'assessed' => 1,
        ];

        // Merge default data with provided overrides. Overrides take precedence.
        $oublogdata = array_merge($defaultoublogdata, $oublogdata);

        $blog = $generator->create_module('oublog', $oublogdata);
        return [$course, $blog];
    }

    public function test_indication_on_a_shared_blog_where_it_is_shared() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        list($course1, $masterblog) = $this->create_oublog_setup(['name' => 'This is master Blog', 'idnumber' => 'idmaster']);
        list($course2, $childblog) = $this->create_oublog_setup([
                'name' => 'This is child Blog',
                'individual' => OUBLOG_SEPARATE_INDIVIDUAL_BLOGS,
                'idsharedblog' => 'idmaster',
        ]);
        list($course3, $masterwithoutchildblog) = $this->create_oublog_setup([
                'name' => 'This is master Blog without any child',
                'idnumber' => 'idmasterwithoutchild',
        ]);
        $modinfo1 = get_fast_modinfo($course1);
        $modinfo2 = get_fast_modinfo($course2);
        $modinfo3 = get_fast_modinfo($course3);
        $cmmaster = $modinfo1->get_cm($masterblog->cmid);
        $cmchild = $modinfo2->get_cm($childblog->cmid);
        $cmmasterwithoutchild = $modinfo3->get_cm($masterwithoutchildblog->cmid);
        $context1 = \context_module::instance($cmmaster->id);
        $context2 = \context_module::instance($cmchild->id);
        $context3 = \context_module::instance($cmmasterwithoutchild->id);
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        assign_capability('moodle/course:manageactivities', CAP_ALLOW, $roleid, $context1->id);
        assign_capability('moodle/course:manageactivities', CAP_ALLOW, $roleid, $context2->id);
        assign_capability('moodle/course:manageactivities', CAP_ALLOW, $roleid, $context3->id);

        // Expected HTML output.
        $expectedmasterinfo = '<div class="oublog-shareinfo"><strong>This blog is shared</strong> under the name ' .
                '<strong>idmaster</strong> for use in other courses. It is included in the following: ' .
                '<a href="https://www.example.com/moodle/mod/oublog/view.php?id=' . $cmchild->id . '">tc_3</a>.</div>';
        $expectedchildinfo = '<div class="oublog-shareinfo"><strong>This is a shared blog</strong>.
                The <a href=\'https://www.example.com/moodle/mod/oublog/view.php?id=' .
                $cmmaster->id . '\'>original blog</a> is in tc_3.</div>';

        // Display sharing info.
        $childinfo = oublog_display_sharing_info($cmchild);
        $masterinfo = oublog_display_sharing_info($cmmaster);
        $masterwioutchildinfo = oublog_display_sharing_info($cmmasterwithoutchild);

        $this->assertEqualsIgnoringWhitespace($expectedchildinfo, $childinfo);
        $this->assertEqualsIgnoringWhitespace($expectedmasterinfo, $masterinfo);
        $this->assertEqualsIgnoringWhitespace('', $masterwioutchildinfo);
    }
}
