<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property int|null $user_id
 * @property int|null $device_id
 * @property int|null $agent_id
 * @property int|null $referer_id
 * @property int|null $language_id
 * @property string|null $client_ip
 * @property bool $is_robot
 * @property bool $is_suspicious
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Device|null $device
 * @property-read Agent|null $agent
 * @property-read Referer|null $referer
 * @property-read Language|null $language
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExperimentMeasurement> $experimentMeasurements
 */
class Session extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'sessions';

    protected $guarded = [];

    protected $casts = [
        'is_robot' => 'boolean',
        'is_suspicious' => 'boolean',
    ];

    public function isRobot(): bool
    {
        return $this->is_robot;
    }

    public function isNoRobot(): bool
    {
        return ! $this->is_robot;
    }

    public function isSuspicious(): bool
    {
        return $this->is_suspicious;
    }

    public function isNotSuspicious(): bool
    {
        return ! $this->is_suspicious;
    }

    /**
     * Can have many Logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Can have many Experiment Measurements
     */
    public function experimentMeasurements(): HasMany
    {
        return $this->hasMany(ExperimentMeasurement::class);
    }

    /**
     * Belongs to one Device
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Belongs to one Agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Belongs to one Referer
     */
    public function referer(): BelongsTo
    {
        return $this->belongsTo(Referer::class);
    }

    /**
     * Belongs to one Language
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
