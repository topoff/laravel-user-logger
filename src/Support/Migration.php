<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Support;

use Illuminate\Database\Migrations\Migration as IlluminateMigration;

class Migration extends IlluminateMigration
{
    public $connection = 'user-logger';
}
