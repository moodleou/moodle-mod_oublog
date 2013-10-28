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
 * Javascript helper function for OUBlog.
 *
 * @package    mod
 * @subpackage oublog
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_oublog = {};

M.mod_oublog.init = function(Y) {
    M.mod_oublog.hidewarning(Y);
    var comments = Y.one('#id_allowcomments');
    if (comments) {
        comments.on('change', function(e){M.mod_oublog.hidewarning(Y);});
    }
};

M.mod_oublog.hidewarning = function(Y) {
    var field = Y.one('#publicwarningmarker');
    if (field) {
        field = field.get('parentNode').get('parentNode');
        var select = Y.one('#id_allowcomments');
        field.setStyle('display', select.get('value') == 2 ? 'block' : 'none');
    }
};

/*Discovery 'block'*/

M.mod_oublog.init_showhide = function(Y, name, curpref) {
    var block = Y.one('.oublog_statsview_content_' + name);
    if (block) {
        var showhide = block.one('.block_action_oublog');
        var form = block.one('form.mform');
        if (showhide && form) {
            var hideinfo = block.one('.oublog_stats_minus');
            var showinfo = block.one('.oublog_stats_plus');
            if (curpref == 1) {
                form.addClass('oublog_displaynone');
                hideinfo.addClass('oublog_displaynone');
                showinfo.removeClass('oublog_displaynone');
            }
            showhide.on(['click', 'keypress'], function(e) {
                if (!e.keyCode || (e.keyCode && e.keyCode == 13)) {
                    e.preventDefault();
                    if (curpref == 1) {
                        hideinfo.removeClass('oublog_displaynone');
                        showinfo.addClass('oublog_displaynone');
                        form.removeClass('oublog_displaynone');
                        if (!Y.one('body').hasClass('notloggedin')) {
                            M.util.set_user_preference('mod_oublog_hidestatsform_' + name, 0);
                        }
                        curpref = 0;
                    } else {
                        hideinfo.addClass('oublog_displaynone');
                        showinfo.removeClass('oublog_displaynone');
                        form.addClass('oublog_displaynone');
                        if (!Y.one('body').hasClass('notloggedin')) {
                            M.util.set_user_preference('mod_oublog_hidestatsform_' + name, 1);
                        }
                        curpref = 1;
                    }
                }
            });
        }
    }
};
