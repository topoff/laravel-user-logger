<?php

namespace Topoff\LaravelUserLogger\Parsers;

class UtmSourceDefault extends AbstractUtmSource
{
    /**
     * Which class parsed the result
     */
    protected function getClass(): string
    {
        return self::class;
    }

    protected function getDevice(): string
    {
        return '';
    }

    protected function getMatchtype(): string
    {
        return '';
    }

    protected function getNetwork(): string
    {
        return '';
    }
}
