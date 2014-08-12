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

M.mod_oublog = M.mod_oublog || {};

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

M.mod_oublog.init_deleteandemail = function(Y, cmid, postid) {
    this.Y = Y;
    this.YAHOO = Y.YUI2;
    // Trap for individual 'Delete' links.
    var delbtns = Y.one('a.oublog_deleteandemail_' + postid);
    delbtns.on('click', function(e) {
        var uri =  e.target.get('href');
        // Show the dialogue.
        delbtns.set('disabled', false);
        var content = M.util.get_string('deleteemailpostdescription', 'oublog');
        var panel = new M.core.dialogue({
            bodyContent: content,
            width: 400,
            centered: true,
            render: true,
            zIndex: 50,
            buttons: {},
            plugins: [Y.Plugin.Drag],
            modal: true});
                // Add the two Delete and Cancel buttons to the bottom of the dialog.
                panel.addButton({
                    label: M.util.get_string('delete', 'oublog'),
                    section: Y.WidgetStdMod.FOOTER,
                    action : function (e) {
                        e.preventDefault();
                        // Add on the 'confirm' delete marker to the link uri.
                        uri += '&confirm=1';
                        document.location.href = uri;
                        panel.hide();
                        panel.destroy();
                    }
                });
                panel.addButton({
                    label: M.util.get_string('deleteandemail', 'oublog'),
                    section: Y.WidgetStdMod.FOOTER,
                    action : function (e) {
                        e.preventDefault();
                        // Add on the 'email' marker to the link uri.
                        uri += '&email=1';
                        document.location.href = uri;
                        panel.hide();
                        panel.destroy();
                    }
                });
                panel.addButton({
                    value  : 'Cancel',
                    section: Y.WidgetStdMod.FOOTER,
                    action : function (e) {
                        e.preventDefault();
                        panel.hide();
                        panel.destroy();
                        Y.one('a.oublog_deleteandemail_' + postid).focus();
                    }
                });
        e.preventDefault();
        Y.one('a.oublog_deleteandemail_' + postid).focus();
        panel.show();
    });

};

/* Import table */
M.mod_oublog.init_posttable = function(Y) {
    var includehead = Y.one('.flexible .header.c3');
    var postchecks = Y.all('.flexible td.c3 input[type=checkbox]');
    if (includehead && postchecks) {
        // Add select all/none links to column header.
        includehead.append('<a href="#" class="oublog_import_all">' + M.util.get_string('import_step1_all', 'oublog') +
                '</a> / <a href="#" class="oublog_import_none">' + M.util.get_string('import_step1_none', 'oublog') + '</a>');
        var all = Y.one('.flexible .c3 .oublog_import_all');
        if (all) {
            all.on('click', function(e) {
                postchecks.set('checked', true);
                postchecks.each(function(check){updatepreselect(check);});
                e.preventDefault();
                return false;
                });
        }
        var none = Y.one('.flexible .c3 .oublog_import_none');
        if (none) {
            none.on('click', function(e) {
                postchecks.set('checked', false);
                postchecks.each(function(check){updatepreselect(check);});
                e.preventDefault();
                return false;});
        }
    }

    var updatepreselect = function(check) {
        var preselect = preselectinput.get('value');
        var id = check.get('name').substr(5);
        if (check.get('checked')) {
         // Add id to preselect value.
            if (id) {
                var prearray = preselect.split(',');
                for (var i = 0, len = prearray.length; i < len; i++) {
                    if (prearray[i] == id) {
                        // Already have, return.
                        return;
                    }
                }
                prearray.push(id);
                preselectinput.set('value', prearray.join());
                updatelinks(prearray);
            }
        } else {
         // De-selecting, remove from preselect.
            if (preselect && id) {
                var prearray = preselect.split(',');
                for (var i = 0, len = prearray.length; i < len; i++) {
                    if (prearray[i] == id) {
                        prearray.splice(i, 1);
                        preselectinput.set('value', prearray.join());
                        updatelinks(prearray);
                        return;
                    }
                }
            }
        }
    };

    var updatelinks = function(prearray) {
        // Update link query strings.
        Y.all('.oublog_import_step1 form .paging a, .flexible a').each(function(link) {
            var linkurl = link.get('href');
            var params = Y.QueryString.parse(linkurl.substr(linkurl.indexOf('&')));
            params.preselected = prearray.join();
            var newurl = Y.QueryString.stringify(params);
            link.set('href', linkurl.substr(0, linkurl.indexOf('&')) + '&' + newurl);
        });
    };

    var preselectinput = Y.one('form input[name=preselected]');
    if (postchecks && preselectinput) {
        postchecks.on('click', function(check) {
            updatepreselect(check.target);
        });
    }
};
