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
 * CLI script to generate timezone_data.json from authoritative sources.
 *
 * This script generates timezone boundary data for the local backend by:
 * 1. Using PHP's built-in timezone database
 * 2. Calculating approximate bounding boxes for major timezones
 * 3. Optionally importing GeoJSON data from timezone-boundary-builder
 *
 * Usage:
 *   php cli/generate_timezone_data.php [--geojson=/path/to/combined.json] [--output=/path/to/output.json]
 *
 * Options:
 *   --geojson   Optional path to timezone-boundary-builder GeoJSON file
 *   --output    Output file path (default: timezone_data.json in plugin root)
 *   --major     Only include major timezones (default: true)
 *   --help      Display this help message
 *
 * @package    local_autotimezone
 * @copyright  2025 University of Strathclyde
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

// Navigate from plugin cli directory to Moodle root config.php.
// Path: cli/ -> local/autotimezone/ -> local/ -> public/ -> moodle root.
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Now get CLI options.
list($options, $unrecognized) = cli_get_params(
    [
        'geojson' => null,
        'output' => null,
        'distribution' => false,
        'major' => true,
        'help' => false,
    ],
    [
        'h' => 'help',
        'o' => 'output',
        'g' => 'geojson',
        'd' => 'distribution',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = <<<EOF
Generate timezone_data.json for local timezone backend.

This script creates timezone boundary data from multiple sources:
- PHP's built-in timezone database (location coordinates)
- Calculated bounding boxes for major timezones
- Optional: GeoJSON data from timezone-boundary-builder project

Usage:
    php cli/generate_timezone_data.php [options]

Options:
    --geojson=PATH      Path to timezone-boundary-builder GeoJSON file
                        (Download from: https://github.com/evansiroky/timezone-boundary-builder/releases)
    --output=PATH       Output file path (default: $CFG->dataroot/local_autotimezone/timezone_data.json)
    --distribution      Output to plugin directory for distribution (maintainers only)
    --major             Only include major timezones (default: true)
    -h, --help          Display this help message

Examples:
    # Generate site-specific data (default, stored in Moodle data directory)
    php cli/generate_timezone_data.php

    # Generate with GeoJSON data for higher precision
    php cli/generate_timezone_data.php --geojson=/tmp/combined.json

    # Generate for distribution (plugin maintainers only)
    php cli/generate_timezone_data.php --distribution

    # Output to custom location
    php cli/generate_timezone_data.php --output=/tmp/custom_tz_data.json

Note:
    By default, generated data is stored in the Moodle data directory and will be
    used in preference to the default distributed data. Use --distribution flag
    only when updating the default timezone data shipped with the plugin.

EOF;
    echo $help;
    exit(0);
}

// Set default output path to Moodle data directory.
if (isset($options['distribution'])) {
    // Output to plugin directory for distribution.
    $outputpath = $options['output'] ?? __DIR__ . '/../timezone_data.json';
    cli_writeln('Distribution mode: Updating default plugin timezone data');
} else {
    // Output to Moodle data directory (site-specific).
    $datadir = $CFG->dataroot . '/local_autotimezone';
    
    // Ensure data directory exists.
    if (!file_exists($datadir)) {
        if (!mkdir($datadir, 0755, true)) {
            cli_error('Failed to create data directory: ' . $datadir);
        }
        cli_writeln('Created data directory: ' . $datadir);
    }
    
    $outputpath = $options['output'] ?? $datadir . '/timezone_data.json';
}

cli_heading('Timezone Data Generator');

/**
 * Main timezone data generator class.
 */
class timezone_data_generator {
    /** @var array Major timezone identifiers we want to include */
    private const MAJOR_TIMEZONES = [
        // North America.
        'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
        'America/Anchorage', 'America/Phoenix', 'Pacific/Honolulu',
        'America/Toronto', 'America/Vancouver', 'America/Edmonton',
        'America/Mexico_City', 'America/Cancun',
        // South America.
        'America/Sao_Paulo', 'America/Argentina/Buenos_Aires', 'America/Santiago',
        'America/Lima', 'America/Bogota', 'America/Caracas',
        // Europe.
        'Europe/London', 'Europe/Dublin', 'Europe/Paris', 'Europe/Berlin',
        'Europe/Madrid', 'Europe/Rome', 'Europe/Amsterdam', 'Europe/Brussels',
        'Europe/Vienna', 'Europe/Zurich', 'Europe/Stockholm', 'Europe/Oslo',
        'Europe/Copenhagen', 'Europe/Helsinki', 'Europe/Athens', 'Europe/Istanbul',
        'Europe/Moscow', 'Europe/Warsaw', 'Europe/Prague', 'Europe/Bucharest',
        // Asia.
        'Asia/Dubai', 'Asia/Riyadh', 'Asia/Karachi', 'Asia/Kolkata',
        'Asia/Dhaka', 'Asia/Bangkok', 'Asia/Jakarta', 'Asia/Singapore',
        'Asia/Manila', 'Asia/Hong_Kong', 'Asia/Shanghai', 'Asia/Tokyo',
        'Asia/Seoul', 'Asia/Taipei', 'Asia/Kuala_Lumpur',
        // Africa.
        'Africa/Cairo', 'Africa/Lagos', 'Africa/Nairobi', 'Africa/Johannesburg',
        'Africa/Casablanca', 'Africa/Algiers',
        // Oceania.
        'Australia/Perth', 'Australia/Adelaide', 'Australia/Brisbane',
        'Australia/Sydney', 'Australia/Melbourne', 'Australia/Hobart',
        'Pacific/Auckland', 'Pacific/Fiji',
        // Atlantic.
        'Atlantic/Reykjavik', 'Atlantic/Azores',
    ];

    /** @var array Known city coordinates for major timezones */
    private const TIMEZONE_CITIES = [
        'America/New_York' => [40.7128, -74.0060],
        'America/Chicago' => [41.8781, -87.6298],
        'America/Denver' => [39.7392, -104.9903],
        'America/Los_Angeles' => [34.0522, -118.2437],
        'America/Phoenix' => [33.4484, -112.0740],
        'America/Anchorage' => [61.2181, -149.9003],
        'Pacific/Honolulu' => [21.3099, -157.8581],
        'America/Toronto' => [43.6532, -79.3832],
        'America/Vancouver' => [49.2827, -123.1207],
        'America/Mexico_City' => [19.4326, -99.1332],
        'America/Sao_Paulo' => [-23.5505, -46.6333],
        'America/Argentina/Buenos_Aires' => [-34.6037, -58.3816],
        'America/Santiago' => [-33.4489, -70.6693],
        'America/Lima' => [-12.0464, -77.0428],
        'America/Bogota' => [4.7110, -74.0721],
        'America/Caracas' => [10.4806, -66.9036],
        'Europe/London' => [51.5074, -0.1278],
        'Europe/Dublin' => [53.3498, -6.2603],
        'Europe/Paris' => [48.8566, 2.3522],
        'Europe/Berlin' => [52.5200, 13.4050],
        'Europe/Madrid' => [40.4168, -3.7038],
        'Europe/Rome' => [41.9028, 12.4964],
        'Europe/Amsterdam' => [52.3676, 4.9041],
        'Europe/Brussels' => [50.8503, 4.3517],
        'Europe/Vienna' => [48.2082, 16.3738],
        'Europe/Zurich' => [47.3769, 8.5417],
        'Europe/Stockholm' => [59.3293, 18.0686],
        'Europe/Oslo' => [59.9139, 10.7522],
        'Europe/Copenhagen' => [55.6761, 12.5683],
        'Europe/Helsinki' => [60.1699, 24.9384],
        'Europe/Athens' => [37.9838, 23.7275],
        'Europe/Istanbul' => [41.0082, 28.9784],
        'Europe/Moscow' => [55.7558, 37.6173],
        'Europe/Warsaw' => [52.2297, 21.0122],
        'Europe/Prague' => [50.0755, 14.4378],
        'Europe/Bucharest' => [44.4268, 26.1025],
        'Asia/Dubai' => [25.2048, 55.2708],
        'Asia/Riyadh' => [24.7136, 46.6753],
        'Asia/Karachi' => [24.8607, 67.0011],
        'Asia/Kolkata' => [28.6139, 77.2090],
        'Asia/Dhaka' => [23.8103, 90.4125],
        'Asia/Bangkok' => [13.7563, 100.5018],
        'Asia/Jakarta' => [-6.2088, 106.8456],
        'Asia/Singapore' => [1.3521, 103.8198],
        'Asia/Manila' => [14.5995, 120.9842],
        'Asia/Hong_Kong' => [22.3193, 114.1694],
        'Asia/Shanghai' => [31.2304, 121.4737],
        'Asia/Tokyo' => [35.6762, 139.6503],
        'Asia/Seoul' => [37.5665, 126.9780],
        'Asia/Taipei' => [25.0330, 121.5654],
        'Asia/Kuala_Lumpur' => [3.1390, 101.6869],
        'Africa/Cairo' => [30.0444, 31.2357],
        'Africa/Lagos' => [6.5244, 3.3792],
        'Africa/Nairobi' => [-1.2921, 36.8219],
        'Africa/Johannesburg' => [-26.2041, 28.0473],
        'Africa/Casablanca' => [33.5731, -7.5898],
        'Africa/Algiers' => [36.7538, 3.0588],
        'Australia/Perth' => [-31.9505, 115.8605],
        'Australia/Adelaide' => [-34.9285, 138.6007],
        'Australia/Brisbane' => [-27.4698, 153.0251],
        'Australia/Sydney' => [-33.8688, 151.2093],
        'Australia/Melbourne' => [-37.8136, 144.9631],
        'Australia/Hobart' => [-42.8821, 147.3272],
        'Pacific/Auckland' => [-36.8485, 174.7633],
        'Pacific/Fiji' => [-18.1416, 178.4419],
        'Atlantic/Reykjavik' => [64.1466, -21.9426],
        'Atlantic/Azores' => [37.7412, -25.6756],
    ];

    /** @var bool Only include major timezones */
    private $majoronly;

    /** @var string|null Path to GeoJSON file */
    private $geojsonpath;

    /**
     * Constructor.
     *
     * @param bool $majoronly Only include major timezones
     * @param string|null $geojsonpath Path to GeoJSON file
     */
    public function __construct(bool $majoronly = true, ?string $geojsonpath = null) {
        $this->majoronly = $majoronly;
        $this->geojsonpath = $geojsonpath;
    }

    /**
     * Generate timezone data.
     *
     * @return array Generated timezone data structure
     */
    public function generate(): array {
        $data = [
            'version' => '1.0',
            'generated' => date('Y-m-d H:i:s'),
            'generator' => 'Moodle local_autotimezone CLI',
            'timezones' => [],
        ];

        if ($this->geojsonpath && file_exists($this->geojsonpath)) {
            cli_writeln('Loading GeoJSON data from: ' . $this->geojsonpath);
            $data['timezones'] = $this->generate_from_geojson();
        } else {
            if ($this->geojsonpath) {
                cli_problem('GeoJSON file not found: ' . $this->geojsonpath);
                cli_writeln('Falling back to calculated boundaries...');
            }
            cli_writeln('Generating from calculated boundaries...');
            $data['timezones'] = $this->generate_from_calculations();
        }

        cli_writeln('Generated ' . count($data['timezones']) . ' timezone entries');

        return $data;
    }

    /**
     * Generate timezone data from GeoJSON file.
     *
     * @return array Array of timezone entries
     */
    private function generate_from_geojson(): array {
        $timezones = [];
        $json = json_decode(file_get_contents($this->geojsonpath), true);

        if (!$json || !isset($json['features'])) {
            cli_error('Invalid GeoJSON format');
            return [];
        }

        $progress = new progress_bar('geojson', 500, true);
        $progress->create();

        $total = count($json['features']);
        $current = 0;

        foreach ($json['features'] as $feature) {
            $current++;
            $progress->update($current, $total, "Processing timezone {$current}/{$total}");

            if (!isset($feature['properties']['tzid']) || !isset($feature['geometry'])) {
                continue;
            }

            $tzid = $feature['properties']['tzid'];

            // Filter to major timezones if requested.
            if ($this->majoronly && !in_array($tzid, self::MAJOR_TIMEZONES)) {
                continue;
            }

            // Calculate bounding box from geometry.
            $bounds = $this->calculate_bounds_from_geometry($feature['geometry']);

            if ($bounds) {
                $timezones[] = [
                    'timezone' => $tzid,
                    'bounds' => $bounds,
                    'description' => $this->get_timezone_description($tzid),
                    'source' => 'geojson',
                ];
            }
        }

        $progress->update($total, $total, 'Complete');

        return $timezones;
    }

    /**
     * Generate timezone data from calculations.
     *
     * @return array Array of timezone entries
     */
    private function generate_from_calculations(): array {
        $timezones = [];
        $tzlist = $this->majoronly ? self::MAJOR_TIMEZONES : DateTimeZone::listIdentifiers();

        $progress = new progress_bar('calculate', 500, true);
        $progress->create();

        $total = count($tzlist);
        $current = 0;

        foreach ($tzlist as $tzid) {
            $current++;
            $progress->update($current, $total, "Processing {$tzid}");

            // Get approximate bounds for this timezone.
            $bounds = $this->calculate_timezone_bounds($tzid);

            if ($bounds) {
                $timezones[] = [
                    'timezone' => $tzid,
                    'bounds' => $bounds,
                    'description' => $this->get_timezone_description($tzid),
                    'source' => 'calculated',
                ];
            }
        }

        $progress->update($total, $total, 'Complete');

        return $timezones;
    }

    /**
     * Calculate bounding box from GeoJSON geometry.
     *
     * @param array $geometry GeoJSON geometry object
     * @return array|null Bounding box [minLat, minLon, maxLat, maxLon] or null
     */
    private function calculate_bounds_from_geometry(array $geometry): ?array {
        $coordinates = [];

        // Extract all coordinates from various geometry types.
        switch ($geometry['type']) {
            case 'Polygon':
                $coordinates = $geometry['coordinates'][0];
                break;
            case 'MultiPolygon':
                foreach ($geometry['coordinates'] as $polygon) {
                    $coordinates = array_merge($coordinates, $polygon[0]);
                }
                break;
            default:
                return null;
        }

        if (empty($coordinates)) {
            return null;
        }

        // Calculate bounding box.
        $lons = array_column($coordinates, 0);
        $lats = array_column($coordinates, 1);

        return [
            round(min($lats), 4),
            round(min($lons), 4),
            round(max($lats), 4),
            round(max($lons), 4),
        ];
    }

    /**
     * Calculate approximate bounds for a timezone.
     *
     * @param string $tzid Timezone identifier
     * @return array|null Bounding box or null if unable to calculate
     */
    private function calculate_timezone_bounds(string $tzid): ?array {
        // Use known city coordinates if available.
        if (isset(self::TIMEZONE_CITIES[$tzid])) {
            list($lat, $lon) = self::TIMEZONE_CITIES[$tzid];
            // Create a box around the city (~500km radius = ~4.5 degrees).
            $margin = 4.5;
            return [
                round($lat - $margin, 4),
                round($lon - $margin, 4),
                round($lat + $margin, 4),
                round($lon + $margin, 4),
            ];
        }

        // Try to infer bounds from timezone name and offset.
        $bounds = $this->infer_bounds_from_name($tzid);
        if ($bounds) {
            return $bounds;
        }

        return null;
    }

    /**
     * Infer approximate bounds from timezone identifier.
     *
     * @param string $tzid Timezone identifier
     * @return array|null Approximate bounds or null
     */
    private function infer_bounds_from_name(string $tzid): ?array {
        // Continental/regional bounds (very rough approximations).
        $regionbounds = [
            'America/North' => [25, -125, 72, -60],
            'America/South' => [-56, -82, 12, -34],
            'America/Central' => [7, -92, 22, -77],
            'Europe/' => [36, -10, 71, 40],
            'Asia/' => [-10, 60, 77, 180],
            'Africa/' => [-35, -18, 37, 52],
            'Australia/' => [-44, 113, -10, 154],
            'Pacific/' => [-50, 130, 60, -100],
            'Atlantic/' => [-60, -45, 70, 0],
            'Indian/' => [-50, 40, 30, 100],
        ];

        foreach ($regionbounds as $prefix => $bounds) {
            if (strpos($tzid, $prefix) === 0) {
                return $bounds;
            }
        }

        return null;
    }

    /**
     * Get human-readable description for timezone.
     *
     * @param string $tzid Timezone identifier
     * @return string Description
     */
    private function get_timezone_description(string $tzid): string {
        $parts = explode('/', $tzid);
        if (count($parts) >= 2) {
            return str_replace('_', ' ', $parts[count($parts) - 1]);
        }
        return $tzid;
    }
}

// Main execution.
try {
    $generator = new timezone_data_generator($options['major'], $options['geojson']);
    $data = $generator->generate();

    cli_writeln('');
    cli_writeln('Writing output to: ' . $outputpath);

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($outputpath, $json) === false) {
        cli_error('Failed to write output file');
    }

    cli_writeln('✓ Timezone data generated successfully!');
    cli_writeln('');
    cli_writeln('File size: ' . round(filesize($outputpath) / 1024, 2) . ' KB');
    cli_writeln('Timezones: ' . count($data['timezones']));
    cli_writeln('');

    exit(0);
} catch (Exception $e) {
    cli_error('Error: ' . $e->getMessage());
}
