<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

class Facade extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return UserLogger::class;
    }
}
