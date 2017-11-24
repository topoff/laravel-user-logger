<?php

namespace Topoff\Tracker\Models;

use Illuminate\Database\Eloquent\Model;

class Base extends Model
{
    /**
     * Base constructor.
     */
    public function __construct() {

        $app = $app ?? app();
        $this->setConnection(($app['config'])->get('tracker.connection2'));
    }
}