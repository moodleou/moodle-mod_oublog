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
 * Definition of log events
 *
 * @package    mod
 * @subpackage lesson
 * @copyright  2010 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'oublog', 'action'=> 'add', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=> 'update', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=>'view', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=> 'view all', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=>'add post', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=>'edit post', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=>'add comment', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=> 'extdashadd', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=> 'extdashremove', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=> 'allposts', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=> 'approve comment', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=> 'delete comment', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=> 'delete post', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=> 'view post', 'mtable'=>'oublog', 'field'=>'name'),
    array('module'=>'oublog', 'action'=> 'import post', 'mtable'=>'oublog', 'field'=>'name'),
);