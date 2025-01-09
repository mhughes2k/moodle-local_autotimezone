<?php

$functions = [
    'local_autotimezone_get_current_timezone' => [
        'classname' => 'local_autotimezone\external\get_current_timezone',
        'description' => 'Get the current timezone of the user',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_autotimezone_update_timezone' => [
        'classname' => 'local_autotimezone\external\update_timezone',
        'description' => 'Update user\'s time zone',
        'type' => 'write',
        'ajax' => true,
    ],
] ;
