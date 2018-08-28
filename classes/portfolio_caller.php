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

namespace mod_oublog;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/portfolio/caller.php');

/**
 * Portfolio callback class for oublog exports.
 *
 * @package mod_oublog
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class portfolio_caller extends \portfolio_module_caller_base {

    protected $postids;
    protected $attachment;
    protected $oublogid;
    protected $cmid;
    protected $cmsharedblogid;

    private $posts;
    protected $files = array();
    private $keyedfiles = array();

    /**
     * Array of arguments the caller expects to be passed through to it.
     * This must be keyed on the argument name, and the array value is a boolean,
     * whether it is required, or just optional
     *
     * @return array
     */
    public static function expected_callbackargs() {
        return array(
            'oublogid' => true,
            'oubloguserid' => true,
            'cmid' => true,
            'cmsharedblogid' => false,
        );
    }

    /**
     * Create portfolio_caller object
     *
     * @param array $callbackargs
     */
    public function __construct($callbackargs) {
        parent::__construct($callbackargs);
        if (!$this->oublogid) {
            throw new portfolio_caller_exception('mustprovidepost', 'oublog');
        }
        $this->postids = explode('|', required_param('ca_postids', PARAM_TEXT));
    }

    /**
     * Load data
     *
     * @global object
     */
    public function load_data() {
        global $DB;
        if (!$this->oublog = $DB->get_record('oublog', array('id' => $this->oublogid))) {
            throw new \portfolio_caller_exception('invalidpostid', 'oublog');
        }
        if (!$this->cm = get_coursemodule_from_instance('oublog', $this->oublogid)) {
            throw new \portfolio_caller_exception('invalidcoursemodule');
        }
        $currentoublog = $this->oublog;
        $currentcm = $this->cm;
        if ($this->cmsharedblogid) {
            // Convert from Child blog to Master.
            $currentoublog = oublog_get_master($this->oublog->idsharedblog);
            $currentcm = get_coursemodule_from_instance('oublog', $currentoublog->id);
        }
        $context = \context_module::instance($currentcm->id);
        $this->posts = oublog_get_posts_by_id($currentoublog, $this->postids);
        $this->modcontext = $context;
        $fs = get_file_storage();
        $this->multifiles = array();
        foreach ($this->posts as $post) {
            $files = array();
            $attach = $fs->get_area_files($this->modcontext->id,
                'mod_oublog', 'attachment', $post->id);
            $embed  = $fs->get_area_files($this->modcontext->id,
                'mod_oublog', 'message', $post->id);
            if (!empty($post->comments)) {
                foreach ($post->comments as $commentpost) {
                    $embedcomments  = $fs->get_area_files($this->modcontext->id,
                        'mod_oublog', 'messagecomment', $commentpost->id);
                    $files = array_merge($files, $embedcomments);
                }
            }
            $files = array_merge($files, $attach, $embed);
            if ($files) {
                $this->keyedfiles[$post->id] = $files;
            } else {
                continue;
            }
            $this->multifiles = array_merge($this->multifiles, $files);
        }
        $this->set_file_and_format_data($this->multifiles);
        if (empty($this->multifiles) && !empty($this->singlefile)) {
            $this->multifiles = array($this->singlefile); // Copy_files workaround.
        }
        // Depending on whether there are files or not, we might have to change richhtml/plainhtml.
        if (!empty($this->multifiles)) {
            $this->add_format(PORTFOLIO_FORMAT_RICHHTML);
        } else {
            $this->add_format(PORTFOLIO_FORMAT_PLAINHTML);
        }
    }

    /**
     * A whole blog from a single post, with or without attachments
     *
     * @global object
     * @global object
     * @uses PORTFOLIO_FORMAT_RICH
     * @return mixed
     */
    public function prepare_package() {
        $plugin = $this->get('exporter')->get('instance')->get('plugin');
        $posttitles = array();
        $postuntitles = array();
        $outputhtml = '';
        // Exporting a set of posts from the view page.
        foreach ($this->posts as $post) {
            $post = oublog_get_post($post->id);
            if ($plugin != 'rtf') {
                $outputhtml = $this->prepare_post($post, true);
                // If post is titled use that as file name for export.
                if ($post->title) {
                    $name = $post->title . '.html';
                } else {
                    $name = get_string('untitledpost', 'oublog') . ' ' .
                        userdate($post->timeposted, '%d-%m-%Y %H-%M-%S');
                    if (in_array($name, $postuntitles)) {
                        $name .= '_' . $post->id;
                    }
                    $postuntitles[] = $name;
                    $name .= '.html';
                }
                // If post title already exists make it unique.
                if (in_array(strtolower($post->title), $posttitles) and $post->title != '' ) {
                    $name = $post->title . ' ' . $post->id . '.html';
                    $post->title = $post->title . ' id ' . $post->id;
                }
            } else {
                // Ensure multiple posts and their comments
                // are included in the html for export.
                $outputhtml .= $this->prepare_post($post, false);
            }
            // Ensure multiple files contained within this post and it's comments
            // are included in the exported file.
            $manifest = ($this->exporter->get('format') instanceof \PORTFOLIO_FORMAT_RICH);
            if (!empty($this->multifiles)) {
                foreach ($this->multifiles as $file) {
                    $this->get('exporter')->copy_existing_file($file);
                }
            }
            if ($plugin != 'rtf') {
                $this->get('exporter')->write_new_file($outputhtml, $name, $manifest);
                $posttitles[] = strtolower($post->title);
            }
        }
        if ($plugin == 'rtf') {
            $name = $this->oublog->name . '.html';
            $this->get('exporter')->write_new_file($outputhtml, $name, $manifest);
        }
    }

    /**
     * @global object
     * @return string
     */
    public function get_return_url() {
        global $CFG;
        return $CFG->wwwroot . '/mod/oublog/view.php?id=' . $this->cmid;
    }

    /**
     * @global object
     * @return array
     */
    public function get_navigation() {
        global $CFG;
        $navlinks = array();
        return array($navlinks, $this->cm);
    }

    /**
     * @return string
     */
    public function get_sha1() {
        $filesha = '';
        if ($this->oublog) {
            return sha1($filesha . ',' . $this->oublog->name . ',' . $this->oublog->intro);
        } else {
            $sha1s = array($filesha);
            foreach ($this->posts as $post) {
                $sha1s[] = sha1($post->title . ',' . $post->message);
            }
            return sha1(implode(',', $sha1s));
        }
    }

    /**
     * @uses CONTEXT_MODULE
     * @return bool
     */
    public function check_permissions() {
        $context = \context_module::instance($this->cmid);
        return (has_capability('mod/oublog:exportpost', $context));
    }

    /**
     * this is a very cut down version of what is in forum_make_mail_post
     *
     * @global object
     * @param int $post
     * @return string
     */
    protected function prepare_post($post, $usehtmls = true) {
        global $PAGE;
        $output = '';
        if ($usehtmls) {
            $output .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" ' .
                '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' .
                \html_writer::start_tag('html', array('xmlns' => 'http://www.w3.org/1999/xhtml'));
            $output .= \html_writer::tag('head',
                \html_writer::empty_tag('meta',
                    array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8')) .
                \html_writer::tag('title', get_string('exportedpost', 'oublog')));
            $output .= \html_writer::start_tag('body') . "\n";
        }
        if (!$oublog = oublog_get_blog_from_postid($post->id)) {
            print_error('invalidpost', 'oublog');
        }
        if (!$cm = get_coursemodule_from_instance('oublog', $oublog->id)) {
            print_error('invalidcoursemodule');
        }
        $oublogoutput = $PAGE->get_renderer('mod_oublog');
        $context = \context_module::instance($cm->id);
        $canmanageposts = has_capability('mod/oublog:manageposts', $context);

        if ($oublog->global) {
            $blogtype = 'personal';
        } else {
            $blogtype = 'course';
        }
        $post->allowcomments = false;
        // Provide format from the exporter to renderers incase its required.
        $format = $this->get('exporter')->get('format');
        $output .= $oublogoutput->render_post($cm, $oublog, $post, false, $blogtype,
            $canmanageposts, false, false, true, $format, false, 'top', $cm);
        if (!empty($post->comments)) {
            $output .= $oublogoutput->render_comments($post, $oublog, false, false, true, $cm, $format);
        }
        if ($usehtmls) {
            $output .= \html_writer::end_tag('body') . \html_writer::end_tag('html');
        }
        return $output;
    }

    public function expected_time() {
        return $this->expected_time_file();
    }

    /**
     * @return string
     */
    public static function display_name() {
        return get_string('modulename', 'oublog');
    }

    public static function base_supported_formats() {
        return array(PORTFOLIO_FORMAT_FILE, PORTFOLIO_FORMAT_RICHHTML, PORTFOLIO_FORMAT_PLAINHTML);
    }
}
