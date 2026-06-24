<?php

declare(strict_types=1);

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
     * Allow the logger to run in the "testing" environment.
     * Normally the logger is disabled in tests; enable this in a host app
     * (or package) test suite that explicitly wants to exercise the logger.
     */
    'enabled_in_testing' => env('USER_LOGGER_ENABLED_IN_TESTING', false),

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
     * Optional custom referer database (snowplow referer-parser json format).
     * Defaults to the bundled file generated from
     * matomo/searchengine-and-social-list via `user-logger:update-referers`.
     */
    'referer_data_path' => null,

    /*
     * Ignore Ips
     */
    'ignore_ips' => [],

    /*
     * Skip tracking for requests whose User-Agent contains any of these
     * (case-insensitive) needles. Intended for synthetic traffic that should
     * never reach analytics - e.g. the post-deploy `deploy-warmup` HTTP warm-up
     * that re-fills the FPM OPcache. Matched as a substring, so `deploy-warmup`
     * also covers `deploy-warmup/1.0` etc.
     */
    'ignore_user_agents' => ['deploy-warmup'],

    /*
     * Log the client ip.
     */
    'log_ip' => true,

    /*
     * Hash the client ip before storing (keyed HMAC-SHA256, truncated to
     * 32 chars - see user-logger:haship to compute the stored value).
     * Set to false to store the ip in plain text; in that case configure
     * retention.ip_days so ips are removed after a defined period.
     */
    'hash_ip' => env('USER_LOGGER_HASH_IP', true),

    /*
     * Secret used as HMAC key for ip pseudonymization.
     * Falls back to app.key when null - note that rotating the app key
     * then changes all future hashes.
     */
    'ip_salt' => env('USER_LOGGER_IP_SALT'),

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
        'features' => [],

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
     * Data retention for pruning via `php artisan model:prune`
     * (schedule it with --model for Log / Session / PerformanceLog).
     * 0 disables pruning for that table. Sessions are only pruned
     * when they no longer have any logs - prune logs first.
     */
    'retention' => [
        'logs_days' => 0,
        'sessions_days' => 0,

        /*
         * Days after the last session activity until the stored client ip
         * is removed (the session itself is kept). Applied by the
         * `user-logger:prune-ips` command. 0 disables ip pruning.
         */
        'ip_days' => 0,
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

        /*
         * Fraction of requests that persist a performance log row (0.0 - 1.0).
         * Slow-request warnings are always emitted, regardless of sampling.
         */
        'sample_rate' => 1.0,

        /*
         * Days to keep performance log rows when pruning via
         * `php artisan model:prune --model="Topoff\LaravelUserLogger\Models\PerformanceLog"`.
         * Set 0 to disable pruning.
         */
        'retention_days' => 30,
    ],
];
