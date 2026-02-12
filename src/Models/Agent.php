<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $browser
 * @property string|null $browser_version
 * @property bool $is_robot
 */
class Agent extends Model
{
    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'agents';

    protected $guarded = [];

    protected $casts = [
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
