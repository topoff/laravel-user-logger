<?php

namespace Topoff\Tracker\Models;

use Illuminate\Database\Eloquent\Model;

abstract class Base extends Model
{

    /**
     * Base constructor.
     *
     * @param string $connection
     */
    public function __construct()
    {
        // Funktioniert nicht, weiss nicht warum -> silently fails
//        $this->setConnection(config('tracker.connection'));
    }


}