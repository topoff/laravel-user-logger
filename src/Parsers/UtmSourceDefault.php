<?php

namespace Topoff\LaravelUserLogger\Parsers;

class UtmSourceDefault extends AbstractUtmSource
{

    /**
     * Which class parsed the result
     *
     * @return string
     */
    protected function getClass(): string
    {
        return self::class;
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
    protected function getMatchtype(): string
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