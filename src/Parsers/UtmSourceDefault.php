<?php

namespace Topoff\LaravelUserLogger\Parsers;

class UtmSourceDefault extends AbstractUtmSource
{

    /**
     * @return string
     */
    protected function getMatchtype(): string
    {
        return '';
    }

    /**
     * @return string
     */
    protected function getDevice(): string
    {
        return '';
    }

    /**
     * @return string
     */
    protected function getNetwork(): string
    {
        return '';
    }
}