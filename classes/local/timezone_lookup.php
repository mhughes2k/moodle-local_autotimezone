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

defined('MOODLE_INTERNAL') || die();

/**
 * Timezone lookup utilities for determining timezone from coordinates.
 *
 * This class provides local timezone detection without relying on external APIs.
 * It uses precomputed timezone boundary data and point-in-polygon algorithms.
 *
 * @package    local_autotimezone
 * @copyright  2025 University of Strathclyde
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timezone_lookup {

    /**
     * Look up timezone from coordinates using cached boundary data.
     *
     * This method loads precomputed timezone boundary boxes and checks if the
     * given coordinate falls within any timezone boundary. It uses a combination
     * of bounding box checks and more precise polygon checks for accuracy.
     *
     * @param float $latitude Latitude coordinate (-90 to 90)
     * @param float $longitude Longitude coordinate (-180 to 180)
     * @return string|null Timezone identifier (e.g., 'Europe/London') or null if not found
     */
    public static function lookup(float $latitude, float $longitude): ?string {
        global $CFG;

        // Validate input coordinates.
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            debugging("Invalid coordinates: lat={$latitude}, lon={$longitude}", DEBUG_DEVELOPER);
            return null;
        }

        // Path to timezone data file.
        $datafile = $CFG->dirroot . '/local/autotimezone/timezone_data.json';

        if (!file_exists($datafile)) {
            debugging('Timezone boundary data file not found at: ' . $datafile, DEBUG_DEVELOPER);
            return self::fallback_lookup($latitude, $longitude);
        }

        $data = json_decode(file_get_contents($datafile), true);

        if (!$data || !isset($data['timezones'])) {
            debugging('Invalid timezone data file format', DEBUG_DEVELOPER);
            return self::fallback_lookup($latitude, $longitude);
        }

        // First pass: check bounding boxes for quick filtering.
        $candidates = [];
        foreach ($data['timezones'] as $tzinfo) {
            if (self::point_in_bounds($latitude, $longitude, $tzinfo['bounds'])) {
                $candidates[] = $tzinfo;
            }
        }

        if (empty($candidates)) {
            // No timezone found, use fallback.
            return self::fallback_lookup($latitude, $longitude);
        }

        // If we have multiple candidates, try to find the best match.
        if (count($candidates) > 1) {
            // Check for polygon data if available.
            foreach ($candidates as $tzinfo) {
                if (isset($tzinfo['polygon']) && self::point_in_polygon($latitude, $longitude, $tzinfo['polygon'])) {
                    return $tzinfo['timezone'];
                }
            }
            // If no polygon match, use the first candidate.
            return $candidates[0]['timezone'];
        }

        return $candidates[0]['timezone'];
    }

    /**
     * Check if a point is within a bounding box.
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param array $bounds Bounding box as [minLat, minLon, maxLat, maxLon]
     * @return bool True if point is within bounds
     */
    protected static function point_in_bounds(float $lat, float $lon, array $bounds): bool {
        return $lat >= $bounds[0] && $lat <= $bounds[2] &&
               $lon >= $bounds[1] && $lon <= $bounds[3];
    }

    /**
     * Check if a point is inside a polygon using ray casting algorithm.
     *
     * This implements the even-odd rule algorithm for point-in-polygon testing.
     * A ray is cast from the point to infinity and the number of intersections
     * with the polygon edges is counted. If odd, the point is inside.
     *
     * @param float $lat Latitude of point to test
     * @param float $lon Longitude of point to test
     * @param array $polygon Array of [lat, lon] coordinate pairs defining the polygon
     * @return bool True if point is inside polygon
     */
    protected static function point_in_polygon(float $lat, float $lon, array $polygon): bool {
        $nvert = count($polygon);
        $inside = false;

        for ($i = 0, $j = $nvert - 1; $i < $nvert; $j = $i++) {
            $lati = $polygon[$i][0];
            $loni = $polygon[$i][1];
            $latj = $polygon[$j][0];
            $lonj = $polygon[$j][1];

            if ((($loni > $lon) != ($lonj > $lon)) &&
                ($lat < ($latj - $lati) * ($lon - $loni) / ($lonj - $loni) + $lati)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Fallback timezone lookup using simple longitude-based calculation.
     *
     * This is used when the timezone boundary data is not available or
     * no timezone match is found. It uses a simplified approach based on
     * longitude offset to approximate the timezone.
     *
     * @param float $latitude Latitude coordinate
     * @param float $longitude Longitude coordinate
     * @return string|null Timezone identifier or null if unable to determine
     */
    protected static function fallback_lookup(float $latitude, float $longitude): ?string {
        // Get all available timezones.
        $allzones = \DateTimeZone::listIdentifiers();

        // Calculate approximate timezone offset from longitude.
        // Each 15 degrees of longitude ≈ 1 hour timezone offset.
        $approximatehours = round($longitude / 15);

        // Create a test datetime to check current offsets.
        $testdate = new \DateTime('now', new \DateTimeZone('UTC'));

        $candidates = [];

        foreach ($allzones as $zone) {
            try {
                $tz = new \DateTimeZone($zone);
                $testdate->setTimezone($tz);
                $offset = $testdate->getOffset() / 3600; // Convert seconds to hours.

                // Find zones that match our approximate offset.
                if (abs($offset - $approximatehours) <= 1.5) {
                    $score = self::calculate_zone_score($zone, $latitude, $longitude, $offset, $approximatehours);
                    $candidates[$zone] = $score;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Sort by score (higher is better) and return the best match.
        arsort($candidates);
        return array_key_first($candidates);
    }

    /**
     * Calculate a score for how likely a timezone matches the given coordinates.
     *
     * This scoring algorithm considers:
     * - Offset difference from calculated longitude offset
     * - Continental boundaries based on latitude
     * - Preference for major cities and regions
     *
     * @param string $zone Timezone identifier
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param float $offset Timezone's UTC offset in hours
     * @param float $approximatehours Approximate offset calculated from longitude
     * @return float Score (higher is better match)
     */
    protected static function calculate_zone_score(
        string $zone,
        float $latitude,
        float $longitude,
        float $offset,
        float $approximatehours
    ): float {
        $score = 100;

        // Penalize based on offset difference.
        $offsetdiff = abs($offset - $approximatehours);
        $score -= $offsetdiff * 10;

        // Get timezone location from identifier (e.g., "Europe/London" -> Europe, London).
        $parts = explode('/', $zone);

        // Prefer major cities/regions based on latitude ranges.
        if (count($parts) >= 2) {
            $continent = $parts[0];

            // Continental latitude ranges (rough approximations).
            $continentranges = [
                'Africa' => [-35, 37],
                'America' => [-56, 72],
                'Antarctica' => [-90, -60],
                'Arctic' => [66, 90],
                'Asia' => [-10, 77],
                'Atlantic' => [-60, 70],
                'Australia' => [-55, -10],
                'Europe' => [36, 71],
                'Indian' => [-50, 30],
                'Pacific' => [-55, 60],
            ];

            if (isset($continentranges[$continent])) {
                list($minlat, $maxlat) = $continentranges[$continent];
                if ($latitude >= $minlat && $latitude <= $maxlat) {
                    $score += 20;
                } else {
                    $score -= 30;
                }
            }
        }

        // Prefer non-deprecated timezones (those with proper region/city format).
        if (count($parts) >= 2) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Get a list of all valid timezone identifiers.
     *
     * This is a convenience method to get all timezone identifiers that
     * Moodle/PHP supports.
     *
     * @return array Array of timezone identifier strings
     */
    public static function get_all_timezones(): array {
        return \DateTimeZone::listIdentifiers();
    }

    /**
     * Validate that a timezone identifier is valid.
     *
     * @param string $timezone Timezone identifier to validate
     * @return bool True if timezone is valid
     */
    public static function is_valid_timezone(string $timezone): bool {
        try {
            new \DateTimeZone($timezone);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
