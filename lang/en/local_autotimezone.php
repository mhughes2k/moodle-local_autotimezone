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
 * Plugin strings are defined here.
 *
 * @package     local_autotimezone
 * @category    string
 * @copyright   2025 Univesity of Strathclyde <learning-technologies@strath.ac.uk>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['autotimezone:use'] = 'Allow users to use the Automatic Time Zone Switcher';
$string['backend_local'] = 'Local Backend';
$string['backend_local_desc'] = 'Local Backend';
$string['backend_timezonedb'] = 'TimeZoneDB Backend';
$string['backend_timezonedb_desc'] = 'Use the <a href="https://timezonedb.com/">https://timezonedb.com/</a> API to get the timezone.

This requires registration and an API key to be provided.

A commercial API key is necessary to use this service.';
$string['checkdeferred'] = 'Automatic Time Zone switching paused';
$string['deferswitchcheckuntil'] = 'Automatic Time Zone Switch check delayed until after {$a}.';
$string['disable'] = 'Disable Automatic Time Zone switcher';
$string['enable'] = 'Enable Automatic Time Zone switcher';
$string['ignoreforXhrs'] = 'Ignore for {$a->delay} hours';
$string['locationbackend'] = 'Location Backend';
$string['locationbackend_desc'] = 'The Location Backend service is used to determine the user\'s timezone based on their location';
$string['pluginname'] = 'Automatic Time Zone Switcher';
$string['privacy:metadata'] = 'The Automatic Time Zone Switcher plugin access Browser Geolocation Sensor data & existing User Profile Timezone data, it does not store any personal data.';
$string['timezonedbapikey'] = 'Timezone DB API Key';
$string['timezonedbapikey_desc'] = 'Timezone DB API Key';
$string['updatemodalbody'] = '<p>Your current location <strong>{$a->currentTz}</strong> does not match your profile\'s time zone <strong>{$a->profileTz}</strong>.</p>';
$string['updatemodaltitle'] = 'Update Timezone';
$string['updatemodalupdatebutton'] = 'Update Timezone';
$string['updatetimezone'] = 'Update Timezone';
$string['updatetimezoneto'] = 'Update Timezone to {$a->currentTz}';
$string['warnontimezoneswitch'] = 'Display if timezone doesn\'t match profile timezone';

