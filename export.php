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
 * Export posts page.
 *
 * @package mod_oublog
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');
require_once($CFG->dirroot . '/lib/tablelib.php');

// Required params.
$oublogid = required_param('ca_oublogid', PARAM_INT);
$currentgroup = required_param('ca_currentgroup', PARAM_INT);
$currentindividual = required_param('ca_currentindividual', PARAM_INT);
$oubloguserid = required_param('ca_oubloguserid', PARAM_INT);
$canaudit = required_param('ca_canaudit', PARAM_INT);
$cmid = required_param('ca_cmid', PARAM_INT);
$issharedblog = optional_param('ca_issharedblog', 0, PARAM_INT);

// Optional params.
$page = optional_param('page', 0, PARAM_INT);

$offset = $page * OUBLOG_POSTS_PER_PAGE_EXPORT;

// Load efficiently (and with full $cm data) using get_fast_modinfo.
$course = $DB->get_record_select('course',
        'id = (SELECT course FROM {course_modules} WHERE id = ?)', array($cmid),
        '*', MUST_EXIST);
$modinfo = get_fast_modinfo($course);
$cm = $modinfo->get_cm($cmid);
if ($cm->modname !== 'oublog') {
    print_error('invalidcoursemodule');
}
if (!$oublog = $DB->get_record('oublog', array('id' => $cm->instance))) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);
oublog_check_view_permissions($oublog, $context, $cm);
if (empty($CFG->enableportfolios) || !has_capability('mod/oublog:exportpost', $context)) {
    print_error('accessdenied', 'oublog');
}

$oublogoutput = $PAGE->get_renderer('mod_oublog');

$url = new moodle_url('/mod/oublog/export.php',
        array('ca_oublogid' => $oublogid,
                'ca_offset' => $offset,
                'ca_currentgroup' => $currentgroup,
                'ca_currentindividual' => $currentindividual,
                'ca_oubloguserid' => $oubloguserid,
                'ca_canaudit' => $canaudit,
                'ca_cmid' => $cmid,
                'ca_issharedblog' => $issharedblog
        ));
$exporturl = new moodle_url('/portfolio/add.php',
        array('ca_oublogid' => $cm->instance,
                'ca_oubloguserid' => $oubloguserid,
                'ca_cmid' => $cmid,
                'ca_cmsharedblogid' => $issharedblog,
                'sesskey' => sesskey(),
                'callbackcomponent' => 'mod_oublog',
                'callbackclass' => 'oublog_export_portfolio_caller',
                'courseid' => $course->id,
                'callerformats' => 'file,richhtml,plainhtml'
        ));

$PAGE->set_url($url);
$PAGE->set_title(get_string('export:title', 'oublog'));
$PAGE->navbar->add(get_string('export:header', 'oublog'));
$PAGE->set_heading(get_string('export:header', 'oublog'));
$oublogoutput->pre_display($cm, $oublog, 'viewpost');

echo $OUTPUT->header();
echo $oublogoutput->render_export_header($cm, $oublog, 'export');
echo $oublogoutput->render_export_select_button();
echo html_writer::div(get_string('export:description', 'oublog'), 'oublog-export-description');
echo $oublogoutput->render_export_type($exporturl);

$table = new \flexible_table('oublog_export_posts_table');
$table->define_headers(array(
        html_writer::img($OUTPUT->image_url('tick', 'oublog'), '', ['class' => 'export-icon-check']),
        get_string('export:header_title', 'oublog'),
        get_string('export:header_date_posted', 'oublog'),
        get_string('export:header_tags', 'oublog'),
        get_string('export:header_author', 'oublog')
));
$table->define_columns(array('select', 'title', 'timeposted', 'tags', 'author'));
$table->define_baseurl($url);
$table->set_attribute('width', '100%');
$table->set_attribute('class', 'oublog-posts-table');
$table->sortable(true, 'timeposted', SORT_DESC);
$table->no_sorting('select');
$table->no_sorting('tags');
$table->setup();

$orderby = $table->get_sql_sort();
list($listposts, $recordcount) = $oublogoutput->render_table_posts(
        $oublogid, $canaudit, $oubloguserid, $currentindividual, $offset, $currentgroup, $orderby, $issharedblog, $cmid);
foreach ($listposts as $listpost) {
    $table->add_data($listpost);
}

if ($recordcount > OUBLOG_POSTS_PER_PAGE_EXPORT) {
    echo html_writer::start_div('oublog-paging');
    echo $OUTPUT->paging_bar($recordcount, $page, OUBLOG_POSTS_PER_PAGE_EXPORT, $url);
    echo html_writer::end_div();
}

$table->finish_output();

if ($recordcount > OUBLOG_POSTS_PER_PAGE_EXPORT) {
    echo html_writer::start_div('oublog-paging');
    echo $OUTPUT->paging_bar($recordcount, $page, OUBLOG_POSTS_PER_PAGE_EXPORT, $url);
    echo html_writer::end_div();
}

echo $oublogoutput->render_export_select_button();
echo $oublogoutput->render_export_type($exporturl);
echo html_writer::div('', 'clearfix');
$PAGE->requires->js_call_amd('mod_oublog/export', 'init', [["newsession" => !isset($_GET['page'])]]);
echo $OUTPUT->footer();
