<?php

namespace Topoff\Tracker\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'user-tracker';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_key',
        'user_id',
        'device_id',
        'agent_id',
        'referer_id',
        'cookie_id',
        'language_id',
        'client_ip',
        'is_robot'
    ];
}