# Automatic Time Zone Switcher #

Local plugin that detects when a user's location does match their time zone and
prompts them to update their time zone setting.

Users can dismiss the prompt for 24 hrs, or they can turn it off via their 
profile settings

## Subscription
The plugin supports two timezone detection backends:

1. **TimeZoneDB API** - Requires a subscription to the TimeZoneDB service
2. **Local Backend** - No subscription required, works completely offline

The Local Backend provides timezone detection without any external API calls or subscription costs.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/autotimezone

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Configuration ##
Once installed you must:

1. Enable the plugin via the settings.
2. Grant the capability `local/autotimezone:config` to the
roles that should be able to use the plugin.

You can grant this to the **Authenticated user** role to allow all users to 
use the tool.

By default the automatic switching is *disabled*.

To enable the automatic switching, go to your user profile, and click on the
"Enable Automatic Time Zone switcher" option.

When enabled, you can return to this page to disable the automatic switching.

### Location Detection ###

There are 2 backend options for determining the user's timezone:

#### TimeZoneDB Backend
The **TimeZoneDB** backend uses the timezoneDB Service to determine the user's timezone from their geolocation. This requires an API key, and may need a Commercial Subscription for use.

**Advantages:**
- High accuracy worldwide
- Comprehensive timezone boundary data
- Regular updates from service provider

**Requirements:**
- API key from timezonedb.com
- Network connectivity
- May require paid subscription

#### Local Backend
The **Local** backend determines the user's timezone from their geolocation without using any external API. This backend is now fully functional and production-ready.

**How it works:**
The local backend uses precomputed timezone boundary boxes covering major cities and regions worldwide. When a user's coordinates are detected, the plugin:
1. Checks the coordinates against timezone boundary boxes
2. Returns the matching timezone identifier
3. Falls back to longitude-based calculation if no exact match is found

**Advantages:**
- No external API required
- No API key or subscription costs
- No network latency
- Works offline
- No API rate limits
- Privacy-friendly (no data sent externally)
- Covers 60+ major timezone regions worldwide

**Limitations:**
- May be less accurate in border regions or remote areas
- Uses simplified bounding boxes rather than precise polygon boundaries
- Timezone data file may need manual updates for new timezone definitions

**Timezone Coverage:**
The local backend includes boundary data for major timezones in:
- North America (US, Canada, Mexico)
- South America (Brazil, Argentina, Chile, etc.)
- Europe (UK, France, Germany, Spain, etc.)
- Asia (China, Japan, India, Singapore, etc.)
- Oceania (Australia, New Zealand)
- Africa (South Africa, Kenya, Nigeria, Egypt)
- Middle East (UAE, Saudi Arabia, Turkey)

## Usage ##
Once enabled, the plugin will check the user's current location and time zone 
and compare to their profile's timezone.

If these are different, the user will be prompted to update their timezone.

You can click anywhere outside of the prompt to dismiss it, ignoring the 
prompt, and it will appear on the next page.

If you click on the **Update** button, your profile will be updated to match 
the detected timezone, and the page will immediately refresh.

If you click on the **Ignore for 24 hrs** button, the prompt will be dismissed 
for 24 hrs, and the check will not be performed until after this period.

## License ##

2025 Univesity of Strathclyde <learning-technologies@strath.ac.uk>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
