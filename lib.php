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
 * Globally shared code for local_autotimezone.
 * @package     local_autotimezone
 * @copyright   2025 Univesity of Strathclyde <learning-technologies@strath.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_autotimezone\local\hook_callbacks;

if ($CFG->branch < 405) {
    /**
     * Prior to 4.5 use the callback approach.
     * @return void
     */
    function local_autotimezone_after_config() {
        hook_callbacks::after_config();
    }
}


use core_user\output\myprofile\category;
use core_user\output\myprofile\node;

/**
 * Add autotimezone controls to the user's profile.
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_autotimezone_myprofile_navigation(\core_user\output\myprofile\tree $tree, \stdClass $user) {
    global $OUTPUT;
    $enabled = get_config('local_autotimezone', 'enabled');
    $allowedtouse = has_capability('local/autotimezone:use', context_system::instance(), $user, false);
    if (!$enabled) {
        // TODO this sholdn't be outputted.
        echo "can't use";
        return;
    }
    if (! $allowedtouse) {
        echo "no permission";
        // TODO Should give a user that the feature is not available to them.
        // TODO Create a request mechanism.
        return;
    }
    $category = new category(
        'local_autotimezone',
        get_string('pluginname', 'local_autotimezone'),
        'reports'
    );

    $tree->add_category($category);
    $userenabled = get_user_preferences('local_autotimezone_enabled', false);
    $content = '';

    if ($userenabled) {
        $url = new \moodle_url('/local/autotimezone/toggle.php', ['enable' => 0]);
        $button = new \single_button($url, get_string('disable', 'local_autotimezone'), 'post');
        // Display next check info.
        $content .= $OUTPUT->render($button);
    } else {
        $url = new \moodle_url('/local/autotimezone/toggle.php', ['enable' => 1]);
        $button = new \single_button($url, get_string('enable', 'local_autotimezone'), 'post');
        $content .= $OUTPUT->render($button);
    }
    $tree->add_node(new node(
        'local_autotimezone',
        'toggleautotimezone',
        get_string('warnontimezoneswitch', 'local_autotimezone'),
        null,
        null,
        $content
    ));

    if ($userenabled) {
        $nextcheck = get_user_preferences('local_autotimezone_nextcheck', false);
        $content = $nextcheck
        ? get_string('deferswitchcheckuntil', 'local_autotimezone', userdate($nextcheck))
        : "";
        // Managing pausing and frequency only makes sense if enabled.
        if ($nextcheck) { // Check is deferred.
            $url = new \moodle_url('/local/autotimezone/toggle.php', ['enable' => 2]);
            $button = new \single_button($url, get_string('resumechecking', 'local_autotimezone'), 'post');
            $content .= \html_writer::tag('div', $OUTPUT->render($button));
        } else {
            // Pause.
            $delay = get_config('local_autotimezone', 'delay');
            // TODO Convert delay in to human readable format.

            $url = new \moodle_url('/local/autotimezone/toggle.php', ['enable' => 3]);
            $button = new \single_button($url, get_string('pausechecking', 'local_autotimezone', $delay), 'post');
            $content .= \html_writer::tag('div', $OUTPUT->render($button));
        }
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

/**
 * Indicate which user preferences this plugin can update.
 * @return array[]
 */
function local_autotimezone_user_preferences() {
    return [
        'local_autotimezone_enabled' => [
            'default' => 0,
            'type' => PARAM_INT,
        ],
        'local_autotimezone_nextcheck' => [
            'default' => 0,
            'type' => PARAM_INT,
        ],
    ];
}

// This function does not seem to be fired.
// function local_autotimezone_render_navbar_output($output) {
// }
