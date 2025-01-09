import {call as fetchMany} from 'core/ajax';
export const checkTimezone = (latitude, longitude) => fetchMany([{
    methodname: 'local_autotimezone_get_current_timezone',
    args: {
        latitude,
        longitude
    }
}])[0];

export const deferChecks = (nextCheck) => fetchMany([{
    methodname: 'core_user_update_user_preferences',
    args: {
        userid: 0,
        emailstop: null,
        preferences: [
            {
                'type': 'local_autotimezone_nextcheck',
                'value': nextCheck
            }
        ]
    }
}])[0];

export const updateTimeZone = (newZone) => fetchMany([{
    methodname:'local_autotimezone_update_timezone',
    args: {
       timezone: newZone
    }
}])[0];
