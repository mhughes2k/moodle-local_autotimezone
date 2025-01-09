<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$state = required_param('enable', PARAM_INT);

set_user_preference('local_autotimezone_enabled', $state);
// If we're turning on we set next check to be 0 so that checks happen.
if ($state = 1) {
    set_user_preference('local_autotimezone_nextcheck', 0);
}
redirect(new \moodle_url('/user/profile.php'));
