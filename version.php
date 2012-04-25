<?php
/**
 * Code fragment to define the version of oublog
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @author Sam Marshall <s.marshall@open.ac.uk>
 * @package oublog
 **/

$module->version  = 2012042500;
$module->requires = 2010121400;
$module->cron     = 60*60*4; // 4 hours

$module->displayversion = 'Unused';
