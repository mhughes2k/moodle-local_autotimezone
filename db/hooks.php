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

/**
 * Hook callbacks for Automatic Time Zone Switcher
 * @package     local_autotimezone
 * @copyright   2025 Univesity of Strathclyde <learning-technologies@strath.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_user\hook\extend_user_menu;

defined('MOODLE_INTERNAL') || die();

global $CFG;

if ($CFG->branch > 404) {
    $callbacks = [
        [
            'hook' => \core\hook\after_config::class,
            'callback' => [\local_autotimezone\local\hook_callbacks::class, 'after_config'],
            'priority' => 500,
        ],
        [
            'hook' => \core\hook\output\before_standard_top_of_body_html_generation::class,
            'callback' => [\local_autotimezone\local\hook_callbacks::class, 'load_datetime_tz_extension'],
            'priority' => 500,
        ],
        [
            'hook' => extend_user_menu::class,
            'callback' => [\local_autotimezone\local\hook_callbacks::class, 'load_datetime_tz_extension_usermenu'],
            'priority' => 500,
        ],
    ];
} else {
    $callbacks = [
        [   // This enables the automatic timezone switching.
            'hook' => \core\hook\after_config::class,
            'callback' => "\local_autotimezone\local\hook_callbacks::after_config",
            'priority' => 500,
        ],
        [
            // This enables the enhancements to the date-time selector.
            'hook' => core\hook\output\before_standard_top_of_body_html_generation::class,
            'callback' => "\local_autotimezone\local\hook_callbacks::load_datetime_tz_extension",
            'priority' => 500,
        ],
    ];
}
