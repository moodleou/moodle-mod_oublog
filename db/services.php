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
 * Web service definition.
 *
 * @package mod_oublog
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
        'mod_oublog_get_user_blogs' => array(
                'classname' => 'mod_oublog_external',
                'methodname' => 'get_user_blogs',
                'classpath' => 'mod/oublog/externallib.php',
                'description' => 'Get all user\'s blogs on system',
                'type' => 'read',
        ),
        'mod_oublog_get_blog_info' => array(
                'classname' => 'mod_oublog_external',
                'methodname' => 'get_blog_info',
                'classpath' => 'mod/oublog/externallib.php',
                'description' => 'Get info on blog, inc access check',
                'type' => 'read',
        ),
        'mod_oublog_get_blog_allposts' => array(
                'classname' => 'mod_oublog_external',
                'methodname' => 'get_blog_allposts',
                'classpath' => 'mod/oublog/externallib.php',
                'description' => 'Get importable user posts from blog',
                'type' => 'read',
        ),
        'mod_oublog_get_blog_posts' => array(
                'classname' => 'mod_oublog_external',
                'methodname' => 'get_blog_posts',
                'classpath' => 'mod/oublog/externallib.php',
                'description' => 'Get selected user posts from blog',
                'type' => 'read',
        ),

);

$services = array(
        'OUBlog import' => array(
                'shortname' => 'oublogimport',
                'functions' => array ('mod_oublog_get_user_blogs', 'mod_oublog_get_blog_info',
                        'mod_oublog_get_blog_allposts', 'mod_oublog_get_blog_posts'),
                'requiredcapability' => '',
                'restrictedusers' => 1,
                'enabled' => 1,
                'downloadfiles' => 1
        )
);
