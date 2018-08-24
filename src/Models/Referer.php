<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;

class Referer extends Model
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
    protected $connection = 'laravel-user-logger';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'referers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'domain_id',
        'url',
        'host',
        'medium',
        'source',
        'search_terms',
    ];
}