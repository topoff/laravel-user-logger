<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $kind
 * @property string|null $model
 * @property string|null $platform
 * @property string|null $platform_version
 * @property bool $is_mobile
 * @property bool $is_robot
 */
class Device extends Model
{
    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'devices';

    protected $guarded = [];

    protected $casts = [
        'is_mobile' => 'boolean',
        'is_robot' => 'boolean',
    ];

    /**
     * Can have many Sessions
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}
