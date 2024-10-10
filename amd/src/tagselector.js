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
 * JS for tag selector on mod_oublog plugin.
 *
 * @module      mod_oublog/tagselector
 * @copyright   2024 The Open University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

class TagSelector {
    /**
     * Initializes the TagSelector instance.
     *
     * @param {string} inputId - The ID of the input element
     * @param {Array} data - The data array of objects to be used in autocomplete
     */
    constructor(inputId, data) {
        this.input = document.getElementById(inputId);
        this.data = data;
        this.filteredData = [...data];

        this.dropdownWrapper = this.createDropdownWrapper();
        this.dropdown = this.createDropdown();
        this.setAttributes(this.input, {
            'aria-owns': this.dropdown.id,
            'aria-expanded': 'false',
            'aria-autocomplete': 'list',
            'role': 'combobox',
        });
        this.activeIndex = -1;

        this.attachInputEvents();
    }

    /**
     * Creates the dropdown wrapper element.
     *
     * @returns {HTMLElement} - The created dropdown wrapper element
     */
    createDropdownWrapper = () => {
        const wrapper = document.createElement('div');
        wrapper.className = 'autocomplete-dropdown-wrapper';
        document.body.appendChild(wrapper);
        return wrapper;
    };

    /**
     * Creates the dropdown element.
     *
     * @returns {HTMLElement} - The created dropdown element
     */
    createDropdown = () => {
        const dropdown = document.createElement('ul');
        dropdown.className = 'autocomplete-dropdown';
        this.setAttributes(dropdown, {
            'id':  this.input.id + '_results',
            'role': 'listbox',
            'tabindex': 0,
        });

        this.dropdownWrapper.appendChild(dropdown);
        return dropdown;
    };

    /**
     * Attaches event listeners for input interactions.
     */
    attachInputEvents = () => {
        this.input.addEventListener('focus', () => this.onFocus());
        this.input.addEventListener('input', () => this.onInput());
        this.input.addEventListener('keydown', (e) => this.onKeyDown(e));
        this.input.addEventListener('blur', () => this.onBlurInput());
        this.dropdown.addEventListener('click', (e) => this.onDropdownItemClick(e));
        document.addEventListener('click', (e) => this.onDocumentClick(e));
        window.addEventListener('resize', () => this.updateDropdownPosition());
    };

    /**
     * Handles input focus event.
     */
    onFocus = () => {
        this.filterData();
        this.updateDropdownPosition();
        this.showDropdown();
    };

    /**
     * Handles input event to filter data.
     */
    onInput = () => {
        // Data for filter got by the latest portion after the last comma.
        this.filterData(this.getLastInputPortion());
        this.updateDropdownPosition();
        this.showDropdown();
    };

    /**
     * Handles keydown event for keyboard navigation.
     *
     * @param {KeyboardEvent} event - The keydown event
     */
    onKeyDown = (event) => {
        const items = this.dropdown.querySelectorAll('.autocomplete-item');
        if (items.length === 0) {
            return;
        }
        switch (event.key) {
            case 'ArrowDown': this.navigateDown(items); break;
            case 'ArrowUp': this.navigateUp(items); break;
            case 'Escape': this.hideDropdown(); break;
            case 'Enter':
                event.preventDefault();
                this.selectItem(items); break;
            default: break;
        }
    };

    /**
     * Navigates down the dropdown items.
     *
     * @param {NodeList} items - The list of dropdown items
     */
    navigateDown = (items) => {
        this.activeIndex = (this.activeIndex + 1) % items.length;
        this.setActiveItem(this.activeIndex);
    };

    /**
     * Navigates up the dropdown items.
     *
     * @param {NodeList} items - The list of dropdown items
     */
    navigateUp = (items) => {
        this.activeIndex = (this.activeIndex - 1 + items.length) % items.length;
        this.setActiveItem(this.activeIndex);
    };

    /**
     * Selects the currently active item in the dropdown.
     *
     * @param {NodeList} items - The list of dropdown items
     */
    selectItem = (items) => {
        if (this.activeIndex >= 0) {
            this.insertItem({ target: items[this.activeIndex] });
        }
    };

    /**
     * Handles dropdown item click event.
     *
     * @param {Event} event - The click event
     */
    onDropdownItemClick = (event) => {
        this.insertItem(event);
    };

    /**
     * Closes the dropdown when blur input element.
     */
    onBlurInput = () => {
        setTimeout(() => {
            const isInput = this.input === document.activeElement;
            const isDropdown = this.dropdownWrapper.contains(document.activeElement);
            if (!isInput && !isDropdown) {
                this.hideDropdown();
            }
        }, 100);
    };

    /**
     * Closes the dropdown when clicking outside.
     *
     * @param {Event} e - The click event
     */
    onDocumentClick = (e) => {
        if (!this.input.contains(e.target) && !e.target.closest('.autocomplete-item')) {
            this.hideDropdown();
        }
    };

    /**
     * Shows the dropdown element.
     */
    showDropdown = () => {
        this.dropdownWrapper.style.display = 'block';
        this.setAttributes(this.input, {
            'aria-expanded': 'true',
        });
    };

    /**
     * Hides the dropdown element.
     */
    hideDropdown = () => {
        this.dropdownWrapper.style.display = 'none';
        this.setAttributes(this.input, {
            'aria-expanded': 'false',
            'aria-activedescendant': '',
        });
        this.activeIndex = -1;
    };

