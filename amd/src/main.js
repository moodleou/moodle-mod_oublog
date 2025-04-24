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
 * Main js on mod_oublog plugin.
 *
 * @module      mod_oublog/main
 * @copyright   2024 The Open University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString, getStrings} from 'core/str';
import DeleteModal from 'mod_oublog/modal';
import * as ModalEvents from 'core/modal_events';
import * as FocusLock from 'core/local/aria/focuslock';
import Pending from 'core/pending';
import * as TinyRepository from 'tiny_autosave/repository';

/**
 * Hides or shows the warning marker based on the value of the comment selection.
 */
const hideWarning = () => {
    const pendingPromise = new Pending('mod/oublog:hideWarning');
    const field = document.querySelector('#publicwarningmarker');
    const select = document.querySelector('#id_allowcomments');
    if (field && select) {
        const parent = field.parentNode.parentNode;
        // We need to use !important to override the core CSS.
        parent.style.setProperty('display', select.value == 2 ? 'block' : 'none', 'important');
    }
    pendingPromise.resolve();
};

/**
 * Initializes the OUBlog module, setting up the comment change listener.
 */
const init = () => {
    hideWarning();
    initButtons();
    const comments = document.querySelector('#id_allowcomments');
    if (comments) {
        comments.addEventListener('change', hideWarning);
    }
};

/**
 * Initializes the show/hide functionality for a specific stats view block.
 *
 * @param {string} name - The name of the block to initialize
 * @param {number} curPref - The current preference (1 = hidden, 0 = visible)
 */
const initShowHide = (name, curPref) => {
    const block = document.querySelector(`.oublog_statsview_content_${name}`);
    if (block) {
        const showHide = block.querySelector('.block_action_oublog');
        const form = block.querySelector('form.mform');
        if (showHide && form) {
            const hideInfo = block.querySelector('.oublog_stats_minus');
            const showInfo = block.querySelector('.oublog_stats_plus');

            // Set initial state based on curpref.
            if (curPref === 1) {
                form.classList.add('oublog_displaynone');
                hideInfo.classList.add('oublog_displaynone');
                showInfo.classList.remove('oublog_displaynone');
            }

            const elements = { hideInfo, showInfo, form };

            showHide.addEventListener('click', (e) => {
                curPref = toggleVisibility(e, elements, curPref, name);
            });

            showHide.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    curPref = toggleVisibility(e, elements, curPref, name);
                }
            });
        }
    }
};


/**
 * Toggles the visibility of stats view elements.
 *
 * @param {Event} e - The event triggering the toggle
 * @param {Object} elements - The elements involved in the toggle
 * @param {number} curPref - The current preference (1 = hidden, 0 = visible)
 * @param {string} name - The name of the block (used for setting preferences)
 * @returns {number} - The updated preference
 */
const toggleVisibility = (e, elements, curPref, name) => {
    const pendingPromise = new Pending('mod/oublog:toggleVisibility');
    e.preventDefault();
    const { hideInfo, showInfo, form } = elements;

    if (curPref === 1) {
        hideInfo.classList.remove('oublog_displaynone');
        showInfo.classList.add('oublog_displaynone');
        form.classList.remove('oublog_displaynone');
        curPref = 0;
    } else {
        hideInfo.classList.add('oublog_displaynone');
        showInfo.classList.remove('oublog_displaynone');
        form.classList.add('oublog_displaynone');
        curPref = 1;
    }

    // Update user preference if logged in.
    if (!document.body.classList.contains('notloggedin')) {
        require(['core_user/repository'], (userRepository) => {
            userRepository.setUserPreference(`mod_oublog_hidestatsform_${name}`, curPref);
        });
    }
    pendingPromise.resolve();
    return curPref;
};

/**
 * Initializes the delete and email confirmation dialog for posts.
 *
 * @param {number} cmId - The course module ID
 * @param {number} postId - The ID of the post to handle
 */
