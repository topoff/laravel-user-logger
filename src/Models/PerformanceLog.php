<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $path
 * @property string|null $method
 * @property int|null $status
 * @property bool $booted
 * @property string|null $skip_reason
 * @property float $request_duration_ms
 * @property float|null $boot_duration_ms
 * @property int|null $queries_total
 * @property int|null $queries_user_logger
 * @property int|null $log_id
 * @property int|null $domain_id
 * @property array<string, float>|null $user_logger_segments
 * @property array<string, int>|null $user_logger_counters
 * @property array<string, mixed>|null $user_logger_meta
 * @property \Carbon\Carbon|null $created_at
 */
class PerformanceLog extends Model
{
    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'performance_logs';

    protected $guarded = [];

    protected $casts = [
        'booted' => 'boolean',
        'request_duration_ms' => 'float',
        'boot_duration_ms' => 'float',
        'queries_total' => 'integer',
        'queries_user_logger' => 'integer',
        'log_id' => 'integer',
        'domain_id' => 'integer',
        'user_logger_segments' => 'array',
        'user_logger_counters' => 'array',
        'user_logger_meta' => 'array',
        'created_at' => 'datetime',
    ];
}
