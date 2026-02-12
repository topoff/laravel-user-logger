<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $local
 */
class Domain extends Model
{
    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'domains';

    protected $guarded = [];

    protected $casts = [
        'local' => 'boolean',
    ];

    /**
     * Can have many Logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }
}
