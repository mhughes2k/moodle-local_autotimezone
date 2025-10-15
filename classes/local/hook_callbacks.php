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

namespace local_autotimezone\local;
use core_user;
use core_date;
use context_system;

use core\hook\output\after_standard_main_region_html_generation;
use DB;

/**
 * Class hook_callbacks
 *
 * @package    local_autotimezone
 * @copyright  2025 University of Strathclyde <learning-technologies@strath.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    const DEFAULT_FIELDNAME = 'modulelocation';
    /**
     * @var string The name of the custom course field that holds a timezone value (e.g. "Asia/Bahrain").
     */
    static $timezonecustomfieldname = self::DEFAULT_FIELDNAME;
    static $timezonecustomfieldid = null;

    const MODE_SWITCHER = 'switcher';
    const MODE_DATETIMEENHANCEMENTS = 'datetimeenhancements';
    static $isAvailable = null;
    /**
     * Check that the plugin is correctly configured.
     * Note we're not checking enablement here, just configuration.
     * @param array|bool $issues If fullreport is true, this will be populated with any issues found. Otherwise simple true / false if config was OK or not
     */
    public static function check_config($fullreport = false): array | bool {
        $issues = [];

        // We always load the fieldname from config.
        self::$timezonecustomfieldname = get_config('local_autotimezone', 'coursetimezonefield');
        if (empty(self::$timezonecustomfieldname)) {
            $issues[] = get_string('fieldnotset', 'local_autotimezone');
            if (!$fullreport) {
                return false;
            }
        }
        // Check the static cache on the class so we don't loop if
        // we've already done it in this request: it's unlikely to change.
        if (!$fullreport && !is_null(self::$isAvailable)) {
            return self::$isAvailable;
        }
        // Full field check.
        // Potentially this gets extended if we had multiple fields that *need*
        // to have been set up.
        $fieldfound = false;
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        foreach ($handler->get_fields() as $field) {
            if ($field->get('shortname') === self::$timezonecustomfieldname) {
                $fieldfound = true;
                break; // We don't need to continue as we found the necesary field.
            }
        }
        if (!$fieldfound) {
            $issues[] = get_string('missingfield', 'local_autotimezone', self::$timezonecustomfieldname);
            $issues[] = get_string('howtocreatefield', 'local_autotimezone');
            // If field doesn't exist disable to the plugin.
            self::$isAvailable = false;
            set_config('enabled', 0, 'local_autotimezone');
            if (!$fullreport) {
                return false;
            }
        }
        self::$isAvailable = $fieldfound;
        if ($fullreport) {
            return $issues;
        }
        return $fieldfound;
    }

    /**
     * Handle the after_config hook call to load switcher.
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function after_config() {
        global $PAGE, $USER, $COURSE;
        if (during_initial_install()) {
            return;
        }
        
        // This doesn't work if we're not logged in.
        if (isguestuser() || !isloggedin()) {
            return;
        }

        $enabled = get_config('local_autotimezone', 'enabled');
       
        $allowedtouse = has_capability('local/autotimezone:use', \core\context\system::instance(), null, false);
        if (!$enabled || !$allowedtouse) {
            return;
        }
        // TODO Restrict to only running on "user" space pages, not admin ones?

        $user = core_user::get_user($USER->id);
        $tz = core_date::get_user_timezone($user);
        if (!is_null($COURSE)) {
            $coursetz  = self::get_custom_field_data($COURSE, self::$timezonecustomfieldname);
        }

        $userenabled = get_user_preferences('local_autotimezone_enabled', 0);
        $nextcheck = get_user_preferences('local_autotimezone_nextcheck', false);        
        $shouldruncheck = (time() >= $nextcheck);
        $delay = get_config('local_autotimezone', 'delay');
        if ($userenabled) {
            if ($shouldruncheck) {
                $PAGE->requires->js_call_amd('local_autotimezone/autotimezone', 'init', [
                    $tz,
                    $delay,
                ]);
            }
        }
    }


    /**
     * Load timezone extension for date-time selectors.
     * @param after_standard_main_region_html_generation $hook
     * @return void
     */
    public static function load_datetime_tz_extension(after_standard_main_region_html_generation $hook) :void {
        global $USER;
        // Check enablement first.
        $enabled = get_config('local_autotimezone', 'datetimeenhancementsenabled');
        if (!$enabled) {
            return;
        }
        // This doesn't work if we're not logged in.
        if (isguestuser() || !isloggedin()) {
            return;
        }

        $context = $hook->renderer->get_page()->context;

        if ($context->contextlevel != CONTEXT_COURSE) {
            $context = $context->get_course_context(false);
        }
        if ($context === false) {
            return;
        }
        $course = get_course($context->instanceid);
        // This will return false if not configured correctly.
        if ($courseTimeZone = self::get_custom_field_data($course, self::$timezonecustomfieldname)) {
            $timezoneAnalysis = self::analyze_timezone_conflicts($courseTimeZone);

            $hook->renderer->get_page()->requires->js_call_amd(
                'local_autotimezone/dateselector-tz',
                'init',
                [
                    $timezoneAnalysis['tone'],
                    $timezoneAnalysis['isDifferentTimezone'],
                    $timezoneAnalysis['courseTimeZone'],
                    $timezoneAnalysis['usertz'],
                    $timezoneAnalysis['servertz'],
                    $timezoneAnalysis['isDifferentServerTimezone'],
                    $timezoneAnalysis['isDifferentUserTimezone']
                ]
            );
        } else {
            debugging('Not loading course timezone as not configured correctly', DEBUG_DEVELOPER);
        }
    }

    /**
     * Analyze timezone conflicts between course, user, and server timezones.
     * 
     * @param string $courseTimeZone The course timezone
     * @return array Array containing timezone analysis data
     */
    public static function analyze_timezone_conflicts($courseTimeZone): array {
        $usertz = core_date::get_user_timezone();
        $servertz = core_date::get_server_timezone();
        $servertimezone = get_config('core', 'timezone');
        
        // Course defaults to server time zone if empty
        if ($courseTimeZone === "") {
            $courseTimeZone = $servertimezone;
        }
        
        $isDifferentServerTimezone = $courseTimeZone !== $servertimezone;
        $isDifferentUserTimezone = $usertz !== $courseTimeZone;
        $isDifferentTimezone = $isDifferentUserTimezone || $isDifferentServerTimezone;
        
        // Determine visual indicator tone
        $tone = 'red';  // Default to indicating conflict
        if ($isDifferentServerTimezone && !$isDifferentUserTimezone) {
            // User's prefs match the course, even if different from server
            $tone = 'green';
        }
        
        return [
            'courseTimeZone' => $courseTimeZone,
            'usertz' => $usertz,
            'servertz' => $servertz,
            'servertimezone' => $servertimezone,
            'isDifferentTimezone' => $isDifferentTimezone,
            'isDifferentServerTimezone' => $isDifferentServerTimezone,
            'isDifferentUserTimezone' => $isDifferentUserTimezone,
            'tone' => $tone
        ];
    }

    static $coursetimezone_cache = [];
    /**
     * Returns either a single value for a named field, or the all of the values for a course.
     * @param \stdClass $course The
     * @param string|bool $name The name of the field to return, or false to return all fields.
     * @return \stdClass|string|bool The value of the field, or all fields, or false on error.
     * @throws \dml_exception
     */
    static function get_custom_field_data($course, $name = false): \stdClass | string | bool {
        $rv = false;
        if (self::check_config() === false) {
            return false;
        }
        // TODO Caching
        if (isset(hook_callbacks::$coursetimezone_cache[$course->id])) {
            $rv = hook_callbacks::$coursetimezone_cache[$course->id];
        } else {
            // Fetch data.
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            $fields = $handler->get_fields();
            // MTTT-275 Need to use the $returnall = true otherwise we don't see the banner configuration if we're a student.
            $datas = $handler->export_instance_data($course->id, true);
            $rv = new \stdClass();
            foreach ($datas as $d) {
                $rv->{$d->get_shortname()} = $d->get_data_controller()->get_value();
            }
            hook_callbacks::$coursetimezone_cache[$course->id] = $rv;
        }
        // We want just 1 value.
        if ($name !== false) {
            if (!property_exists($rv, $name)) {
                debugging("Requested custom field $name does not exist on course {$course->id}", DEBUG_DEVELOPER);
                return false;
            }
            return $rv->$name;
        }
        return $rv;
    }
}
