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

defined('MOODLE_INTERNAL') || die;

/**
 * Declare web services.
 * @package     local_autotimezone
 * @copyright   2025 Univesity of Strathclyde <learning-technologies@strath.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
    'local_autotimezone_get_current_timezone' => [
        'classname' => 'local_autotimezone\external\get_current_timezone',
        'description' => 'Get the current timezone of the user',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_autotimezone_update_timezone' => [
        'classname' => 'local_autotimezone\external\update_timezone',
        'description' => 'Update user\'s time zone',
        'type' => 'write',
        'ajax' => true,
    ],
];
