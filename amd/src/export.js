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
 * JavaScript to manage export feature.
 *
 * @package mod_oublog
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module mod_oublog/export
 */

define([
    'jquery'
], function($) {
    var t;
    t = {
        /**
         * List out all of css selector used in export module.
         */
        CSS: {
            SELECTALLPOST: 'button[name="oublog-export-select-all"]',
            SELECTNONE: 'button[name="oublog-export-select-none"]',
            CHECKBOXS: '.oublog-posts-table input[type="checkbox"]',
            EXPORTSELECTED: 'button[name="oublog-export-selected"]',
            EXPORTTYPE: '#oublog-export-type'
        },

        /**
         * Module config. Passed from server side.
         */
        mconfig: null,

        /**
         * Selected post id for export.
         */
        contentIds: [],

        /**
         * Initialize module.
         *
         * @param {JSON} options  The settings for module
         * @method init
         */
        init: function(options) {
            t.mconfig = options;
            if (sessionStorage.getItem("contentIds") !== null && t.mconfig.newsession !== true) {
                t.contentIds = JSON.parse(sessionStorage.getItem("contentIds"));
            }
            $(t.CSS.SELECTALLPOST).on('click', t.selectAll.bind(t));
            $(t.CSS.SELECTNONE).on('click', t.selectNone.bind(t));
            $(t.CSS.CHECKBOXS).on('click', t.selectPost.bind(t));
            $(t.CSS.EXPORTSELECTED).on('click', t.exportSelected.bind(t));
            t.initSelectedPost();
            t.initButtonState();
        },

        /**
         * Select all posts
         * @method selectAll
         */
        selectAll: function() {
            $(t.CSS.CHECKBOXS).prop('checked', true);
            $(t.CSS.SELECTALLPOST).prop("disabled", true);
            $(t.CSS.SELECTALLPOST).blur();
            $(t.CSS.SELECTNONE).prop('disabled', false);
            $(t.CSS.EXPORTSELECTED).prop('disabled', false);
            $(t.CSS.CHECKBOXS).each(function() {
                if ($(this).prop('checked')) {
                    t.addPost($(this).val());
                }
            });
        },

        /**
         * Remove all selected posts
         * @method selectNone
         */
        selectNone: function() {
            $(t.CSS.CHECKBOXS).prop('checked', false);
            $(t.CSS.SELECTALLPOST).prop("disabled", false);
            $(t.CSS.SELECTNONE).prop("disabled", true);
            $(t.CSS.SELECTNONE).blur();
            $(t.CSS.EXPORTSELECTED).prop('disabled', true);
            $(t.CSS.CHECKBOXS).each(function() {
                if (!$(this).prop('checked')) {
                    t.removePost($(this).val());
                }
            });
        },

        /**
         * Select a post
         * @method selectPost
         */
        selectPost: function(event) {
            if (event.target.checked == true) {
                t.addPost(event.target.value);
            } else {
                t.removePost(event.target.value);
            }
            t.initButtonState();
        },

        /**
         * Set checkbox state to checked if user already selected it.
         * @method selectNone
         */
        initSelectedPost: function() {
            $(t.CSS.CHECKBOXS).each(function() {
                var index = t.contentIds.indexOf($(this).val());
                if (index !== -1) {
                    $(this).prop('checked', true);
                }
            });
        },

        /**
         * Export selected posts
         * @method exportSelected
         */
        exportSelected: function() {
            if (t.contentIds.length > 0) {
                var exportUrl = $(t.CSS.EXPORTSELECTED).data("url");
                var exportType = $(t.CSS.EXPORTTYPE).val();
                exportUrl = this.updateQueryStringParameter(exportUrl, 'instance', String(exportType));
                exportUrl = this.updateQueryStringParameter(exportUrl, 'ca_postids', t.contentIds.join('|'));
                sessionStorage.removeItem('contentIds');
                t.contentIds = [];
                window.location.href = exportUrl;
            }
        },

        /**
         * Remove post from sessionStorage and contentIds
         * @method removePost
         */
        removePost: function(postId) {
            var index = t.contentIds.indexOf(postId);
            if (index !== -1) {
                t.contentIds.splice(index, 1);
                sessionStorage.setItem("contentIds", JSON.stringify(t.contentIds));
            }
        },

        /**
         * Add post from sessionStorage and contentIds
         * @method addPost
         */
        addPost: function(postId) {
            var index = t.contentIds.indexOf(postId);
            if (index === -1) {
                t.contentIds.push(postId);
                sessionStorage.setItem("contentIds", JSON.stringify(t.contentIds));
            }
        },

        /**
         * Init the state of all buttons.
         * @method initButtonState
         */
        initButtonState: function() {
            var uncheckBoxes = 0;
            var boxquantity = 0;
            $(t.CSS.CHECKBOXS).each(function() {
                if ($(this).prop('checked') == false) {
                    uncheckBoxes++;
                }
                boxquantity++;
            });
            $(t.CSS.SELECTALLPOST).prop('disabled', uncheckBoxes == 0);
            $(t.CSS.SELECTNONE).prop('disabled', uncheckBoxes == boxquantity);
            $(t.CSS.EXPORTSELECTED).prop('disabled', t.contentIds.length == 0);
        },

        /**
         * Update or add new param to uri.
         *
         * @method updateQueryStringParameter
         * @param {String} uri
         * @param {String} key
         * @param {String} value
         * @returns {string|*}
         */
        updateQueryStringParameter: function(uri, key, value) {
            var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i"),
                separator = uri.indexOf('?') !== -1 ? "&" : "?";
            return uri.match(re) ? uri.replace(re, '$1' + key + "=" + value + '$2') :
                uri + separator + key + "=" + value;
        }
    };
    return t;
});
