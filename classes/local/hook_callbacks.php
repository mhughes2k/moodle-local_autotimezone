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
/**
 * Class hook_callbacks
 *
 * @package    local_autotimezone
 * @copyright  2025 University of Strathclyde <learning-technologies@strath.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Handle the after_config hook call.
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function after_config() {
        global $PAGE, $USER;
        return;
        if (during_initial_install()) {
            return;
        }
        $enabled = get_config('local_autotimezone', 'enabled');
        $allowedtouse = has_capability('local/autotimezone:use', context_system::instance(), null, false);
        if (!$enabled || !$allowedtouse) {
            return;
        }
        // TODO Restrict to only running on "user" space pages, not admin ones?

        $user = core_user::get_user($USER->id);
        $tz = core_date::get_user_timezone($user);

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
     * @var string The name of the custom course field that holds a timezone value (e.g. "Asia/Bahrain").
     */
    static $timezonecustomfieldname = 'modulelocation';
    static $timezonecustomfieldid = null;
    /**
     * Load timezone extension for date-time selectors.
     * @param after_standard_main_region_html_generation $hook
     * @return void
     */
    public static function load_datetime_tz_extension(after_standard_main_region_html_generation $hook) :void {
        global $USER;
        $context = $hook->renderer->get_page()->context;

        if ($context->contextlevel != CONTEXT_COURSE) {
            $context = $context->get_course_context(false);
        }
        if ($context === false) {
            return;
        }
        $course = get_course($context->instanceid);

        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $fields = $handler->get_fields();
        $courseTimeZone = hook_callbacks::get_custom_field_data($course, hook_callbacks::$timezonecustomfieldname);

        $isDifferentTimezone = false;
        $isDifferentServerTimezone = false;
        $servertimezone = get_config('core', 'timezone');
        if ($courseTimeZone !== "") {
            $isDifferentServerTimezone = $courseTimeZone !== $servertimezone;
        }

        $isDifferentUserTimezone = $USER->timezone !== $courseTimeZone;
        $isDifferentTimezone = $isDifferentUserTimezone || $isDifferentServerTimezone;
        $tone = 'red';  // TODO this should be a style rule.
        if ($isDifferentServerTimezone && !$isDifferentUserTimezone) {
            // User's prefs match the course.
            $tone = 'green';
        }

        $hook->renderer->get_page()->requires->js_call_amd(
            'local_strath/dateselector-tz',
            'init',
            [
                $tone,
                $isDifferentTimezone,
                $courseTimeZone,
                $USER->timezone,
                $servertimezone,
                $isDifferentServerTimezone,
                $isDifferentUserTimezone
            ]
        );
    }

    static $coursetimezone_cache = [];
    static function get_custom_field_data($course, $name = false) {
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
        if ($name !== false) {
            return $rv->$name;
        }
        return $rv;
    }
}
