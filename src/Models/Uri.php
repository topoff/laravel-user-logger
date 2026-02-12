<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uri
 */
class Uri extends Model
{
    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'uris';

    protected $guarded = [];

    /**
     * Can have many Logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }
}
