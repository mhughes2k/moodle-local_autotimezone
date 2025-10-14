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
 * @copyright   2025 University of Strathclyde <learning-technologies@strath.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_autotimezone_settings', new lang_string('pluginname', 'local_autotimezone'));
    $ADMIN->add('localplugins', $settings);
    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree && $ADMIN->locate('localplugins')) {
        $checkresult = \local_autotimezone\local\hook_callbacks::check_config(true);
        // TODO A global on/off setting.
        $settings->add(new admin_setting_configcheckbox(
            'local_autotimezone/enabled',
            get_string('enable', 'local_autotimezone'),
            get_string('disable', 'local_autotimezone'),
            0
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_autotimezone/datetimeenhancementsenabled',
            get_string('enabledatetimeenhancementsenabled', 'local_autotimezone'),
            get_string('enabledatetimeenhancementsenabled_desc', 'local_autotimezone'),
            0
        ));

        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $fields = $handler->get_fields();
        // var_dump($fields);
        // Extract the shortname and name into a simple array for the options.
        $fieldopts = ['' => get_string('disabled', 'local_autotimezone')];
        foreach ($fields as $field) {
            $fieldopts[$field->get('shortname')] = $field->get('name');
        }
        // Field to use setting.
        $settings->add(new admin_setting_configselect(
            'local_autotimezone/coursetimezonefield',
            get_string('coursetimezonefield', 'local_autotimezone'),
            get_string('coursetimezonefield_desc', 'local_autotimezone'),
            '',
            $fieldopts
        ));

        $settings->add(new admin_setting_configduration(
            'local_autotimezone/delay',
            get_string('checkdeferred', 'local_autotimezone'),
            get_string('deferswitchcheckuntil', 'local_autotimezone'),
            24 * HOURSECS,
            HOURSECS
        ));

        // TODO Add output to the settings page that indicates if the plugin is correctly configured.
        $settings->add(
            new admin_setting_heading(
                'local_autotimezone/configcheck',
                get_string('configcheck', 'local_autotimezone'),
                \html_writer::alist(empty($checkresult) ? [get_string('configok', 'local_autotimezone')] : $checkresult)
            )
        );

        $settings->add(
            new admin_setting_heading(
                'local_autotimezone/servicebackend',
                get_string('locationbackend', 'local_autotimezone'),
                get_string('locationbackend_desc', 'local_autotimezone')
            )
        );


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
