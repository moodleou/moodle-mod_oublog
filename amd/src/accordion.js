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

import Config from 'core/config';

/*
 * Handle expand/collapse of areas within the blog usage block.
 *
 * @package mod_oublog/accordion
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Unique id for each accordion.
let id = 0;

/**
 * Initialize the accordion.
 *
 * @param {string} containerClass The class of the container to apply the accordion to.
 * @param {number} defaultOpen The default tab to open.
 * @return void
 */
export const init = (containerClass, defaultOpen) => {
    const container = document.querySelector('ul.oublog-accordion-' + containerClass);
    if (!container) {
        return;
    }
    container.setAttribute('tabindex', 0);
    let counter = 1;
    // Setup UI.
    const tabs = container.querySelectorAll('li');
    if (!defaultOpen || tabs.length < defaultOpen) {
        defaultOpen = 1;
    }

    // Initialize event for each tab.
    tabs.forEach((tab) => {
        const generateID = getNextId();
        // Set data set for each tab element.
        tab.dataset.containerClass = containerClass;
        tab.dataset.number = counter;
        // Set the state of the tab.
        // State 1 = open, 0 = closed.
        tab.dataset.state = 1;

        // Set the title and content of the tab.
        const title = tab.querySelector('.oublog_statsview_title h2');
        title.style.userSelect = 'none';
        title.setAttribute('aria-controls', generateID);
        title.setAttribute('role', 'tab');
        title.setAttribute('tabindex', 0);
        const content = tab.querySelector('.oublog_statsview_content');
        content.setAttribute('aria-labeledby', generateID);
        content.setAttribute('role', 'tabpanel');

        // Set the default open tab.
        if (parseInt(defaultOpen) !== counter) {
            closeTab(tab, content);
        } else {
            // Manual open to stop others being closed.
            content.classList.add('oublog-accordion-open');
            tab.classList.add('oublog-accordion-open');
        }

        // Add event listener for the click event.
        title.addEventListener('click', (e) => {
            e.preventDefault();
            toggleAccordion(tab, content);
        });

        // Add event listener for the enter key.
        title.addEventListener('keydown', (e) => {
            if (e.keyCode === 13) {
                e.preventDefault();
                toggleAccordion(tab, content);
            }
        });
        counter++;
    });

    // Keep track of the open tab for current user by focusing on the tab.
    const headings = container.querySelectorAll('.oublog_statsview_title h2');
    headings.forEach((heading) => {
        heading.addEventListener('focus', (e) => {
            e.target.parentNode.classList.add('focus');
        });
    });

    // Navigate through the tabs using the up and down arrow keys.
    container.addEventListener('keydown', (e) => {
        const focusedElement = document.activeElement;
        const index = [...headings].indexOf(focusedElement);

        // Handle 'up' key (keyCode 38).
        if (e.keyCode === 38 && focusedElement.nodeName === 'H2') {
            e.preventDefault();
            if (index > 0) {
                headings[index - 1].focus();
            } else {
                headings[headings.length - 1].focus();
            }
        }

        // Handle 'down' key (keyCode 40).
        if (e.keyCode === 40 && focusedElement.nodeName === 'H2') {
            e.preventDefault();
            if (index === headings.length - 1) {
                headings[0].focus();
            } else {
                headings[index + 1].focus();
            }
        }
    });
};

/**
 * Toggle the accordion.
 *
 * @param {HTMLDivElement} tab The tab to close.
 * @param {HTMLDivElement} content The content of the tab.
 * @return void
 */
const toggleAccordion = (tab, content) => {
    return parseInt(tab.dataset.state) === 1 ?
        closeTab(tab, content) :
        openTab(tab, content);
};

/**
 * Returns the unique id.
 *
 * @return {string} New unique id
 */
const getNextId = () => {
    return 'oublog_accordion_' + id++;
};

/**
 * Close a tab.
 *
 * @param {HTMLDivElement} tab The tab to close.
 * @param {HTMLDivElement} content The content of the tab.
 * @return void
 */
const closeTab = (tab, content) => {
    tab.classList.remove('oublog-accordion-open');
    tab.classList.add('oublog-accordion-closed');
    content.classList.remove('oublog-accordion-open');
    content.classList.add('oublog-accordion-closed');
    tab.dataset.state = 0;
};

/**
 * Open a tab.
 *
 * @param {HTMLDivElement} tab The tab to open.
 * @param {HTMLDivElement} content The content of the tab.
 * @return void
 */
const openTab = (tab, content) => {
    // Shut all other tabs.
    const tabs = tab.parentNode.querySelectorAll('li.oublog-accordion-open');
    tabs.forEach((tabRef) => {
        const content = tabRef.querySelector('.oublog_statsview_content');
        closeTab(tabRef, content);
    });

    tab.classList.remove('oublog-accordion-closed');
    tab.classList.add('oublog-accordion-open');
    content.classList.remove('oublog-accordion-closed');
    content.classList.add('oublog-accordion-open');
    tab.dataset.state = 1;

    // Keep track of the open tab for current user.
    if (!document.body.classList.contains('notloggedin')) {
        setUserPreference('oublog_accordion_' + tab.dataset.containerClass + '_open', tab.dataset.number);
    }
};

/**
 * Set the user preference.
 *
 * @param {string} name The name of the preference.
 * @param {string} value The value of the preference.
 * @return {void}
 */
export const setUserPreference = (name, value) => {
    const url = new URL(Config.wwwroot + '/lib/ajax/setuserpref.php');
    url.searchParams.set('sesskey', Config.sesskey);
    url.searchParams.set('pref', encodeURI(name));
    url.searchParams.set('value', encodeURI(value));
    // Set the user preference by making a request.
    fetch(url)
        .then(res => {
            // For debugging purposes.
            if (Config.developerdebug && !res.ok) {
                window.console.error(res);
            }
        }).catch((error) => {
            // For debugging purposes.
            if (Config.developerdebug) {
                window.console.error(error);
                // eslint-disable-next-line max-len
                alert("Error updating user preference '" + name + "' using ajax. Clicking this link will repeat the Ajax call that failed so you can see the error: ");
            }
        });
};
