<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property int $id
 * @property string $session_id
 * @property string $feature
 * @property string|null $variant
 * @property string $variant_key
 * @property int|null $first_log_id
 * @property int|null $last_log_id
 * @property int $exposure_count
 * @property int $conversion_count
 * @property Carbon|null $first_exposed_at
 * @property Carbon|null $last_exposed_at
 * @property Carbon|null $first_converted_at
 * @property Carbon|null $last_converted_at
 * @property string|null $last_conversion_event
 * @property string|null $last_conversion_entity_type
 * @property string|null $last_conversion_entity_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read float $conversion_rate
 */
class ExperimentMeasurement extends Model
{
    protected $connection = 'user-logger';

    protected $table = 'experiment_measurements';

    protected $guarded = [];

    /**
     * Injective encoding of variant for the unique index: '' stands for NULL,
     * real values get a 'v' prefix — so a genuine '' variant (key 'v') can
     * never collide with NULL (key ''). Needed because NULLs never conflict
     * in a unique index, which let the parallel-insert race duplicate
     * NULL-variant rows. The migration's SQL backfill mirrors this encoding.
     */
    public static function variantKeyFor(?string $variant): string
    {
        return $variant === null ? '' : 'v'.$variant;
    }

    /**
     * variant_key is derived on every save so no writer can forget it.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saving(function (self $measurement): void {
            $measurement->variant_key = self::variantKeyFor($measurement->variant);
        });
    }

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
