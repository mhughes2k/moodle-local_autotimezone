# Updating Distributed Timezone Data

This document describes how plugin maintainers should generate updated timezone
boundary data and replace the default `timezone_data.json` shipped with the
plugin.

WARNING: Only plugin maintainers should replace the default distributed file.
Site administrators should generate site-specific data using the CLI generator
and place the generated data in the Moodle data directory (see below).

## Recommended workflow

1. Generate high-precision data using timezone-boundary-builder GeoJSON (optional):

   - Download the combined GeoJSON release (e.g. `combined.json`) from:
     https://github.com/evansiroky/timezone-boundary-builder/releases

   - Run the CLI generator inside the site Docker container:

   ```bash
   docker exec moodle501-webserver-1 php /var/www/html/public/local/autotimezone/cli/generate_timezone_data.php --geojson=/tmp/combined.json --distribution
   ```

   - The `--distribution` flag outputs the generated file to the plugin
     directory (default: `local/autotimezone/timezone_data.json`). This replaces
     the distributed default file that is shipped with the plugin repository.

2. Validate generated data

   - Run local unit tests or manual checks for common coordinates (see
     `LOCAL_BACKEND.md` for examples).
   - Ensure timezone identifiers are valid and there are no large overlapping
     bounding boxes that could reduce accuracy.

3. Commit the updated file

   - Add the updated `timezone_data.json` to git, following the repository
     contribution guidelines and include an entry in `CHANGES.md`.

4. Release

   - Bump plugin version if necessary and tag a release.
   - Update `docs/UPDATING_TIMEZONE_DATA.md` with the date and notes about the
     update if you'd like to track changes.

## Site administrator workflow (do NOT modify distributed file)

Site administrators should generate and use site-specific data stored under
Moodle's data directory. This keeps site-generated data separate from the
distributed plugin files and prevents accidental commits of site-specific
changes.

Example (inside Docker container):

```bash
# Generate site-specific data in Moodle data directory
docker exec moodle501-webserver-1 php /var/www/html/public/local/autotimezone/cli/generate_timezone_data.php

# The generated file will be stored in $CFG->dataroot/local_autotimezone/timezone_data.json
```

Site-specific data will be preferred by the plugin over the distributed default
file.

## Notes

- The CLI generator has a `--distribution` option for plugin maintainers only.
- Generated data should be reviewed carefully before being committed to the
  repository.
- For the highest precision, use GeoJSON polygon data from the
  timezone-boundary-builder project.
