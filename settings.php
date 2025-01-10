<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     local_autotimezone
 * @category    admin
 * @copyright   2025 Univesity of Strathclyde <learning-technologies@strath.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_autotimezone_settings', new lang_string('pluginname', 'local_autotimezone'));
    $ADMIN->add('localplugins', $settings);
    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree && $ADMIN->locate('localplugins')) {

        // TODO: Define actual plugin settings page and add it to the tree - {@link https://docs.moodle.org/dev/Admin_settings}.

        // TODO A global on/off setting.
        $settings->add(new admin_setting_configcheckbox(
            'local_autotimezone/enabled',
            get_string('enable', 'local_autotimezone'),
            get_string('disable', 'local_autotimezone'),
            0
        ));
        $settings->add(new admin_setting_configduration(
            'local_autotimezone/delay',
            get_string('checkdeferred', 'local_autotimezone'),
            get_string('deferswitchcheckuntil', 'local_autotimezone'),
            24 * HOURSECS,
            HOURSECS
        ));

        // Choose which back end to use timezonedb or local.
        $backends = [
            'backend_timezonedb' => get_string('backend_timezonedb', 'local_autotimezone'),
            'backend_local' => get_string('backend_local', 'local_autotimezone'),
        ];

        $settings->add(new admin_setting_configselect(
            'local_autotimezone/locationbackend',
            get_string('locationbackend', 'local_autotimezone'),
            get_string('locationbackend_desc', 'local_autotimezone'),
            'backend_timezonedb',
            $backends
        ));

        $settings->add(
            new admin_setting_heading(
                'backendlocal',
                get_string('backend_local', 'local_autotimezone'),
                get_string('backend_local_desc', 'local_autotimezone')
            ));

        $settings->add(
            new admin_setting_heading(
                'backendtimezonedb',
                get_string('backend_timezonedb', 'local_autotimezone'),
                get_string('backend_timezonedb_desc', 'local_autotimezone')
            ));
        $settings->add(new admin_setting_configtext(
            'local_autotimezone/timezonedbapikey',
            get_string('timezonedbapikey', 'local_autotimezone'),
            get_string('timezonedbapikey_desc', 'local_autotimezone'),
            ""
        ));
    }
}
