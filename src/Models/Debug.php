<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $kind
 * @property string|null $value
 */
class Debug extends Model
{
    public $timestamps = false;

    protected $connection = 'user-logger';

    protected $table = 'debugs';

    protected $guarded = [];
}
