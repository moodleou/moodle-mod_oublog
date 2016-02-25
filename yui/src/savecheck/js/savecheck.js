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
 * Check-save functionality for during oublog post attempts.
 *
 * @package   mod_oublog
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_oublog = M.mod_oublog || {};

M.mod_oublog.savecheck = {
    init : function(contextid) {
        // Trap edit saving and test that the server connection is available.
        var btns = Y.all('#id_submitbutton');
        btns.on('click', function(e) {
            function savefail() {
                // Save failed, alert network or session issue.
                btns.set('disabled', true);
                var panel = new M.core.alert({
                    title : M.util.get_string('savefailtitle', 'oublog'),
                    message : M.util.get_string('savefailnetwork', 'oublog'),
                    render : true,
                    plugins : [ Y.Plugin.Drag ],
                    modal : true
                });
                panel.show();
                e.preventDefault();
                // Trap cancel and make it a GET - so works with login.
                var cancel = Y.one('#id_cancel');
                cancel.on('click', function(e) {
                    var form = Y.one('.region-content #mform1');
                    var text = form.one('#fitem_id_message');
                    var attach = form.one('#fitem_id_attachments');
                    text.remove();
                    attach.remove();
                    form.set('method', 'get');
                });
            }
            function checksave(transactionid, response) {
                // Check response OK.
                if (response.responseText !== 'ok') {
                    // Send save failed due to login/session error.
                    savefail();
                }
            }
            var cfg = {
                method : 'POST',
                data : 'sesskey=' + M.cfg.sesskey + '&contextid=' + contextid,
                on : {
                    success : checksave,
                    failure : savefail
                },
                sync : true,// Wait for result so we can cancel submit.
                timeout : 10000
            };
            Y.io('confirmloggedin.php', cfg);
        });
    }
};
