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
 * Extend the datetimeselector and dateselector form controls with a timezone awareness indicator.
 *
 * This basically adds a "badge" element when the course is known be delivered in a different time zone from the
 * server timezone.
 *
 * @module     local_strath/dateselector-tz
 * @copyright  2025 University of Strathclyde
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import log from 'core/loglevel';
import Templates from 'core/templates';
import { get_strings } from "core/str";

/**
 * Initialize the timezone awareness indicator.
 *
 * Adds a badge to date/time selectors when the course is in a different timezone
 * than the server.
 * @param {string} tone Tone
 * @param {boolean} isDifferentTimezone - Whether the course timezone differs from server timezone
 * @param {string} courseTimezone - The timezone of the course
 * @param {string} userTimezone - The timezone of the user
 * @param {string} serverTimezone - The timezone of the server
 * @param {boolean} isDifferentServerTimezone
 * @param {boolean} isDifferentUserTimezone
 * @returns {Promise<void>}
 */
export const init = async (tone,
                           isDifferentTimezone,
                           courseTimezone,
                           userTimezone,
                           serverTimezone,
                           isDifferentServerTimezone,
                           isDifferentUserTimezone
) => {
    // Only proceed if the course is in a different timezone
    if (!isDifferentTimezone) {
        log.debug('Course is not in a different timezone');
        return;
    }

    log.debug(`Course timezone is ${courseTimezone}`);

    const strings = await get_strings([
        { key: 'coursetimezoneis', component: 'local_strath', param: {
            'usertz': userTimezone,
            'coursetz': courseTimezone,
            'servertz': serverTimezone
        }},
        { key: 'timezonewarning', component: 'local_strath', param: {
            'usertz': userTimezone,
            'coursetz': courseTimezone,
            'servertz': serverTimezone
        }},
        { key: 'usermoduletimezonemismatch', component: 'local_strath', param: {
            'usertz': userTimezone,
            'coursetz': courseTimezone,
            'servertz': serverTimezone
        }},
        { key: 'servermoduletimezonemismatch', component: 'local_strath', param: {
            'usertz': userTimezone,
            'coursetz': courseTimezone,
            'servertz': serverTimezone
        }}
    ]);
    log.debug(strings);
    const message = strings[0];
    const serverMessage = isDifferentServerTimezone
        ? strings[3]
        : '';
    const userMessage = isDifferentUserTimezone
        ? strings[2]
        : '';
    // Find all date/time selector fieldsets
    const dateTimeSelectors = $('fieldset[data-fieldtype="date_time"]');
    // Also handle date selectors (not just date_time)
    const dateSelectors = $('fieldset[data-fieldtype="date"]');

    const context = {
        'courseTimezone': courseTimezone,
        'tone': tone,
        'message': message  + userMessage + serverMessage,
        'attributes': [
            {"name": "src", "value":""},
            {"name": "extracclasses", "value":""},
            {"name": "alt", "value": message  + userMessage + serverMessage}
        ]
    };
    Templates.renderForPromise('local_autotimezone/timezonebadge', context)
        .then(({html, js}) => {
            log.debug(html);
            // Add the badge to each selector
            dateTimeSelectors.each(function() {
                const fieldset = $(this);
                const selector = fieldset.find('.fdate_time_selector');
                Templates.appendNodeContents(selector, html, js);
            });
            dateSelectors.each(function() {
                const fieldset = $(this);
                const selector = fieldset.find('.fdate_time_selector');
                Templates.appendNodeContents(selector, html, js);
            });
        })
        .catch((error) => {
            log.error(error);
        })
    ;





};
