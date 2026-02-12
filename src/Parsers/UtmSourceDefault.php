<?php

namespace Topoff\LaravelUserLogger\Parsers;

class UtmSourceDefault extends AbstractUtmSource
{
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
