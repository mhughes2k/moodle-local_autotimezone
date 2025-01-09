<?php
namespace local_autotimezone\external;
require_once($CFG->libdir .'/filelib.php');

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
class get_current_timezone extends \core_external\external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'latitude' => new external_value(PARAM_RAW, 'Latitude', VALUE_OPTIONAL),
            'longitude' => new external_value(PARAM_RAW, 'Longitude', VALUE_OPTIONAL),
        ]);
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_RAW, 'Status', VALUE_REQUIRED),
            'message' => new external_value(PARAM_RAW, 'Message', VALUE_REQUIRED),
            'profiletimezone' => new external_value(PARAM_RAW, 'Timezone set in profile.', VALUE_REQUIRED),
            'timezone' => new external_value(PARAM_RAW, 'Actual geo-located Timezone.', VALUE_REQUIRED),
        ]);
        //external_value(PARAM_RAW, 'Timezone', VALUE_REQUIRED);
    }
    /**
     * Makes a request to API to convert current lat long to timezone
     * @return mixed
     * @throws \dml_exception
     */
    public static function execute($latitude, $longitude) {
        global $USER;
        $user = \core_user::get_user($USER->id);
        $usertz = \core_date::get_user_timezone($user); // Don't user $USER as this is cached.

        $apikey = "VBJ3253GXDUR"; // TODO move to setting.
        $params = self::validate_parameters(self::execute_parameters(), ['latitude' => $latitude, 'longitude' => $longitude]);

        $request = "http://api.timezonedb.com/v2.1/get-time-zone?key={$apikey}&format=json&by=position&lat={$params['latitude']}&lng={$params['longitude']}";

        $response = \download_file_content($request, null, null, false, false, true);
        $json = json_decode($response);

        if ($usertz != $json->zoneName) {
            // We have a mismatch between the user's profile timezone and their browser's location timezone.
            return (object) [
                'status' => 'moved',
                'message' => "Profile TZ ({$usertz}) does not match browser TZ ({$json->zoneName}).",// . print_r($json,1),
                'profiletimezone' => $usertz,
                'timezone' => $json->zoneName,
            ];
        }

        return (object) [
            'status' => 'match',
            'message' => "Profile TZ ({$usertz}) matches browser TZ ({$json->zoneName}).",//. print_r($json,1),
            'profiletimezone' => $usertz,
            'timezone' => $json->zoneName,
        ];;

    }
}
