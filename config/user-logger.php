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
    'do_not_track_user_ids' => [1, 2],

    /*
     * Which uri names are not trackable?
     */
    'do_not_track_routes' => [
        'api/auth/sanctum/*',
        'livewire*',
        'telescope*',
        'pulse*',
        'debugbar*',
        'debugbar.*',
        '_debugbar*',
        'log-viewer*',
        'nova*',
        'admin/*',
        'admin-nova/*',
        'admin-filament/*',
        '*.jpg',
        '*.jpeg',
        '*.js',
        '*.css',
        '*.map',
        '*.png',
        '*.gif',
        '.well-known*',
    ],

    /*
     * When one of these routes are called, it must be a robot, mostly attacks
     * -> mark the whole session as suspicious AND robot
     */
    'blacklist_routes' => [
        ' and ',
        ' or ',
        '"',
        '*.7z',
        '*.ash',
        '*.ashx',
        '*.asp',
        '*.aspx',
        '*.bac',
        '*.back',
        '*.bz2',
        '*.cfm',
        '*.cgi',
        '*.ch',
        '*.com',
        '*.config',
        '*.dat',
        '*.db',
        '*.e',
        '*.env',
        '*.gz',
        '*.init',
        '*.jsf',
        '*.jsp',
        '*.lz',
        '*.rar',
        '*.sql',
        '*.sqlite',
        '*.sqlitedb',
        '*.tar',
        '*.tar.gz',
        '*.tar.z',
        '*.tgz',
        '*.txt',
        '*.well-known*',
        '*.xz',
        '*.z',
        '*.zip',
        '*adminer*',
        '*backup*',
        '*error*',
        '*ldap:*',
        '*phpinfo*',
        '*phpmyadmin*',
        '*wp-config*',
        '*wp-content*',
        '*wp-include*',
        '*wp-login*',
        '--',
        '\'',
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
    'path_is_mail' => [],

    /*
     * Ignore Ips
     */
    'ignore_ips' => [],

    /*
     * log ip -> its always hashed
     */
    'log_ip' => true,

    /*
     * Pennant-based experiment measurement
     */
    'experiments' => [
        /*
         * Enable experiment measurement in the logger
         */
        'enabled' => false,

        /*
         * List of Pennant features to measure
         */
        'features' =>  [],

        /*
         * Log events that count as conversions
         */
        'conversion_events' => [
            'conversion',
        ],

        /*
         * Optional conversion entity type whitelist (empty = all)
         */
        'conversion_entity_types' => [],

        /*
         * Auto-register Nova resources when Nova is installed
         */
        'nova' => [
            'enabled' => true,
        ],

        /*
         * Pennant store configuration used by user-logger.
         * This allows multiple apps to share experiment resolutions
         * via the same user-logger database.
         */
        'pennant' => [
            'store' => 'user-logger',
            'connection' => 'user-logger',
            'table' => 'pennant_features',
            'auto_install' => true,

            /*
             * Scope strategy:
             * - session: always use user-logger session id (recommended for multi-app shared DB)
             * - auth_or_session: use authenticated user scope when available, otherwise session id
             */
            'scope' => 'session',
        ],
    ],

    /*
     * debug not parsable Agents, Referers, etc. in debugs table
     */
    'debug' => false,

    /*
     * User-Agent parser optimizations (matomo/device-detector)
     */
    'user_agent' => [
        /*
         * Enable parser cache (uses the app's default Laravel cache store)
         */
        'cache' => true,

        /*
         * Skip DeviceDetector bot matching for faster parsing.
         * Enable only if bot detection is handled elsewhere in your app.
         */
        'skip_bot_detection' => true,
    ],

    /*
     * Performance instrumentation
     */
    'performance' => [
        /*
         * Enable request + user-logger timing logs
         */
        'enabled' => false,

        /*
         * Include query counters in performance logs
         */
        'log_queries' => false,

        /*
         * Emit warning log when request duration is >= this threshold (ms)
         * Set 0 to disable slow-request warnings
         */
        'slow_ms' => 0,
    ],
];
