<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;

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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kind',
        'value',
    ];
}