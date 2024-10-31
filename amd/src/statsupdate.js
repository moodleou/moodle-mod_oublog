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

import Pending from 'core/pending';
import * as FormChangeChecker from 'core_form/changechecker';

/**
 * Moodle renderer used to display special elements of the blog
 *
 * @module    mod_oublog/statusupdate
 * @copyright 2024 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

/**
 * Initialise the statsupdate form.
 *
 * @param {string} container_class The class of the container to update.
 */
export const init = (container_class) => {
    let content = document.querySelector('.oublog_statsview_content_' + container_class);
    if (content) {
        const form = content.querySelector('form.mform');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const pendingPromiseAjax = new Pending('mod_oublog/statsupdate');
            // Add an ajax 'spinner'.
            let div = document.createElement('div');
            div.classList.add('ajaxworking');
            let pos = event.target.querySelector('div.row.fitem:last-child div:last-child');
            if (pos) {
                pos.appendChild(div);
            }
            const id = form.querySelector('input[name=id]');
            const currentcmid = form.querySelector('input[name=currentcmid]');
            let args = {
                type: form.querySelector('input[name=type]').value,
                id: 0,
                currentcmid: 0,
            };
            if (id) {
                args.id = id.value;
            }
            if (currentcmid) {
                args.currentcmid = currentcmid.value;
            }
            const formData = new FormData(form);
            fetch('stats_update.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
                .then(response => {
                    statsupdate_killspinner(false);
                    try {
                        if (response.containerclass &&
                            !content.classList.contains(response.containerclass)) {
                            // Mismatch between data and caller.
                            content = document.querySelector('.' + response.containerclass);
                        }
                        if (response.subtitle && response.subtitleclass) {
                            const subtitle = content.querySelector('.' + response.subtitleclass);
                            if (subtitle) {
                                subtitle.innerHTML = response.subtitle;
                            }
                        }
                        if (response.info && response.infoclass) {
                            const info = content.querySelector('.' + response.infoclass);
                            if (info) {
                                info.innerHTML = response.info;
                            }
                        }
                        if ((response.content || response.content === '') && response.contentclass) {
                            const innercontent = content.querySelector('.' + response.contentclass);
                            if (innercontent) {
                                innercontent.innerHTML = response.content;
                            }
                        }
                    } catch (e) {
                        statsupdate_killspinner(true);
                        // eslint-disable-next-line no-console
                        console.log(e);
                        pendingPromiseAjax.resolve();
                        return;
                    }
                    pendingPromiseAjax.resolve();
                })
                .catch(() => {
                    statsupdate_killspinner(true);
                    pendingPromiseAjax.resolve();
                });
            const statsupdate_killspinner = (submit) => {
                const spinner = form.querySelector('.ajaxworking');
                if (spinner) {
                    spinner.remove(true);
                }
                if (submit) {
                    // Manual form submission fallback.
                    FormChangeChecker.disableAllChecks();
                    form.submit();
                }
            };
        });
    }
};
