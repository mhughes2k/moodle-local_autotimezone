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

import Log from 'core/log';
import {checkTimezone, deferChecks, updateTimeZone} from './repository';
import UpdateIgnoreModal from './modal_update_ignore';
import Pending from 'core/pending';
import {getString, getStrings} from 'core/str';
import {DEFAULT_CONFIG, STRINGS} from './common';


var STRING = null;
var CONFIG = null;
/**
 * Automatic Time Zone Switcher.
 * @param {string} currentTimeZone The user's current timezone identifier.
 * @param {int} delay Number of seconds to delay checks by.
 */
export const init = async(
    currentTimeZone,
    delay
) => {
    const RESULT_MATCH = "match";
    CONFIG = DEFAULT_CONFIG;
    CONFIG.delay = delay;
    Log.info("Loading autotimezone");
    Log.debug(CONFIG);
    const stringValues = await getStrings(
        STRINGS.map(
            (key) => ({key, component: 'local_autotimezone'}))
    );
    STRING = new Map(STRINGS.map((key, index) => ([key, stringValues[index]])));
    Log.debug(STRING);

    Log.info(currentTimeZone);
    // We need access to the geolocation API.
    if (!navigator.geolocation) {
        Log.warn("No geolocation API available");
        return;
    } else {
        Log.info("Geolocation API available");
    }

    // If the user's current location is not within the current time zone, we will switch it.
    let watchid = navigator.geolocation.watchPosition(
        (pos) => {
            Log.info("Got position: " + pos.coords.latitude + ", " + pos.coords.longitude);
            // Determine if current pos.coords is within the current time zone using api.timezonedb.com
            checkTimezone(pos.coords.latitude, pos.coords.longitude)
            .then((data) => {
                Log.info(data);
                if (data.status != RESULT_MATCH) {
                    Log.warn("No match");
                    Log.info("Current time zone: " + data.profiletimezone);
                    Log.warn("New time zone " + data.timezone);
                    // Display some sort UI to the user to indicate that they're real
                    // location doesn't match their time zone.
                    // We really want this to be *non*-interruptive, but noticeable.
                    updateIgnore(
                        data.profiletimezone,
                        data.timezone,
                        () => {
                            Log.info("Updating time zone to " + data.timezone);
                            updateTimeZone(data.timezone).then(() => {
                                window.location.reload();
                                return true;
                            })
                            .fail(() => {
                                Log.error("Failed to update time zone");
                                return false;
                            });

                        },

                        () => {
                            // Set user preference to not trigger checking for at least 24 hrs.
                            const delayms = CONFIG.delay * 1000;
                            const now = new Date();
                            const nextCheck = new Date();
                            nextCheck.setTime(now.getTime() + delayms);
                            Log.info("Stopping location changes for " + CONFIG.delay + " hrs " + nextCheck.toISOString());
                            deferChecks(nextCheck.getTime() / 1000);// Convert to unixtimestamp in seconds.
                        }
                    );
                }
                navigator.geolocation.clearWatch(watchid);
                return true;
            })
            .fail((error) => {
                Log.error(error);
                navigator.geolocation.clearWatch(watchid);
                return false;
            });
        },
        (error) => {
            Log.error(error);
            if (error.code == 2) {
                Log.error("Unable to establish position");
            }
            navigator.geolocation.clearWatch(watchid);
        });
};

/**
 * Display the warning modal
 * @param {string} profileTz  Timezone in user's profile.
 * @param {string} currentTz {string Timezone determined by location.
 * @param {callback} updateCallback
 * @param {callback} cancelCallback
 * @returns {Promise<Modal>}
 */
export const updateIgnore = async(profileTz, currentTz, updateCallback, cancelCallback) => {
    var pendingPromise = new Pending('local/autotimezone:updateIgnore');

    const [
        ModalEvents
    ] = await Promise.all([
        import('core/modal_events')
    ]);

    const title = STRING.get('updatemodaltitle');
    const body = await getString(
        'updatemodalbody', 'local_autotimezone',
        {profileTz: profileTz, currentTz: currentTz}
    );
    const updateto = await getString(
        'updatetimezoneto', 'local_autotimezone',
        {currentTz: currentTz}
    );
    const ignoreforXhrs = await getString(
        'ignoreforXhrs', 'local_autotimezone',
        {delay: CONFIG.delay / 3600}
    );
    const modal = await UpdateIgnoreModal.create({
        title,
        body: body,
        buttons: {
            save: updateto,
            cancel: ignoreforXhrs
        },
        removeOnClose: true,
        show: true
    });
    modal.getRoot().on(ModalEvents.save, updateCallback);
    modal.getRoot().on(ModalEvents.cancel, cancelCallback);
    pendingPromise.resolve();
    return modal;
};
