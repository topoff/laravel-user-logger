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
        '*.jpg',
        '*.jpeg',
        '*.js',
        '*.css',
        '*.map',
        '*.png',
        '*.gif',
    ],

    /*
     * session_name
     */
    'session_name'        => 'user-logger-session',

    /*
     * log ip -> its always hashed
     */
    'log_ip'              => true,

];
