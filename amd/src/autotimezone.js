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
//import { saveCancel } from 'core/notification';
import UpdateIgnoreModal from './modal_update_ignore';
import Pending from 'core/pending';
import {getString, getStrings} from 'core/str';
import { STRINGS } from './autotimezone_strings';


var STRING = null;
/**
 * Automatic Time Zone Switcher.
 * @param {string} currentTimeZone The user's current timezone identifier.
 */
export const init = async (
    currentTimeZone
) => {
    const RESULT_MATCH = "match";

    Log.info("Loading autotimezone");
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
            Log.info("Got position: "+ pos.coords.latitude + ", "+ pos.coords.longitude);
            // Determine if current pos.coords is within the current time zone using api.timezonedb.com
            checkTimezone(pos.coords.latitude, pos.coords.longitude)
            .then((data) => {
                Log.info(data);
                if (data.status != RESULT_MATCH) {
                    Log.warn("No match");
                    Log.info("Current time zone: "+ data.profiletimezone);
                    Log.warn("New time zone "+ data.timezone);
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
                            });

                        },

                        () => {
                            // Set user preference to not trigger checking for at least 24 hrs.
                            const oneDay = 24 *60 * 60 * 1000;
                            const now = new Date();
                            const nextCheck = new Date();
                            nextCheck.setTime(now.getTime() + oneDay);
                            Log.info("Stopping location changes for 24 hrs " + nextCheck.toISOString());
                            deferChecks(nextCheck.getTime()/1000);// Convert to unixtimestamp in seconds.
                        }
                    );
                }
                navigator.geolocation.clearWatch(watchid);
            })
            .fail((error) => {
                Log.error(error);
                navigator.geolocation.clearWatch(watchid);
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

export const updateIgnore = async (profileTz, currentTz, updateCallback, cancelCallback) => {
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
    const modal = await UpdateIgnoreModal.create({
        title,
        body: body,
        buttons: {
            save: updateto,
            cancel: STRING.get('ignorefor24hrs')
        },
        removeOnClose : true,
        show:true
    });
    modal.getRoot().on(ModalEvents.save, updateCallback);
    modal.getRoot().on(ModalEvents.cancel, cancelCallback);
    pendingPromise.resolve();
    return modal;
};
