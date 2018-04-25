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
        $childrenblog1 = oublog_get_children($blogmaster1->cm->idnumber);
        $childrenblog2 = oublog_get_children($blogmaster2->cm->idnumber);

        $result[] = array_slice($childrenblog1, 0, 2);
        $result[] = array_slice($childrenblog2, 0, 2);

        $this->assertNotNull($result);
        $this->assertEquals(count($result), 2);

        $this->assertEquals($chilblog1->id, $result[0][1]->id);
        $this->assertEquals($chilblog2->id, $result[0][0]->id);
        $this->assertEquals($chilblog3->id, $result[1][1]->id);
        $this->assertEquals($chilblog4->id, $result[1][0]->id);
        $this->assertEquals('idmaster2', $result[1][0]->idsharedblog);
        $this->assertEquals('idmaster', $result[0][0]->idsharedblog);
    }
}