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
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
require_once($CFG->dirroot . '/user/lib.php');

/**
 * @package     local_autotimezone
 * @copyright   2025 Univesity of Strathclyde <learning-technologies@strath.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_timezone extends \core_external\external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'timezone' => new external_value(PARAM_RAW, 'TimeZone', VALUE_REQUIRED),
        ]);
    }

    public static function execute_returns() {
        return null;
    }
    /**
     * Makes a request to API to convert current lat long to timezone
     * @return mixed
     * @throws \dml_exception
     */
    public static function execute($timezone) {
        global $USER, $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['timezone' => $timezone]);
        $user = \core_user::get_user($USER->id);    // we can only update the calling user.
        $user->timezone = $params['timezone'];

        user_update_user($user, false); // DOn't update the password.
        //Reload session user.
        \core\session\manager::set_user($user);
        profile_load_custom_fields($USER);
        return;
    }
}
