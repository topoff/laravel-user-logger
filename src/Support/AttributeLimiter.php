<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Support;

/**
 * Truncates request-derived attribute values to their DB column limits,
 * so user-controlled input (user agents, utm params, headers) can never
 * fail an insert in strict SQL mode.
 */
class AttributeLimiter
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, int>  $limits
     * @return array<string, mixed>
     */
    public static function apply(array $attributes, array $limits): array
    {
        foreach ($limits as $key => $max) {
            if (isset($attributes[$key]) && is_string($attributes[$key]) && mb_strlen($attributes[$key]) > $max) {
                $attributes[$key] = mb_substr($attributes[$key], 0, $max);
            }
        }

        return $attributes;
    }
}
