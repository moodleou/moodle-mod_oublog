<?php
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
    print_error('invalidlink','oublog');
}
if (!$oublog = $DB->get_record("oublog", array("id"=>$link->oublogid))) {
    print_error('invalidblog','oublog');
}
if (!$cm = get_coursemodule_from_instance('oublog', $link->oublogid)) {
    print_error('invalidcoursemodule');
}

require_sesskey();

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

$oubloginstance = $link->oubloginstancesid ? $DB->get_record('oublog_instances', array('id'=>$link->oubloginstancesid)) : null;
oublog_require_userblog_permission('mod/oublog:managelinks', $oublog,$oubloginstance,$context);

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

        $DB->execute($sql, array($link->sortorder+1,$link->id));
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