const initDeleteAndEmail = (cmId, postId) => {
    const deleteBtn = document.querySelector(`a.oublog_deleteandemail_${postId}`);
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const pendingPromise = new Pending('core/notification:confirm');
            let uri = deleteBtn.href;
            const content = await getString('deleteemailpostdescription', 'oublog');
            const modal = await DeleteModal.create({
                templateContext: {content: content},
            });
            const $root = await modal.getRoot();
            FocusLock.trapFocus(document.querySelector('.modal-dialog.oublog-delete-modal'));

            $root.on(ModalEvents.hidden, function () {
                modal.destroy();
                FocusLock.untrapFocus();
            });

            // Handle delete and deleteandmail event.
            $root.on(ModalEvents.delete, (e) => {
                e.preventDefault();
                uri += '&confirm=1';
                document.location.href = uri;
            });
            $root[0].addEventListener('click', (e) => {
                const deleteAndMail = e.target.closest('[data-action="deleteandmail"]');
                if (deleteAndMail) {
                    e.preventDefault();
                    // Add on the 'email' marker to the link uri.
                    uri += '&email=1';
                    document.location.href = uri;
                    modal.hide();
                    modal.destroy();
                }
            });
            pendingPromise.resolve();
        });
    }
};

/**
 * Updates the preselected value for the given checkbox.
 *
 * @param {HTMLInputElement} check - The checkbox element
 * @param {HTMLInputElement} preSelectInput - The input element storing the preselected values
 */
const updatePreselect = (check, preSelectInput) => {
    const pendingPromise = new Pending('mod/oublog:updatePreselect');
    let preSelect = preSelectInput.value;
    const id = check.name.substring(5);

    if (check.checked) {
        // Add id to preselect value.
        if (id) {
            const preArray = preSelect ? preSelect.split(',') : [];
            if (!preArray.includes(id)) {
                preArray.push(id);
                preSelectInput.value = preArray.join(',');
                updateLinks(preArray);
            }
        }
    } else {
        // De-selecting, remove from preselect.
        if (preSelect && id) {
            const preArray = preSelect.split(',');
            const index = preArray.indexOf(id);
            if (index !== -1) {
                preArray.splice(index, 1);
                preSelectInput.value = preArray.join(',');
                updateLinks(preArray);
            }
        }
    }
    pendingPromise.resolve();
};

/**
 * Updates the links in the table with the latest "preselected" query parameter.
 * Adds or modifies the `preselected` parameter in each link's query string.
 *
 * @param {Array} preArray - The array of preselected IDs
 */
const updateLinks = (preArray) => {
    const pendingPromise = new Pending('mod/oublog:updateLinks');
    const links = document.querySelectorAll('.oublog_import_step1 form .paging a, .flexible a');
    links.forEach((link) => {
        const url = new URL(link.href);
        url.searchParams.set('preselected', preArray.join(','));
        link.href = url.toString();
    });
    pendingPromise.resolve();
};

/**
 * Initializes the post table with select-all and select-none functionality.
 */
const initPostTable = async () => {
    const includeHead = document.querySelector('.flexible .header.c3');
    const postChecks = document.querySelectorAll('.flexible td.c3 input[type="checkbox"]');
    const preSelectInput = document.querySelector('form input[name=preselected]');

    if (includeHead && postChecks) {
        const [
            selectAll,
            none,
        ] = await getStrings([
            'import_step1_all',
            'import_step1_none',
        ].map((key) => ({key, 'component': 'oublog'})));
        includeHead.innerHTML += `
            <a href="#" class="oublog_import_all">${selectAll}</a> /
            <a href="#" class="oublog_import_none">${none}</a>
        `;

        document.querySelector('.flexible .c3 .oublog_import_all').addEventListener('click', (e) => {
            e.preventDefault();
            postChecks.forEach((check) => {
                check.checked = true;
                updatePreselect(check, preSelectInput);
            });
        });

        document.querySelector('.flexible .c3 .oublog_import_none').addEventListener('click', (e) => {
            e.preventDefault();
            postChecks.forEach((check) => {
                check.checked = false;
                updatePreselect(check, preSelectInput);
            });
        });
    }

    if (postChecks && preSelectInput) {
        postChecks.forEach((check) => {
            check.addEventListener('click', () => {
                updatePreselect(check, preSelectInput);
            });
        });
    }
};

/**
 * Removes the auto-saved draft data for the TinyMCE editor.
 */
const initButtons = () => {
    const submitButton = document.getElementById('id_submitbutton');
    if (submitButton) {
        submitButton.addEventListener('click', () => {
            // Get the TinyMCE editor instance.
            const editorID = 'id_message';
            const editor = window.tinyMCE?.get(editorID);

            if (editor) {
                // Remove the auto-save session for the editor.
                TinyRepository.removeAutosaveSession(editor);
            }
        });
    }
};

export { init, initShowHide, initDeleteAndEmail, initPostTable };
