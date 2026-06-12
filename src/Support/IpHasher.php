<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Support;

/**
 * Pseudonymizes client ips with a keyed HMAC-SHA256, truncated to 32 chars
 * to fit the sessions.client_ip column.
 */
class IpHasher
{
    public static function hash(string $clientIp): string
    {
        $key = (string) (config('user-logger.ip_salt') ?: config('app.key'));

        return substr(hash_hmac('sha256', $clientIp, $key), 0, 32);
    }
}
