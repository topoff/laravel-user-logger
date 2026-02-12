<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $preference
 * @property string|null $range
 */
class Language extends Model
{
    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'languages';

    protected $guarded = [];

    /**
     * Can have many Sessions
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}
