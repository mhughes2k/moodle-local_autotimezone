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
use \core_user;
use \core_date;
use \context_system;
/**
 * Class hook_callbacks
 *
 * @package    local_autotimezone
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    public static function after_config() {
        global $CFG;
        global $PAGE, $USER;
    if (during_initial_install()) {
        return;
    }
    $enabled = get_config('local_autotimezone', 'enabled');
    $allowedtouse = has_capability('local/autotimezone:use', context_system::instance(), null, false);
    if (!$enabled || ! $allowedtouse) {
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
                $delay
            ]);
        }
    }
    }
}
