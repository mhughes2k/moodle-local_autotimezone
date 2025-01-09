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

function local_autotimezone_after_config() {
    global $PAGE, $USER;

        $user = \core_user::get_user($USER->id);
        $tz = core_date::get_user_timezone($user);

        $enabled = get_user_preferences('local_autotimezone_enabled', 0);
        $nextcheck = get_user_preferences('local_autotimezone_nextcheck', false);
        $shouldruncheck = (time() >= $nextcheck);
//        debugging("Enabled: $enabled, Next Check: $nextcheck, Sjhoudl run:{$shouldruncheck}");
        if ($enabled) {
            if ($shouldruncheck) {
//                set_user_preference('local_autotimezone_nextcheck', 0);
                $PAGE->requires->js_call_amd('local_autotimezone/autotimezone', 'init', [
                    $tz
                ]);
            } else {
//                debugging('Check deferred to ' . userdate($nextcheck));
            }
        } else {
//            debugging('Disabled');
        }

}

use core_user\output\myprofile\category;
use core_user\output\myprofile\node;
function local_autotimezone_myprofile_navigation(\core_user\output\myprofile\tree $tree, \stdClass $user) {
    global $OUTPUT;
    $category = new category(
        'local_autotimezone',
        get_string('pluginname', 'local_autotimezone'),
        'reports'
    );

    $tree->add_category($category);
    $enabled = get_user_preferences('local_autotimezone_enabled', false);
    if ($enabled) {
        $url = new \moodle_url('/local/autotimezone/toggle.php', ['enable' => 0]);
        $button = new \single_button($url, get_string('disable', 'local_autotimezone'), 'post');
        $content = $OUTPUT->render($button);
    } else {
        $url = new \moodle_url('/local/autotimezone/toggle.php', ['enable' => 1]);
        $button = new \single_button($url, get_string('enable', 'local_autotimezone'), 'post');
        $content = $OUTPUT->render($button);
    }
    $tree->add_node(new node(
        'local_autotimezone',
        'toggleautotimezone',
        get_string('warnontimezoneswitch', 'local_autotimezone'),
        null,
        null,
        $content
    ));

    $nextcheck = get_user_preferences('local_autotimezone_nextcheck', false);
    $content = $nextcheck
        ? get_string('deferswitchcheckuntil', 'local_autotimezone', userdate($nextcheck))
        : ""
        ;
    if ($nextcheck) {// CHeck is deferred}
        $tree->add_node(new node(
            'local_autotimezone',
            'deferrcheckuntil',
            get_string('checkdeferred', 'local_autotimezone'),
            null,
            null,
            $content
        ));
    }
}

function local_autotimezone_user_preferences() {
    return [
        'local_autotimezone_enabled' => [
            'default' => 0,
            'type' => PARAM_INT
        ],
        'local_autotimezone_nextcheck' => [
            'default' => 0,
            'type' => PARAM_INT
        ]
    ];
}
