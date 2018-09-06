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
     * internal domains, with / without www does matter
     */
    'internal_domains'    => [],

    /*
     * Mark referer as mail, if there is a special keyword in the url path
     */
    'path_is_mail'        => [],

    /*
     * log ip -> its always hashed
     */
    'log_ip'              => true,

    /*
     * debug not parsable Agents, Referers, etc. in debugs table
     */
    'debug'               => true,
];
