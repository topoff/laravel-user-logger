<?php

namespace Topoff\Tracker\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
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
    protected $connection = 'user-tracker';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'devices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kind',
        'model',
        'platform',
        'platform_version',
        'is_mobile',
        'is_robot',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_mobile' => 'boolean',
        'is_robot'  => 'boolean',
    ];
}