<?php
/**
 * This page prints the blog index page
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */

require_once("../../config.php");
require_once("locallib.php");

$id = required_param('id', PARAM_INT);   // course

if (! $course = $DB->get_record('course', array('id'=>$id))) {
    print_error('coursemisconf');
}

// Support for OU shared activities system, if installed
$grabindex=$CFG->dirroot.'/course/format/sharedactv/grabindex.php';
if(file_exists($grabindex)) {
    require_once($grabindex);
}

require_course_login($course);

add_to_log($course->id, "oublog", "view all", "index.php?id=$course->id", "");


$strweek = get_string('week');
$strtopic = get_string('topic');
$strname = get_string('name');
$strdata = get_string('modulename','oublog');
$strdataplural  = get_string('modulenameplural','oublog');

$PAGE->navbar->add($strdata, new moodle_url('/mod/oublog/index.php', array('id'=>$course->id)));
$PAGE->set_title($strdata);
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();

/// Print the list of blogs
if (!$blogs = get_all_instances_in_course('oublog', $course)) {
    notice(get_string('thereareno', 'moodle',$strdataplural) , "$CFG->wwwroot/course/view.php?id=$course->id");
}

// Get the post count
$sql = "SELECT o.id, COUNT(p.id) as postcount
        FROM {oublog} o
        INNER JOIN {oublog_instances} i ON i.oublogid = o.id
        INNER JOIN {oublog_posts} p ON p.oubloginstancesid = i.id
        WHERE o.course = ? AND p.deletedby IS NULL
        GROUP BY o.id ";
$counts = $DB->get_records_sql($sql, array($course->id));


$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');
$strdescription = get_string('blogsummary', 'oublog');
$strentries = get_string('posts', 'oublog');
$table = new html_table();

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname, $strdescription, $strentries);
    $table->align = array ('center', 'center', 'center', 'center');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname, $strdescription, $strentries);
    $table->align = array ('center', 'center', 'center', 'center');
} else {
    $table->head  = array ($strname, $strdescription, $strentries);
    $table->align = array ('center', 'center', 'center');
}

$currentsection = '';

foreach($blogs as $blog) {

    $printsection = '';

    //Calculate the href
    if (!$blog->visible) {
        //Show dimmed if the mod is hidden
        $link = "<a class=\"dimmed\" href=\"view.php?id=$blog->coursemodule\">".format_string($blog->name,true)."</a>";
    } else {
        //Show normal if the mod is visible
        $link = "<a href=\"view.php?id=$blog->coursemodule\">".format_string($blog->name,true)."</a>";
    }

    $numposts = isset($counts[$blog->id]) ? $counts[$blog->id]->postcount : 0;

    if ($course->format == 'weeks' || $course->format == 'topics') {
        if ($blog->section !== $currentsection) {
            if ($blog->section) {
                $printsection = $blog->section;
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $blog->section;
        }
        $row = array ($printsection, $link, format_string($blog->summary, true), $numposts);

    } else {
        $row = array ($link, $blog->summary, $numposts);
    }

    $table->data[] = $row;
}

echo "<br />";
echo html_writer::table($table);
echo $OUTPUT->footer();