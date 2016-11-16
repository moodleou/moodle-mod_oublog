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
 * This page allows a user to change a links position in the list
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */

require_once("../../config.php");
require_once("locallib.php");

$link = required_param('link', PARAM_INT);
$down = required_param('down', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_RAW);

if (!$link = $DB->get_record('oublog_links', array('id'=>$link))) {
    print_error('invalidlink', 'oublog');
}
if (!$oublog = $DB->get_record("oublog", array("id"=>$link->oublogid))) {
    print_error('invalidblog', 'oublog');
}
if (!$cm = get_coursemodule_from_instance('oublog', $link->oublogid)) {
    print_error('invalidcoursemodule');
}

require_sesskey();

$context = context_module::instance($cm->id);

$oubloginstance = $link->oubloginstancesid ? $DB->get_record('oublog_instances', array('id'=>$link->oubloginstancesid)) : null;
oublog_require_userblog_permission('mod/oublog:managelinks', $oublog, $oubloginstance, $context);

$params = array();
if ($oublog->global) {
    $where = "oubloginstancesid = ? ";
    $params[] = $link->oubloginstancesid;
} else {
    $where = "oublogid = ? ";
    $params[] = $link->oublogid;
}

// Get the max sort order
$maxsortorder = $DB->get_field_sql("SELECT MAX(sortorder) FROM {oublog_links} WHERE $where", $params);

if ($down == 1) { // Move link down
    if ($link->sortorder != $maxsortorder) {
        $sql = "UPDATE {oublog_links} SET sortorder = ?
                WHERE $where AND sortorder = ?";

        $DB->execute($sql, array_merge(array($link->sortorder), $params, array($link->sortorder+1)));

        $sql = "UPDATE {oublog_links} SET sortorder = ?
                WHERE id = ? ";

        $DB->execute($sql, array($link->sortorder+1, $link->id));
    }
} else { // Move link up
    if ($link->sortorder != 1) {
        $sql = "UPDATE {oublog_links} SET sortorder = ?
                WHERE $where AND sortorder = ?";

        $DB->execute($sql, array_merge(array($link->sortorder), $params, array($link->sortorder-1)));

        $sql = "UPDATE {oublog_links} SET sortorder = ?
                WHERE id = ? ";

        $DB->execute($sql, array($link->sortorder-1, $link->id));
    }
}

redirect($returnurl);
