<?php

return [

    /*
     |--------------------------------------------------------------------------
     | UserLogger Settings
     |--------------------------------------------------------------------------
     |
     */

    /*
     * Enable User Logger
     */
    'enabled'             => env('USER_LOGGER_ENABLED', false),

    /*
     * Log robots?
     */
    'log_robots'          => false,

    /*
     * Which uri names are not trackable?
     */
    'do_not_track_routes' => [
        'debugbar*',
        'debugbar.*',
        '_debugbar*',
        'log-viewer*',
        'admin*',
        '*.jpg',
        '*.jpeg',
        '*.js',
        '*.css',
        '*.map',
    ],

    /*
     * session_name
     */
    'session_name' => 'user-logger-session',
];
