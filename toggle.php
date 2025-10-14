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
 * Toggle the autotimezone setting.
 * @package     local_autotimezone
 * @copyright   2025 University of Strathclyde <learning-technologies@strath.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

const ACTION_DISABLE = 0;
const ACTION_ENABLE = 1;
const ACTION_CHECKNOW = 2;
const ACTION_PAUSE = 3;

$state = required_param('enable', PARAM_INT);

// If we're turning on we set next check to be 0 so that checks happen.
switch ($state) {
    case ACTION_ENABLE:
        set_user_preference('local_autotimezone_enabled', 1);
        break;
    case ACTION_DISABLE:
        set_user_preference('local_autotimezone_enabled', 0);
        break;
    case ACTION_CHECKNOW:
        set_user_preference('local_autotimezone_nextcheck', 0);
        break;
    case ACTION_PAUSE:
        $delay = get_config('local_autotimezone', 'delay');
        $nextcheck = time() + ($delay * 3600);
        set_user_preference('local_autotimezone_nextcheck', $nextcheck);
        break;
    default:
        throw new \moodle_exception('invalidaction');
}

redirect(new \moodle_url('/user/profile.php'));
