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
     * Log only Events
     */
    'only-events'             => env('USER_LOGGER_ONLY_EVENTS', false),

    /*
     * Log robots?
     */
    'log_robots'          => false,

    /*
     * Which uri names are not trackable?
     */
    'do_not_track_routes' => [
        'telescope*',
        'debugbar*',
        'debugbar.*',
        '_debugbar*',
        'log-viewer*',
        'nova*',
        'admin*',
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
     * Ignore Ips
     */
    'ignore_ips'          => [],

    /*
     * log ip -> its always hashed
     */
    'log_ip'              => true,

    /*
    * use A/B Testing experiments
    */
    'use_experiments'     => false,

    /*
     * active experiments - max 16 chars
     * crawlers will always run as in the first experiment, but will not be logged
     */
    'experiments'         => [
        'a',
        'b',
    ],

    /*
     * debug not parsable Agents, Referers, etc. in debugs table
     */
    'debug'               => true,
];
