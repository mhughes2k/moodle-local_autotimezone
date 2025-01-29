<?php
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

namespace local_autotimezone\external;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir .'/filelib.php');


use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

/**
 * Web service to determine current timezone based on lat/long.
 * @package     local_autotimezone
 * @copyright   2025 Univesity of Strathclyde <learning-technologies@strath.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_current_timezone extends \core_external\external_api {

    /**
     * Define service parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'latitude' => new external_value(PARAM_RAW, 'Latitude', VALUE_OPTIONAL),
            'longitude' => new external_value(PARAM_RAW, 'Longitude', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Define return structure.
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_RAW, 'Status', VALUE_REQUIRED),
            'message' => new external_value(PARAM_RAW, 'Message', VALUE_REQUIRED),
            'profiletimezone' => new external_value(PARAM_RAW, 'Timezone set in profile.', VALUE_REQUIRED),
            'timezone' => new external_value(PARAM_RAW, 'Actual geo-located Timezone.', VALUE_REQUIRED),
        ]);
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

        $params = self::validate_parameters(self::execute_parameters(), ['latitude' => $latitude, 'longitude' => $longitude]);

        $backend = get_config('local_autotimezone', 'locationbackend');
        switch (strtolower($backend)) {
            case 'backend_timezonedb':
                return self::backend_timezonedb($usertz, $params);
                break;
            case 'backend_local':
                return self::backend_local($usertz, $params);
                break;
            default:
                throw new \coding_exception('invalidbackend');
        }
    }

    /**
     * Autotimezone.com API backend.
     * @param string $usertz
     * @param string $params
     * @param {string} $usertz User's timezone from profile.
     * @param {string[]} $params Web Service Parameters.
     * @return object
     * @throws \dml_exception
     */
    protected static function backend_timezonedb(string $usertz, array $params): object {
        $apikey = get_config('local_autotimezone', 'timezonedbapikey');

        $request = "http://api.timezonedb.com/v2.1/get-time-zone?key={$apikey}&format=json&by=position&";
        $request .= "lat={$params['latitude']}&lng={$params['longitude']}";

        $response = \download_file_content($request, null, null, false, false, true);
        $json = json_decode($response);

        if ($usertz != $json->zoneName) {
            // We have a mismatch between the user's profile timezone and their browser's location timezone.
            return (object) [
                'status' => 'moved',
                'message' => "Profile TZ ({$usertz}) does not match browser TZ ({$json->zoneName}).",
                'profiletimezone' => $usertz,
                'timezone' => $json->zoneName,
            ];
        }

        return (object) [
            'status' => 'match',
            'message' => "Profile TZ ({$usertz}) matches browser TZ ({$json->zoneName}).",
            'profiletimezone' => $usertz,
            'timezone' => $json->zoneName,
        ];;
    }

    /**
     * Non-API based back end.
     * @param {string} $usertz User's timezone from profile.
     * @param {string[]} $params Web Service Parameters.
     * @return object
     */
    protected static function backend_local(string $usertz, array $params): object {
        return (object) [
            'status' => 'match',
            'message' => "Not implemented, always matches ({$params['latitude']}&lng={$params['longitude']})",
            'profiletimezone' => $usertz,
            'timezone' => $usertz,
        ];
    }
}
