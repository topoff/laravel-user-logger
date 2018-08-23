<?php

namespace Topoff\Tracker\Support;

use Exception;
use Illuminate\Database\Migrations\Migration as IlluminateMigration;

class Migration extends IlluminateMigration
{
    public $connection;

    /**
     * Migration constructor.
     *
     * @param $app
     *
     * @throws Exception
     */
    public function __construct($app = NULL)
    {
        $app = $app ?? app();
        $config = $app['config'];
        $this->connection = $config->get('tracker.connection');
        if (empty($this->connection)){
            throw new Exception("topoff/Tracker: connection im config file nicht definiert");
        }
    }

}
