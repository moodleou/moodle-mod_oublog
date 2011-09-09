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
 * Javascript helper function for IMS Content Package module.
 *
 * @package    mod
 * @subpackage oublog
 * @copyright  2011 Dan Marsden  {@link http://danmarsden.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var oublog = {};
function oublog_init() {
    oublogHideWarning(); //first check if need to hide onload
    YAHOO.util.Event.addListener(document.getElementById('id_allowcomments'), 'change', oublogHideWarning);
}
function oublogHideWarning() {
    var field = document.getElementById('publicwarningmarker').parentNode.parentNode;
    var select = document.getElementById('id_allowcomments');
    field.style.display = select.value == 2 ? 'block' : 'none';
}
YAHOO.util.Event.onDOMReady(oublog_init);
