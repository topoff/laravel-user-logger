<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $domain_id
 * @property string|null $url
 * @property string|null $source
 * @property string|null $medium
 * @property string|null $keywords
 * @property string|null $campaign
 * @property string|null $adgroup
 * @property string|null $matchtype
 * @property string|null $device
 * @property string|null $adposition
 * @property string|null $network
 */
class Referer extends Model
{
    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'referers';

    protected $guarded = [];

    /**
     * Can have many Sessions
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Belongs to one Domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
