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
 * Privacy Provider for local_autotimezone
 * @copyright 2025 University of Strathclyde <learning-technologies@strath.ac.uk>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_autotimezone
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Return the reason for the privacy provider.
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
