<?php
/**
 * Unit tests for (some of) mod/oublog/locallib.php.
 *
 * @author dan@danmarsden.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package oublog
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/mod/oublog/locallib.php');

class oublog_locallib_test extends UnitTestCaseUsingDatabase {
    public static $includecoverage = array('mod/oublog/locallib.php');
    public $oublog_tables = array('lib' => array(
                                      'course_categories',
                                      'course_sections',
                                      'course',
                                      'files',
                                      'modules',
                                      'context',
                                      'course_modules',
                                      'user',
                                      'capabilities',
                                      'role_assignments',
                                      'role_capabilities',
                                      'grade_items',
                                      ),
                                  'mod/oublog' => array(
                                      'oublog',
                                      'oublog_instances',
                                      'oublog_posts',
                                      'oublog_edits',
                                      'oublog_comments',
                                      'oublog_tags',
                                      'oublog_taginstances'
                                     ,'oublog_links')
                            );
    public $courseid = 1;
    public $course = array();
    public $userid;
    public $modules = array();
    public $usercount = 0;


     /**
     * Create temporary test tables and entries in the database for these tests.
     * These tests have to work on a brand new site.
     */
    function setUp() {
        global $CFG;

        parent::setup();

        $this->switch_to_test_db(); // All operations until end of test method will happen in test DB

        if (oublog_search_installed()) {
            $this->oublog_tables['local/ousearch'] = array(
                'local_ousearch_documents',
                'local_ousearch_words',
                'local_ousearch_occurrences');
        }

        foreach ($this->oublog_tables as $dir => $tables) {
            $this->create_test_tables($tables, $dir); // Create tables
            foreach ($tables as $table) { // Fill them if load_xxx method is available
                $function = "load_$table";
                if (method_exists($this, $function)) {
                    $this->$function();
                }
            }
        }

    }

    function tearDown() {
        parent::tearDown(); // All the test tables created in setUp will be dropped by this
    }

    function load_user() {
        $user = new stdClass();
        $user->username = 'testuser';
        $user->firstname = 'Test';
        $user->lastname = 'User';
        $this->userid = $this->testdb->insert_record('user', $user);
    }

    function load_course_categories() {
        $cat = new stdClass();
        $cat->name = 'misc';
        $cat->depth = 1;
        $cat->path = '/1';
        $this->testdb->insert_record('course_categories', $cat);
    }

    /**
     * Load module entries in modules table
     */
    function load_modules() {
        $module = new stdClass();
        $module->name = 'subpage';
        $module->id = $this->testdb->insert_record('modules', $module);
        $this->modules[] = $module;
    }

    function load_capabilities() {
        $cap = new stdClass();
        $cap->name = 'mod/oublog:view';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;
    }

    /*

     Unit tests cover:
         * Adding a blog
         * Deleting a blog
         * Adding posts
         * Adding comments
         * Getting a single post
         * Getting a list of posts

     Unit tests do NOT cover:
         * Deleting a Post - there is no back end function for this, the code is inline

    */

    // tests for adding and getting blog
    public function test_oublog_add_blog() {

        $course = $this->get_new_course();

        // test adding
        $oublog = new stdClass();
        $oublog->course = $course->id;
        $oublog->name = 'Test';
        $oublog->summary = '';
        $oublog->global = 0;
        $oublog->views = 0;
        $oublog->grade = 0;
        $oublog->id = oublog_add_instance($oublog);
        $this->assertIsA($oublog->id, 'integer');

        // add the course module records
        $coursesection = $this->get_new_course_section($course->id);
        $cm = $this->get_new_course_module($course->id, $oublog->id, $coursesection->id);
        $oublog->instance = $cm->instance;

        // test updating
        $oublog->name = 'Test Update';
        $this->assertTrue(oublog_update_instance($oublog));
    }

    public function test_oublog_add_post() {

        // whole course
        $course        = $this->get_new_course();
        $coursesection = $this->get_new_course_section($course->id);
        $oublog        = $this->get_new_oublog_whole_course($course->id);
        $cm            = $this->get_new_course_module($course->id, $oublog->id, $coursesection->id);

        // whole course - basic post
        $post = $this->get_post_hash($oublog->id);
        $postid = oublog_add_post($post,$cm,$oublog,$course);
        $this->assertIsA($postid, 'integer');

        // personal blog
        $course = $this->get_new_course();
        $coursesection = $this->get_new_course_section($course->id);
        $oublog = $this->get_new_oublog_personal($course->id);
        $cm     = $this->get_new_course_module($course->id, $oublog->id, $coursesection->id);

        // personal - basic post
        $post = $this->get_post_hash($oublog->id);
        $postid = oublog_add_post($post,$cm,$oublog,$course);
        $this->assertIsA($postid, 'integer');

    }

    /* test_oublog_add_comment */
    public function test_oublog_add_comment() {

        // personal blog
        $course = $this->get_new_course();
        $coursesection = $this->get_new_course_section($course->id);
        $oublog = $this->get_new_oublog_personal($course->id);
        $cm     = $this->get_new_course_module($course->id, $oublog->id, $coursesection->id);

        $post = $this->get_post_hash($oublog->id);
        $postid = oublog_add_post($post,$cm,$oublog,$course);

        $comment = new stdClass();
        $comment->title = 'Test Comment';
        $comment->message = 'Message for test comment';
        $comment->authorname = 'Tester';
        $comment->postid = $postid;

        $commentid = oublog_add_comment($course,$cm,$oublog,$comment);
        $this->assertIsA($commentid, 'integer');

        // whole course
        $oublog = $this->get_new_oublog_whole_course($course->id);
        $cm     = $this->get_new_course_module($course->id, $oublog->id,$coursesection->id);

        $post = $this->get_post_hash($oublog->id);
        $postid = oublog_add_post($post,$cm,$oublog,$course);

        // only reset what we need to
        $comment->postid = $postid;

        $commentid = oublog_add_comment($course,$cm,$oublog,$comment);
        $this->assertIsA($commentid, 'integer');
    }

    // edit posts

    /*
     Test getting a single post
    */
    public function test_oublog_add_and_get_post() {

        $course = $this->get_new_course();
        $coursesection = $this->get_new_course_section($course->id);
        $oublog = $this->get_new_oublog_personal($course->id);
        $cm     = $this->get_new_course_module($course->id, $oublog->id, $coursesection->id);

        // first make sure we have a post to use
        $post_hash = $this->get_post_hash($oublog->id);

        // set some custom things to check
        $title_check   = "test_oublog_get_post";
        $message_check = "test_oublog_get_post";
        $post_hash->title   = $title_check;
        $post_hash->message['text'] = $message_check;

        // create the post - assumes oublog_add_post is working
        $postid = oublog_add_post($post_hash,$cm,$oublog,$course);

        // get the actual post - what we're really testing
        $post = oublog_get_post($postid);

        // do some basic checks - does it match our test post created above?
        $this->assertIsA($post, "stdClass");
        $this->assertEqual($post->title, $title_check);
        $this->assertEqual($post->message, $message_check);
    }

    /*
     Test getting mulitple posts
    */
    public function test_oublog_add_and_get_posts() {   // disabled this as it's calling has_capability, which is failing, need to figure out how to implement that

        $course = $this->get_new_course();
        $coursesection = $this->get_new_course_section($course->id);
        $oublog = $this->get_new_oublog_whole_course($course->id);
        $cm     = $this->get_new_course_module($course->id, $oublog->id, $coursesection->id);

        $postcount = 3; // number of posts to test

        $title_check   = "test_oublog_get_posts";

        // first make sure we have some posts to use
        $post_hashes = array();
        for ($i = 1; $i <= $postcount; $i++) {
            $post_hashes[$i] = $this->get_post_hash($oublog->id);
            $post_hashes[$i]->title   = $title_check . '_' . $i;
        }

        // create the posts - assumes oublog_add_post is working
        $postids = array();
        foreach($post_hashes as $post_hash) {
            $postids[] = oublog_add_post($post_hash,$cm,$oublog,$course);
        }

        // get a list of the posts
        $context      = get_context_instance(CONTEXT_MODULE, $cm->instance);
        $currentgroup = oublog_get_activity_group($cm, true);

        list($posts, $recordcount) = oublog_get_posts($oublog, $context, 0, $cm, $currentgroup);

        // same name of records returned that were added?
        $this->assertEqual($recordcount, $postcount);

        // first post returned should match the last one added
        $this->assertEqual($posts[$postcount]->id,  $postcount);
        $this->assertEqual($posts[$postcount]->title,  $title_check . '_' . $postcount);

    }

    /* test_oublog_get_last_modified */
    function test_oublog_get_last_modified() {

        $course = $this->get_new_course();
        $coursesection = $this->get_new_course_section($course->id);
        $oublog = $this->get_new_oublog_whole_course($course->id);
        $cm     = $this->get_new_course_module($course->id, $oublog->id, $coursesection->id);

        $post = $this->get_post_hash($oublog->id);
        $postid = oublog_add_post($post,$cm,$oublog,$course);

        $lastmodified = oublog_get_last_modified($cm, $course, $this->userid);
        $this->assertNotNull($lastmodified, 'integer');

    }

    /*
     These functions require us to create database entries and/or grab objects to make it possible to test the
     many permuations required for OU Blogs.

    */

    function get_new_user() {

        $this->usercount++;

        $user = new stdClass();
        $user->username = 'testuser' . $this->usercount;
        $user->firstname = 'Test';
        $user->lastname = 'User';
        $user->id = $this->testdb->insert_record('user', $user);
        return $user;
    }

    function get_new_course() {
        $course = new stdClass();
        $course->category = 1;
        $course->fullname = 'Anonymous test course';
        $course->shortname = 'ANON';
        $course->summary = '';
        $course->modinfo = null;
        $course->id = $this->testdb->insert_record('course', $course);
        return $course;
    }

    function get_new_course_section($courseid, $sectionid=1) {
        $section = new stdClass();
        $section->course = $courseid;
        $section->section = $sectionid;
        $section->name = 'Test Section';
        $section->id = $this->testdb->insert_record('course_sections', $section);
        return $section;
    }

    public function get_new_course_module($courseid, $subpageid, $section, $groupmode=0) {
        $cm = new stdClass();
        $cm->course = $courseid;
        $cm->module = $this->modules[0]->id;
        $cm->instance = $subpageid;
        $cm->section = $section;
        $cm->groupmode = $groupmode;
        $cm->groupingid = 0;
        $cm->id = $this->testdb->insert_record('course_modules', $cm);
        return $cm;
    }

    /* Returns a global AKA personal blog */
    public function get_new_oublog_personal($courseid){
        $oublog = new stdClass();
        $oublog->course = $courseid;
        $oublog->name = 'Personal Blog';
        $oublog->summary = '';
        $oublog->global = 1;
        $oublog->views = 0;
        $oublog->allowcomments = 0;
        $oublog->maxvisibility = 100;
        $oublog->id = $this->testdb->insert_record('oublog', $oublog);
        return $oublog;
    }

    /* Returns a whole course blog */
    public function get_new_oublog_whole_course($courseid){
        $oublog = new stdClass();
        $oublog->course = $courseid;
        $oublog->name = 'Whole Course';
        $oublog->summary = '';
        $oublog->global = 0;
        $oublog->views = 0;
        $oublog->allowcomments = 0;
        $oublog->maxvisibility = 100;
        $oublog->grade = 0;
        $oublog->id = $this->testdb->insert_record('oublog', $oublog);
        return $oublog;
    }

    public function get_post_hash($oublogid) {
        $post = new stdClass();
        $post->oublogid = $oublogid;
        $post->userid = $this->userid;
        $post->groupid = 0;
        $post->title = "testpost";
        $post->message['itemid'] = 1;
        $post->message['text'] = "<p>newpost</p>";
        $post->allowcomments = 1;
        $post->visibility = 100;
        $post->attachments = '';
        return $post;
    }
}
