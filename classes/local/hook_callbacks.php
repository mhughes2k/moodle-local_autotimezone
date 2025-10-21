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

use core\check\performance\debugging;
use core_user;
use core_date;

use function DI\get;

/**
 * Class hook_callbacks
 *
 * @package    local_autotimezone
 * @copyright  2025 University of Strathclyde <learning-technologies@strath.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * @var string Default name for a custom course field that holds a timezone value (e.g. "Asia/Bahrain").
     */
    const DEFAULT_FIELDNAME = 'modulelocation';
    /**
     * @var string The name of the custom course field that holds a timezone value (e.g. "Asia/Bahrain").
     */
    protected static $tzcustomfieldname = self::DEFAULT_FIELDNAME;
    /**
     * @var int The id of the custom course field that holds a timezone value (e.g. "Asia/Bahrain").
     */
    protected static $tzcustomfieldid = null;

    /**
     * @var string Timezone switcher mode.
     */
    const MODE_SWITCHER = 'switcher';
    /**
     * @var string Datetime enhancements mode (adds timezone info to date-time selectors).
     */
    const MODE_DATETIMEENHANCEMENTS = 'datetimeenhancements';

    /**
     * @var bool Cache of whether the configuration is valid and the tool is usable.
     */
    protected static $isavailable = null;

    /**
     * Check that the plugin is correctly configured.
     * Returns true if config is OK, false otherwise.
     */
    public static function check_config(): bool {
        // We always load the fieldname from config.
        self::$tzcustomfieldname = get_config('local_autotimezone', 'coursetimezonefield');
        if (empty(self::$tzcustomfieldname)) {
            return false;
        }
        if (!is_null(self::$isavailable)) {
            return self::$isavailable;
        }
        $fieldfound = false;
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        foreach ($handler->get_fields() as $field) {
            if ($field->get('shortname') === self::$tzcustomfieldname) {
                $fieldfound = true;
                break;
            }
        }
        self::$isavailable = $fieldfound;
        return $fieldfound;
    }

    /**
     * Returns an array of problems with the configuration.
     * @return array
     */
    public static function config_report(): array {
        $issues = [];
        self::check_config();
        self::$tzcustomfieldname = get_config('local_autotimezone', 'coursetimezonefield');
        if (empty(self::$tzcustomfieldname)) {
            $issues[] = get_string('fieldnotset', 'local_autotimezone');
        }
        $fieldfound = false;
        if (!empty(self::$tzcustomfieldname)) {
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            foreach ($handler->get_fields() as $field) {
                if ($field->get('shortname') === self::$tzcustomfieldname) {
                    $fieldfound = true;
                    break;
                }
            }
            if (!$fieldfound) {
                $issues[] = get_string('missingfield', 'local_autotimezone', self::$tzcustomfieldname);
                $issues[] = get_string('howtocreatefield', 'local_autotimezone');
            }
        }
        return $issues;
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
        self::check_config();
        $enabled = get_config('local_autotimezone', 'enabled');

        $allowedtouse = has_capability('local/autotimezone:use', \core\context\system::instance(), null, false);
        if (!$enabled || !$allowedtouse) {
            return;
        }
        // TODO Restrict to only running on "user" space pages, not admin ones?

        $user = core_user::get_user($USER->id);
        $usertz = core_date::get_user_timezone($user);
        $coursetz = null;
        if (!is_null($COURSE)) {
            $coursetz  = self::get_custom_field_data($COURSE, self::$tzcustomfieldname);
        }

        $userenabled = get_user_preferences('local_autotimezone_enabled', 0);
        $nextcheck = get_user_preferences('local_autotimezone_nextcheck', false);
        $shouldruncheck = (time() >= $nextcheck);
        $delay = get_config('local_autotimezone', 'delay');
        if ($userenabled) {
            if ($shouldruncheck) {
                $PAGE->requires->js_call_amd('local_autotimezone/autotimezone', 'init', [
                    $usertz,
                    $coursetz,
                    $delay,
                ]);
            }
        }
    }


    /**
     * Load timezone extension for date-time selectors.
     *
     * Current set up is that you need to have the enhancements enabled and you always get the date-time garnishes.
     * Once turned on getting the course notification of a mismatch between the user and the course is an option.
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     * @return void
     */
    public static function load_datetime_tz_extension(\core\hook\output\before_standard_top_of_body_html_generation $hook): void {
        self::check_config();
        $context = $hook->renderer->get_page()->context;
        if ($timezoneanalysis = self::load_datetime_tz_extension_core($hook, $context)) {
            // Check that this is all in use, and if it is, add the adornments.
            $hook->renderer->get_page()->requires->js_call_amd(
                'local_autotimezone/dateselector-tz',
                'init',
                [
                    $timezoneanalysis['tone'],
                    $timezoneanalysis['isdifferenttimezone'],
                    $timezoneanalysis['coursetimezone'],
                    $timezoneanalysis['usertz'],
                    $timezoneanalysis['servertz'],
                    $timezoneanalysis['isdifferentservertimezone'],
                    $timezoneanalysis['isdifferentusertimezone'],
                ]
            );
        }
    }
    /**
     * Load the timezone extension for the user menu.
     */
    public static function load_datetime_tz_extension_usermenu(core_user\hook\extend_user_menu $hook): void {
        self::check_config();
        if ($timezoneanalysis = self::load_datetime_tz_extension_core($hook)) {
            // Check in use, and add the user_menu display.
            // Notification should have been skipped in the core if it was turned off.
            $notificationtype = get_config('local_autotimezone', 'coursenotificationtype');
            if ($notificationtype === 'usermenu') {
                $texttitle = get_string(
                    'usermenu:timezoneconflictindicator',
                    'local_autotimezone',
                    (object)[
                        'usertz' => $timezoneanalysis['usertz'],
                        'coursetz' => $timezoneanalysis['coursetimezone'],
                        'servertz' => $timezoneanalysis['servertz'],
                    ]
                );
                $hook->add_navitem(
                    (object)[
                        'itemtype' => 'link',
                        'title' => $texttitle,
                        'text' => $texttitle,
                        // 'titleidentifier' => 'local_autotimezone_timezoneconflictindicator',
                    ]
                );
            }
        } else {
            debugging('Timezone analysis not available in usermenu hook', DEBUG_DEVELOPER);
        }
    }

    /**
     * Check and get timezone analysis data.
     * 
     * This will perform any thing that is done in *all* cases where datetime enhancements are enabled.
     * This would be:
     *  * Check if datetime enhancements are enabled.
     *  * Get the course timezone from the custom field.
     *  * Analyze timezone conflicts.
     *  * Displaying banner "notification" if configured.
     * @param \core\hook\output\before_standard_top_of_body_html_generation|\core_user\hook\extend_user_menu $hook
     * @param \context|null $context The context to use to find the course. If null, no course context is used.
     * @return array|false The timezone analysis data, or false if not available.
     */
    protected static function load_datetime_tz_extension_core($hook, ?\context $context = null): array | false {
        global $OUTPUT;
        self::check_config();
        // Check enablement first.
        $enabled = get_config('local_autotimezone', 'datetimeenhancementsenabled');
        if (!$enabled) {
            return false;
        }
        // This doesn't work if we're not logged in.
        if (isguestuser() || !isloggedin()) {
            return false;
        }

        if ($context && $context->contextlevel != CONTEXT_COURSE) {
            $context = $context->get_course_context(false);
        }

        $course = $context ? get_course($context->instanceid) : null;
        // This will return false if not configured correctly.
        if ($course && $coursetimezone = self::get_custom_field_data($course, self::$tzcustomfieldname)) {
            $timezoneanalysis = self::analyze_timezone_conflicts($coursetimezone);
            // Add notification to user if there is a conflict.
            $tza = (object) [
                'usertz' => $timezoneanalysis['usertz'],
                'coursetz' => $timezoneanalysis['coursetimezone'],
                'servertz' => $timezoneanalysis['servertz'],
            ];

            $coursenotificationenabled = get_config('local_autotimezone', 'coursenotificationenabled');
            $shownotificationforcourseserverconflict = get_config('local_autotimezone', 'shownotificationforcourseserverconflict');
            if ($coursenotificationenabled) {
                if ($timezoneanalysis['isdifferenttimezone']) {
                    $what = false;
                    if ($timezoneanalysis['isdifferentusertimezone']) {
                        $what = 'usermoduletimezonemismatch';
                    } else if (
                        $shownotificationforcourseserverconflict && 
                        $timezoneanalysis['isdifferentservertimezone']
                    ) {
                        $what = 'servermoduletimezonemismatch';
                    }
                    if ($what ?? false) {
                        $notificationtype = get_config('local_autotimezone', 'coursenotificationtype');
                        if ($notificationtype === 'banner') {
                            $helpicon = new \core\output\help_icon('timezoneconflicthelp', 'local_autotimezone', $tza);
                            \core\notification::add(
                                get_string($what, 'local_autotimezone', $tza) .
                                $OUTPUT->render($helpicon),
                                \core\output\notification::NOTIFY_WARNING
                            );
                        }
                    }
                }
            }
            return $timezoneanalysis;
        }
        return self::analyze_timezone_conflicts("");
    }

    /**
     * Analyze timezone conflicts between course, user, and server timezones.
     *
     * @param string $coursetimezone The course timezone
     * @return array Array containing timezone analysis data
     */
    protected static function analyze_timezone_conflicts($coursetimezone): array {
        $usertz = core_date::get_user_timezone();
        $servertz = core_date::get_server_timezone();
        $servertimezone = get_config('core', 'timezone');

        // Course defaults to server time zone if empty.
        if ($coursetimezone === "") {
            $coursetimezone = $servertimezone;
        }

        $isdifferentservertimezone = $coursetimezone !== $servertimezone;
        $isdifferentusertimezone = $usertz !== $coursetimezone;
        $isdifferentusertimezone = $isdifferentusertimezone || $isdifferentservertimezone;

        // Determine visual indicator tone.
        $tone = 'red';  // Default to indicating conflict.
        if ($isdifferentservertimezone && !$isdifferentusertimezone) {
            // User's prefs match the course, even if different from server
            $tone = 'green';
        }

        return [
            'coursetimezone' => $coursetimezone,
            'usertz' => $usertz,
            'servertz' => $servertz,
            'servertimezone' => $servertimezone,
            'isdifferenttimezone' => $isdifferentusertimezone,
            'isdifferentservertimezone' => $isdifferentservertimezone,
            'isdifferentusertimezone' => $isdifferentusertimezone,
            'tone' => $tone,
        ];
    }

    /**
     * @var array Cache of course timezone custom field data.
     */
    protected static $coursetimezonecache = [];
    /**
     * Returns either a single value for a named field, or the all of the values for a course.
     *
     * This can be called as part of the public API.
     * @param \stdClass $course The
     * @param string|bool $name The name of the field to return, or false to return all fields.
     * @return \stdClass|string|bool The value of the field, or all fields, or false on error.
     * @throws \dml_exception
     */
    public static function get_custom_field_data($course, $name = false): \stdClass | string | bool {
        $rv = false;
        if (self::check_config() === false) {
            return false;
        }
        // TODO Caching.
        if (isset(self::$coursetimezonecache[$course->id])) {
            $rv = self::$coursetimezonecache[$course->id];
        } else {
            // Fetch data.
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            // MTTT-275 Need to use the $returnall = true otherwise we don't see the banner configuration if we're a student.
            $datas = $handler->export_instance_data($course->id, true);
            $rv = new \stdClass();
            foreach ($datas as $d) {
                $rv->{$d->get_shortname()} = $d->get_data_controller()->get_value();
            }
            self::$coursetimezonecache[$course->id] = $rv;
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
