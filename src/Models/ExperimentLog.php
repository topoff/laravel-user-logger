<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $session_id
 * @property string|null $experiment
 */
class ExperimentLog extends Model
{
    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'experimentlogs';

    protected $guarded = [];

    protected $casts = [];

    /**
     * Belongs to one session
     */
    public function sessions(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
