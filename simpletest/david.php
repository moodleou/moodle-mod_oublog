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
                                      'course',
                                      'files',
                                      'modules',
                                      'context',
                                      'course_modules',
                                      'user'),
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
    public $activities = array();
    public $modules = array();
    public $course_module = array();

    // some useful constants
    const BASEMODULEKEY = 0;
    const PERSONALBLOGINSTANCE = 1;
    const COURSEBLOGINSTANCE = 2;


     /**
     * Create temporary test tables and entries in the database for these tests.
     * These tests have to work on a brand new site.
     */
    function setUp() {
        global $CFG;

        parent::setup();

        $this->switch_to_test_db(); // All operations until end of test method will happen in test DB

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
    function load_course() {
        $course = new stdClass();
        $course->category = 1;
        $course->fullname = 'Sitecourse';
        $course->shortname = 'SITE';
        $course->summary = '';
        $course->id = $this->testdb->insert_record('course', $course);

        $course = new stdClass();
        $course->category = 1;
        $course->fullname = 'Anonymous test course';
        $course->shortname = 'ANON';
        $course->summary = '';
        $this->courseid = $this->testdb->insert_record('course', $course);
        $course->id = $this->courseid;
        $this->course = $course;
    }

    /**
     * Load module entries in modules table
     */
    function load_modules() {
        $module = new stdClass();
        $module->name = 'oublog';
        $module->id = $this->testdb->insert_record('modules', $module);
        $this->modules[self::BASEMODULEKEY] = $module;
    }

    /**
     * Load module instance entries in course_modules table
     */
    function load_course_modules() {

        // personal blog cm
        $course_module = $this->get_course_module_object(1, self::PERSONALBLOGINSTANCE);         
        $this->course_module[0] = $course_module;

        // course blog cm
        $course_module = $this->get_course_module_object($this->courseid, self::COURSEBLOGINSTANCE);
        $this->course_module[1] = $course_module;

        $nextinstance = self::COURSEBLOGINSTANCE + 1;

        // some more course blogs
        $course_module = $this->get_course_module_object($this->courseid, $nextinstance);
        $this->course_module[2] = $course_module;

        $nextinstance++;

        $course_module = $this->get_course_module_object($this->courseid, $nextinstance);
        $this->course_module[3] = $course_module;

    }

    /**
     * Load test oublog data into the database
     */
    function load_oublog() {
        $oublog = new stdClass();
        $oublog->course = 1;
        $oublog->name = 'Personal Blogs';
        $oublog->summary = '';
        $oublog->global = 1;
        $oublog->views = 0;
        $oublog->id = $this->testdb->insert_record('oublog', $oublog);
        $this->activities[0] = $oublog;

        $oublog = new stdClass();
        $oublog->course = $this->courseid;
        $oublog->name = 'Whole Course';
        $oublog->summary = '';
        $oublog->global = 0;
        $oublog->views = 0;
        $oublog->id = $this->testdb->insert_record('oublog', $oublog);
        $this->activities[1] = $oublog;

        $oublog = new stdClass();
        $oublog->course = $this->courseid;
        $oublog->name = 'Groups';
        $oublog->summary = '';
        $oublog->global = 0;
        $oublog->views = 0;
        $oublog->id = $this->testdb->insert_record('oublog', $oublog);
        $this->activities[2] = $oublog;

        $oublog = new stdClass();
        $oublog->course = $this->courseid;
        $oublog->name = 'individuals';
        $oublog->summary = '';
        $oublog->global = 0;
        $oublog->views = 0;
        $oublog->id = $this->testdb->insert_record('oublog', $oublog);
        $this->activities[3] = $oublog;
    }

    // helper functions for manipulating datasets used in testing
    public function get_course_module_object($course, $instance) {
        $course_module = new stdClass();
        $course_module->course = $course;
        $course_module->module = $this->modules[self::BASEMODULEKEY]->id;
        $course_module->instance = $instance;
        $course_module->id = $this->testdb->insert_record('course_modules', $course_module);
        return $course_module;
    }

    public function get_oublog_post_object($blogid = 1) {
        $post = new stdClass();
        $post->oublogid = $blogid;
        $post->userid = $this->userid;
        $post->groupid = 0;
        $post->title = "testpost";
        $post->message['itemid'] = 1;
        $post->message['text'] = "<p>newpost</p>";
        $post->allowcomments = 1;
        return $post;
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

    // tests for adding deleting blog
    public function XXX_test_oublog_add_blog() {
        $oublog = new stdClass();
        $oublog->course = $this->courseid;
        $oublog->name = 'Test Addiiton';
        $oublog->summary = '';
        $oublog->global = 0;
        $oublog->views = 0;
        $this->assertTrue(oublog_add_instance($oublog));
    }

    public function XXX_test_oublog_delete_blog() {
        $instanceid = $this->course_module[3]->instance;
        $this->assertTrue(oublog_delete_instance($instanceid));
    }

    // tests for adding posts
    public function XXX_test_oublog_add_post_personal_nofile() { //site/personal blog without files
        $post = new stdClass();
        $post->oublogid = $this->activities[0]->id;
        $post->userid = 2;
        $post->groupid = 0;
        $post->title = "testpost";
        $post->message['itemid'] = 1;
        $post->message['text'] = "<p>newpost</p>";
        $post->allowcomments = 1;
        $postid = oublog_add_post($post,$this->course_module[0],$this->activities[0],$this->course);

        $this->assertTrue($postid);
    }
    public function XXX_test_oublog_add_post_personal_with_file() { //site/personal blog with embedded files
        $post = new stdClass();

        $this->assertTrue(false);
    }
    public function XXX_test_oublog_add_post_personal_with_file_and_attachment() { //site/personal with embedded and attached files
        $this->assertTrue(false);
    }

    public function XXX_test_oublog_add_post_course_nofile() { //course blog without files
        $post = new stdClass();
        $post->oubloginstancesid = 1;
        $post->groupid = 0;
        $post->title = "testpost";
        $post->message['itemid'] = 1;
        $post->message['text'] = "<p>newpost</p>";
        $post->allowcomments = 1;
        $postid = oublog_add_post($post,$this->course_module[1],$this->activities[1],$this->course);

        $this->assertTrue($postid);
    }
    public function XXX_test_oublog_add_post_course_with_file() { //site/personal blog with embedded files
        $this->assertTrue(false);
    }
    public function XXX_test_oublog_add_post_course_with_file_and_attachment() { //site/personal with embedded and attached files
        $this->assertTrue(false);
    }

    // add comments
    public function XXX_test_oublog_add_comment() {

        // post to add a comment to - assumes oublog_add_post is working
        $postobj = $this->get_oublog_post_object();
        $postid = oublog_add_post($postobj,$this->course_module[0],$this->activities[0],$this->course);

        $courseid = $this->activities[1]->course;
        $blogid   = $this->activities[1]->id;

        $course = $this->testdb->get_record("course", array("id"=>$courseid)); 
        $cm   = get_coursemodule_from_id('oublog', $blogid);
        $blog = $this->testdb->get_record("oublog", array("id"=>$cm->instance)); 

        $comment = new stdClass();
        $comment->title = 'Test Comment';
        $comment->message = 'Message for test comment';
        $comment->authorname = 'Tester';
        $comment->postid = $postid;

        $commentid = oublog_add_comment($course,$cm,$blog,$comment);
        $this->assertIsA($commentid, 'integer');

    }
 
    public function XXX_test_oublog_delete_comment() {
        // can't find function to use
        $this->assertTrue(false);
    }

    // edit posts


    /* 
     Test getting a single post
    */
    public function XXX_test_oublog_add_and_get_post() {
        // first make sure we have a post to use
        $postobj = $this->get_oublog_post_object();

        // set some custom things to check
        $title_check   = "test_oublog_get_post";
        $message_check = "test_oublog_get_post";
        $postobj->title   = $title_check;
        $postobj->message['text'] = $message_check;

        // create the post - assumes oublog_add_post is working
        $postid = oublog_add_post($postobj,$this->course_module[0],$this->activities[0],$this->course);

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
    public function XXX_test_oublog_add_and_get_posts() {

        $blogid = $this->activities[1]->id; // use course blog

        $postcount = 3; // number of posts to test

        $title_check   = "test_oublog_get_posts";

        // first make sure we have some posts to use
        $postobjs = array();
        for ($i = 1; $i <= $postcount; $i++) {
            $postobjs[$i] = $this->get_oublog_post_object($blogid);
            $postobjs[$i]->title   = $title_check . '_' . $i;
        }

        // create the posts - assumes oublog_add_post is working
        $postids = array();
        foreach($postobjs as $postobj) {
            $postids[] = oublog_add_post($postobj,$this->course_module[self::COURSEBLOGINSTANCE],$this->activities[0],$this->course);  
        }

        // get a list of the posts
        $cm           = get_coursemodule_from_id('oublog', $blogid);
        $blog         = $this->testdb->get_record("oublog", array("id"=>$cm->instance)); 
        $context      = get_context_instance(CONTEXT_MODULE, $cm->instance);
        $currentgroup = oublog_get_activity_group($cm, true);

        list($posts, $recordcount) = oublog_get_posts($blog, $context, 0, $cm, $currentgroup);

        // same name of records returned that were added?
        $this->assertEqual($recordcount, $postcount); 

        // first post returned should match the last one added
        $this->assertEqual($posts[$postcount]->id,  $postcount); 
        $this->assertEqual($posts[$postcount]->title,  $title_check . '_' . $postcount);

    }

    // get posts for atom/rss feed

    // test last modified   
    function XXX_test_oublog_get_last_modified1() {

        $courseid = $this->activities[1]->course;
        $cmid   = $this->activities[1]->id;

        $course = $this->testdb->get_record("course", array("id"=>$courseid)); 
        $cm   = get_coursemodule_from_id('oublog', $cmid);

        // need to make sure we have a post to check against
        $postobj = $this->get_oublog_post_object();
        $postid = oublog_add_post($postobj,$cm,$this->activities[1],$course);

        // without user
        $lastmodified = oublog_get_last_modified($cm, $course);
        $this->assertIsA($lastmodified, 'integer');

        // with user
        $lastmodified = oublog_get_last_modified($cm, $course, 1);
        $this->assertIsA($lastmodified, 'integer');

    }

    function test_oublog_get_last_modified() {

        /*  I want a course, a course module, an oublog record, and a post */
        $course = $this->get_course();
        $oublog = $this->get_oublog_personal($course->id);
        $cm     = $this->get_course_module($course->id, $oublog->id);

        $post = $this->get_post_hash($oublog->id);
        $postid = oublog_add_post($post,$cm,$oublog,$course);

        $lastmodified = oublog_get_last_modified($cm, $course, $this->userid);
        $this->assertNotNull($lastmodified, 'integer');

    }

    /* Helper functions */

    function get_course() {
        $course = new stdClass();
        $course->category = 1;
        $course->fullname = 'Anonymous test course';
        $course->shortname = 'ANON';
        $course->summary = '';
        $this->courseid = $this->testdb->insert_record('course', $course);
        $course->id = $this->courseid;
        return $course;
    }

    public function get_oublog_personal($courseid){
        $oublog = new stdClass();
        $oublog->course = $courseid;
        $oublog->name = 'Personal Blog';
        $oublog->summary = '';
        $oublog->global = 1;
        $oublog->views = 0;
        $oublog->id = $this->testdb->insert_record('oublog', $oublog);
        return $oublog;
    }

    public function get_course_module($courseid, $oublogid) {
        $cm = new stdClass();
        $cm->course = $courseid;
        $cm->module = $this->modules[self::BASEMODULEKEY]->id;
        $cm->instance = $oublogid;
        $cm->groupmode = 0;
        $cm->id = $this->testdb->insert_record('course_modules', $cm);
        return $cm;
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
        return $post;
    }
}
