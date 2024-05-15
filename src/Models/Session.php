<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Session
 */
class Session extends Model
{
    /**
     * Necessary, Because the primary key is a uuid
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'user-logger';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sessions';

    /**
     * The attributes that are guarded from mass assignment.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
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
     * Belongs to one Domain
     */
    public function referer(): BelongsTo
    {
        return $this->belongsTo(Referer::class);
    }

    /**
     * Belongs to one Domain
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
