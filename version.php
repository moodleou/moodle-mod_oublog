<?php
// This file is part of Moodle - http://moodle.org//
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
 * Code fragment to define the version of oublog
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 **/

$module->version  = 2012102600;
$module->requires = 2011120100;
$module->cron     = 60*60*4; // 4 hours.

$module->displayversion = 'Unused';