    /**
     * Highlights the portion of the suggestion that matches the user's query by wrapping the matching
     * characters in a <strong> tag to apply bold formatting in the HTML output.
     *
     * @param {string} suggestion - The full suggestion text from the list of possible autocomplete options
     * @returns {string} - The suggestion string with the matching query portion wrapped in <strong> tags. If no match is found,
     *                     the original suggestion is returned without modification
     */
    highlightMatch = (suggestion) => {
        let query = this.getLastInputPortion();
        const startIdx = suggestion.toLowerCase().indexOf(query.toLowerCase());
        const endIdx = startIdx + query.length;
        return suggestion.slice(0, startIdx) +
            '<strong>' + suggestion.slice(startIdx, endIdx) + '</strong>' +
            suggestion.slice(endIdx);
    };

    /**
     * Filters the data based on the current input value.
     *
     * @param {string} query - The query string typed by the user that will be matched in the suggestion
     */
    filterData = (query= '') => {
        let existingValues = this.cleanInputValue().split(',').map(value => value.trim()).filter(value => value !== '');
        this.filteredData = this.data.filter(item => item.tag.toLowerCase().includes(query.toLowerCase())
            && !existingValues.includes(item.tag));
        this.renderDropdown();
    };

    /**
     * Cleans up the input value for filtering.
     *
     * @returns {string} - The cleaned input value
     */
    cleanInputValue = () => {
        let value = this.input.value.trim();
        if (value.endsWith(',')) {
            value = value.slice(0, -1).trim();
        }
        return value;
    };

    /**
     * Retrieves the latest portion of text from the input value after the last comma.
     *
     * @returns {string} - The portion of text after the last comma, trimmed of extra spaces
     */
     getLastInputPortion = () => {
        return this.input.value.trim().split(',').pop().trim();
    };

    /**
     * Renders the filtered data in the dropdown.
     */
    renderDropdown = () => {
        this.dropdown.innerHTML = '';
        this.filteredData.forEach(item => {
            const li = document.createElement('li');
            li.className = 'autocomplete-item';
            this.setAttributes(li, {
                'role': 'option',
                'tabindex': -1,
                'id': `autocomplete-item-${item.id}`,
                'aria-selected': 'false',
            });

            const resultDiv = document.createElement('div');
            resultDiv.className = 'autocomplete_result';
            const titleSpan = document.createElement('span');
            titleSpan.className = 'autocomplete_result_title';
            titleSpan.innerHTML = this.highlightMatch(item.tag);
            resultDiv.appendChild(titleSpan);

            const countSpan = document.createElement('span');
            countSpan.className = 'autocomplete_result_info';
            getString('numposts', 'oublog', item.count).then((numpost) => {
                countSpan.textContent = numpost;
            });
            resultDiv.appendChild(countSpan);

            li.appendChild(resultDiv);
            this.dropdown.appendChild(li);
        });
    };

    /**
     * Sets the active (highlighted) item in the dropdown.
     *
     * @param {number} index - The index of the active item
     */
    setActiveItem = (index)  => {
        const items = this.dropdown.querySelectorAll('.autocomplete-item');
        items.forEach((item, i) => {
            const isActive = i === index;
            this.setAttributes(item, {
                'aria-selected': isActive ? 'true' : 'false',
            });
            item.classList.toggle('active', isActive);
            if (isActive) {
                this.setAttributes(this.input, {
                    'aria-activedescendant': item.id,
                });
            }
        });
    };

    /**
     * Inserts the selected item into the input field.
     *
     * @param {Event} event - The event that triggered the insertion
     */
    insertItem = (event) => {
        const selectedItemTag = event.target.closest('.autocomplete-item').querySelector('.autocomplete_result_title').textContent;
        if (!selectedItemTag) {
            return;
        }

        let currentValue = this.input.value.trim();
        let inputValueParts = currentValue.split(',').map(item => item.trim());
        inputValueParts[inputValueParts.length - 1] = selectedItemTag;

        this.input.value = inputValueParts.join(', ') + ', ';
        this.input.focus();
        this.filterData();
        this.activeIndex = -1;
    };

    /**
     * Updates the dropdown position relative to the input field.
     */
    updateDropdownPosition = () => {
        const rect = this.input.getBoundingClientRect();
        const scrollTop = document.documentElement.scrollTop;

        this.dropdownWrapper.style.position = 'absolute';
        this.dropdownWrapper.style.top = `${rect.bottom + scrollTop}px`;
        this.dropdownWrapper.style.left = `${rect.left}px`;
        this.dropdownWrapper.style.width = `${rect.width}px`;
    };

    /**
     * Set attributes to element.
     *
     * @param {HTMLElement} el
     * @param {Object} object
     */
    setAttributes = (el, object) => {
        for (let key in object) {
            el.setAttribute(key, object[key]);
        }
    };

}

/**
 * Initializes an TagSelector instance for a given input element.
 *
 * @param {String} inputID - The ID of the input element where the TagSelector will be attached
 * @param {Object|Array} data - An array of data items to be used for the TagSelector suggestions
 * @returns {TagSelector} - An instance of the TagSelector class initialized with the given input element and data
 */
export const init = (inputID, data) => {
    // Ensure data is converted to an array if it's an object.
    const dataArray = Array.isArray(data) ? data : Object.values(data);
    return new TagSelector(inputID, dataArray);
};
