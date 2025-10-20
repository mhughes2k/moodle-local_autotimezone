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
$string['configcheck'] = 'Configuration Check';
$string['configok'] = 'Basic Configuration OK';
$string['cntype:banner'] = 'Banner at top of course page';
$string['cntype:usermenu'] = 'Item in user menu';
$string['coursenotification'] = 'Display in-course notification';
$string['coursenotification_desc'] = "Display a notification on course pages if user's timezone doesn't match the course's timezone.";
$string['coursenotification_link'] = 'https://mhughes2k.github.io/moodle-local_autotimezone/help/coursenotification';
$string['coursenotificationtype'] = 'Notification type';
$string['coursenotificationtype_desc'] = 'Select how the notification is displayed in-course.';
$string['coursetimezonefield'] = 'Course timezone field';
$string['coursetimezonefield_desc'] = "Select the course custom field that holds the timezone value for the course's location.";
$string['coursetimezoneis'] = 'Course timezone is {$a->coursetz}.';
$string['deferswitchcheckuntil'] = 'Automatic Time Zone Switch check delayed until after {$a}.';
$string['disable'] = 'Disable Automatic Time Zone switcher';
$string['disabled'] = 'Disabled';
$string['enable'] = 'Enable Automatic Time Zone switcher';
$string['enabledatetimeenhancementsenabled'] = 'Enable Date-Time selector enhancements';
$string['enabledatetimeenhancementsenabled_desc'] = 'Enable Timezone enhancements on date-time pickers.';
$string['fieldnotset'] = 'Course Time Zone Field has not been selected.';
$string['howtocreatefield'] = 'You can create this field via the "Course Customfields" settings.';
$string['ignoreforXhrs'] = 'Ignore for {$a->delay} hours';
$string['locationbackend'] = 'Location Backend';
$string['locationbackend_desc'] = 'The Location Backend service is used to determine the user\'s timezone based on their location';
$string['missingfield'] = 'The custom course field "{$a}" is missing. Please create it.';
$string['nextcheck'] = 'Check timezone again after';
$string['pausechecking'] = 'Pause ({$a})';
$string['plugin_disabled'] = 'Plugin disabled';
$string['pluginname'] = 'Automatic Time Zone Switcher';
$string['privacy:metadata'] = 'The Automatic Time Zone Switcher plugin access Browser Geolocation Sensor data & existing User Profile Timezone data, it does not store any personal data.';
$string['resumechecking'] = 'Resume Checks';
$string['servermoduletimezonemismatch'] = "Module timezone ({\$a->coursetz}) is different to server's timezone ({\$a->servertz}).";
$string['shownotificationforcourseserverconflict'] = 'Show Course - Server time zone mismatch';
$string['shownotificationforcourseserverconflict_desc'] = "Show notification if course timezone and server timezone don't match.

Requires **Display in-course notification** to be enabled.";
$string['timezoneconflicthelp'] = 'Time Zone Conflict';
$string['timezoneconflicthelp_help'] = "Your time zone is different from the either the server or the course.

All times are displayed according to your profile's timezone, and when you enter a date/time you are entering it according to your profile's timezone.

This can lead to mis-match if your time zone is set to \"Europe/London\", but the course (and the users on it) are anticipating \"Asia/Bahrain\".";
$string['timezonedbapikey'] = 'Timezone DB API Key';
$string['timezonedbapikey_desc'] = 'Timezone DB API Key';
$string['timezonewarning'] = 'Timezonewarning';
$string['unabletodeterminetimezonefromlocation'] = 'Unable to determine timezone from location.';
$string['updatemodalbody'] = '<p>Your current location <strong>{$a->currentTz}</strong> does not match your profile\'s time zone <strong>{$a->profileTz}</strong>.</p>';
$string['updatemodaltitle'] = 'Update Timezone';
$string['updatemodalupdatebutton'] = 'Update Timezone';
$string['updatetimezone'] = 'Update Timezone';
$string['updatetimezoneto'] = 'Update Timezone to {$a->currentTz}';
$string['usermenu:timezoneconflictindicator'] = 'Timezone Conflict';
$string['usermoduletimezonemismatch'] = 'Your timezone ({$a->usertz}) does not match course timezone ({$a->coursetz}).';
$string['warnontimezoneswitch'] = 'Display if timezone doesn\'t match profile timezone';
$string['youaresettingtimezone'] = 'You are setting the time in "{$a->usertz}" timezone!';

