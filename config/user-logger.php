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
    'enabled' => env('USER_LOGGER_ENABLED', false),

    /*
     * Log only Events
     */
    'only-events' => env('USER_LOGGER_ONLY_EVENTS', false),

    /*
     * Log robots?
     */
    'log_robots' => false,

    /*
     * Which Users should not be tracked?
     */
    'do_not_track_user_ids' => [
        1, 2
    ],

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
        'admin/*',
        '*.jpg',
        '*.jpeg',
        '*.js',
        '*.css',
        '*.map',
        '*.png',
        '*.gif',
        '.well-known*'
    ],

    /*
     * When one of these routes are called, it must be a robot, mostly attacks
     * -> mark the whole session as suspicious AND robot
     */
    'blacklist_routes' => [
        '*.7z',
        '*.asp',
        '*.aspx',
        '*.bac',
        '*.back',
        '*.cfm',
        '*.bz2',
        '*.cgi',
        '*.dat',
        '*.ch',
        '*.config',
        '*.db',
        '*.e',
        '*.env',
        '*.gz',
        '*.init',
        '*.lz',
        '*.rar',
        '*.sql',
        '*.sqlite',
        '*.sqlitedb',
        '*.tar',
        '*.txt',
        '*.tar.gz',
        '*.tar.z',
        '*.xz',
        '*.z',
        '*.zip',
        '*backup*',
        '*phpmyadmin*',
        '*wp-config*',
        '*wp-login*',
        '*adminer*',
    ],

    /*
     * session_name
     */
    'session_name' => 'user-logger-session',

    /*
     * internal domains, with / without www does matter
     */
    'internal_domains' => [],

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
