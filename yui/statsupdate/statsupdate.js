YUI.add('moodle-mod_oublog-statsupdate', function(Y) {
    M.mod_oublog = M.mod_oublog || {};
    M.mod_oublog.statsupdate = {
            // Override option forms and make them submit via ajax to dynamically update container.
            init: function(container_class) {
                var content = Y.one('.oublog_statsview_content_' + container_class);
                if (content) {
                    var form = content.one('form.mform');
                    form.on('submit', function(e){
                        e.preventDefault();
                        var cfg = {
                                method: 'POST',
                                on: {
                                    start: function() {
                                        // Add an ajax 'spinner'.
                                        var submit = form.one('.form-inline');
                                        submit.append('<div class="ajaxworking" />');
                                    },
                                    success: function(transactionid, o) {
                                        statsupdate_killspinner(false);
                                        if (o.responseText) {
                                            // Process the JSON data returned from the server.
                                            try {
                                                var response = Y.JSON.parse(o.responseText);
                                                if (response.error) {
                                                    statsupdate_killspinner(true);
                                                    return;
                                                }
                                                if (response.containerclass &&
                                                        !content.hasClass(response.containerclass)) {
                                                    // Mismatch between data and caller.
                                                    content = Y.one('.' + response.containerclass);
                                                }
                                                if (response.subtitle && response.subtitleclass) {
                                                    var subtitle = content.one('.' + response.subtitleclass);
                                                    if (subtitle) {
                                                        subtitle.set('innerHTML', response.subtitle);
                                                    }
                                                }
                                                if (response.info && response.infoclass) {
                                                    var info = content.one('.' + response.infoclass);
                                                    if (info) {
                                                        info.set('innerHTML', response.info);
                                                    }
                                                }
                                                if ((response.content || response.content == '')
                                                        && response.contentclass) {
                                                    var innercontent = content.one('.' + response.contentclass);
                                                    if (innercontent) {
                                                        innercontent.set('innerHTML', response.content);
                                                        // We need to call dependant js.
                                                        if (response.containerclass &&
                                                                response.content.indexOf('oublog_statsinfo_bar') &&
                                                                M.mod_oublog.statsbar) {
                                                            M.mod_oublog.statsbar.init(response.containerclass);
                                                        }
                                                    }
                                                }
                                            } catch (e) {
                                                statsupdate_killspinner(true);
                                                return;
                                            }
                                        } else {
                                            statsupdate_killspinner(true);
                                            return;
                                        }
                                    },
                                    failure: function() {
                                        statsupdate_killspinner(true);
                                    }
                                },
                                form: {
                                    id: form
                                }
                        };
                        var uri = M.cfg.wwwroot + '/mod/oublog/stats_update.php';
                        Y.io(uri, cfg);
                        var statsupdate_killspinner = function(submit) {
                            var spinner = form.one('.ajaxworking');
                            if (spinner) {
                                spinner.remove(true);
                            }
                            if (submit) {
                                // Manual form submission fallback.
                                form.submit();
                            }
                        };
                        return;
                        });
                }
            }
        };
    }, '@VERSION@', {requires: ['node', 'io', 'io-form', 'json-parse']}
);
