<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $session_id
 * @property string $feature
 * @property string|null $variant
 * @property int|null $first_log_id
 * @property int|null $last_log_id
 * @property int $exposure_count
 * @property int $conversion_count
 * @property \Carbon\Carbon|null $first_exposed_at
 * @property \Carbon\Carbon|null $last_exposed_at
 * @property \Carbon\Carbon|null $first_converted_at
 * @property \Carbon\Carbon|null $last_converted_at
 * @property string|null $last_conversion_event
 * @property string|null $last_conversion_entity_type
 * @property string|null $last_conversion_entity_id
 * @property-read float $conversion_rate
 */
class ExperimentMeasurement extends Model
{
    protected $connection = 'user-logger';

    protected $table = 'experiment_measurements';

    protected $guarded = [];

    protected $casts = [
        'exposure_count' => 'integer',
        'conversion_count' => 'integer',
        'first_exposed_at' => 'datetime',
        'last_exposed_at' => 'datetime',
        'first_converted_at' => 'datetime',
        'last_converted_at' => 'datetime',
    ];

    public function getConversionRateAttribute(): float
    {
        if ($this->exposure_count <= 0) {
            return 0.0;
        }

        return round(($this->conversion_count / $this->exposure_count) * 100, 2);
    }
}
