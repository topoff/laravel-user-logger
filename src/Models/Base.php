<?php

namespace Topoff\Tracker\Models;

use Illuminate\Database\Eloquent\Model;

abstract class Base extends Model
{
    protected $connection = 'totracker';
}