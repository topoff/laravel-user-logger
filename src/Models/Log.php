<?php

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $session_id
 * @property int|null $domain_id
 * @property int|null $uri_id
 * @property string|null $event
 * @property string|null $entity_type
 * @property string|null $entity_id
 * @property string|null $comment
 * @property string|null $created_at
 */
class Log extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'logs';

    protected $guarded = [];

    /**
     * Prune via `php artisan model:prune --model="Topoff\LaravelUserLogger\Models\Log"`.
     * A retention of 0 days disables pruning.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        $days = (int) config('user-logger.retention.logs_days', 0);
        if ($days <= 0) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('created_at', '<', now()->subDays($days));
    }

    /**
     * Belongs to one Session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Belongs to one URI
     */
    public function uri(): BelongsTo
    {
        return $this->belongsTo(Uri::class);
    }

    /**
     * Belongs to one Domain
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
