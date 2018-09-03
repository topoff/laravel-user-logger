<?php

namespace Topoff\LaravelUserLogger\Support;

use Exception;
use Illuminate\Database\Migrations\Migration as IlluminateMigration;

class Migration extends IlluminateMigration
{
    public $connection;

    /**
     * Migration constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->connection = 'user-logger';
    }

}
