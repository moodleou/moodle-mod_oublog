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
 * JS for save check on mod_oublog plugin.
 *
 * @module      mod_oublog/savecheck
 * @copyright   2024 The Open University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import Notification from 'core/notification';
import Config from 'core/config';
import Pending from 'core/pending';
import * as FormChangeChecker from 'core_form/changechecker';

class SaveCheck {
    /**
     * Constructor to initialize SaveCheck with the given context ID.
     *
     * @param {number} contextId - The context ID used for server validation
     */
    constructor(contextId) {
        this.contextId = contextId;
        this.init();
    }

    /**
     * Initialize event listeners for save buttons.
     */
    init = () => {
        const btns = document.querySelectorAll('#id_submitbutton');
        btns.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                this.handleButtonClick(e, btns);
            });
        });
    };

    /**
     * Handle the click event of save buttons.
     *
     * @param {Event} e - The click event
     * @param {NodeList} btns - The list of save buttons
     */
    handleButtonClick = (e, btns) => {
        e.preventDefault();
        const pendingPromise = new Pending('mod/oublog:savecheck');
        this.sendCheckRequest(
            (response) => {
                this.checkSave(response, e, btns);
                pendingPromise.resolve();
            },
            (error) =>  {
                this.checkFailure(error, btns);
                pendingPromise.resolve();
            }
        );

    };

    /**
     * Handle save failure scenario by displaying an alert and disabling buttons.
     *
     * @param {string} stringName - The name of the failure string
     * @param {string} info - Additional info for the alert
     * @param {NodeList} btns - The list of save buttons
     */
    saveFail = async (stringName, info, btns) => {
        let content = await getString('savefailtext', 'oublog', await getString(stringName, 'oublog'));
        if (info) {
            content += `[${info}]`;
        }

        btns.forEach(btn => {
            btn.disabled = true;
        });

        Notification.alert(await getString('savefailtitle', 'oublog'), content);
        const cancel = document.querySelector('#id_cancel');
        if (cancel) {
            cancel.addEventListener('click', () => {
                const form = document.querySelector('#region-main .mform');
                if (form) {
                    const text = form.querySelector('#fitem_id_message');
                    const attach = form.querySelector('#fitem_id_attachments');
                    if (text) {
                        text.remove();
                    }
                    if (attach) {
                        attach.remove();
                    }
                    form.method = 'get';
                }
            });
        }
    };

    /**
     * Check the save response for success and handle failure if necessary.
     *
     * @param {XMLHttpRequest} response - The XMLHttpRequest response
     * @param {Event} e - The click event
     * @param {NodeList} btns - The list of save buttons
     */
    checkSave = (response, e, btns) => {
        if (response.responseText.search('ok') === -1) {
            // Send save failed due to login/session error.
            this.saveFail('savefailsession', response.responseText, btns);
        } else {
            // If the response is OK, allow form submission.
            const form = e.target.closest('form');
            if (form) {
                // Manually trigger the form submission.
                // For the first submission, this form will be marked as "submitted."
                // Therefore, when calling submit again,
                // we need to invoke this function to prevent the form from being detected as dirty.
                FormChangeChecker.disableAllChecks();
                form.submit();
            }
        }
    };

    /**
     * Handle network or timeout failures for save request.
     *
     * @param {Error} error - The error object
     * @param {NodeList} btns - The list of save buttons
     */
    checkFailure = (error, btns) => {
        this.saveFail('savefailnetwork', error.statusText, btns);
    };

    /**
     * Send an XMLHttpRequest to verify session status.
     *
     * @param {Function} onSuccess - Callback function on successful response
     * @param {Function} onFailure - Callback function on error/timeout
     */
    sendCheckRequest = (onSuccess, onFailure) => {
        const xhr = new XMLHttpRequest();
        const params = `sesskey=${Config.sesskey}&contextid=${this.contextId}`;
        xhr.open('POST', 'confirmloggedin.php', true);
        xhr.timeout = 30000;

        xhr.onreadystatechange = () => {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    onSuccess(xhr);
                } else {
                    onFailure(xhr);
                }
            }
        };

        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(params);
    };
}

/**
 * Initializes an SaveCheck instance for a given input element.
 *
 * @param {Number} contextId - The context id
 * @returns {SaveCheck} - An instance of the SaveCheck class initialized with the given input element and data
 */
export const init = (contextId) => {
    return new SaveCheck(contextId);
};
