<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Debug
 */
class Debug extends Model
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
    protected $table = 'debugs';

    /**
     * The attributes that are guarded from mass assignment.
     *
     * @var array
     */
    protected $guarded = [];
}
