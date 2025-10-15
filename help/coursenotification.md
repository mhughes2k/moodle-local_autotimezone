# In Course Notifications

In-course notifications are turned on via the `coursenotificationenabled` settting in the plugin settings.

When enabled, if a user is viewing a course that has a different timezone to their own timezone, they will see a notification at the top of the course page.

By default only a notification about there being a mismatch between the course's timezone and the user's profile timezone is displayed.

Optionally, a notification can be shown if the course's timezone doesn't match the server's default timezone.

## Prerequisites

* Autotimezone plugin must be enabled.
* A Course timezone field must be selected, and hold valid "timezone identifiers" (e.g. "America/New_York", "Europe/London", etc).

