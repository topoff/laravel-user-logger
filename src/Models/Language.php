<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Language
 */
class Language extends Model
{
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
    protected $table = 'languages';

    /**
     * The attributes that are guarded from mass assignment.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Can have many Sessions
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}
