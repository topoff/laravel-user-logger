<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class UtmSourceGoogle
 */
class UtmSourceGoogle extends AbstractUtmSource
{
    /**
     * Devices translation, keys are the values from google delivered
     */
    private array $devices = ['m' => 'mobile', 't' => 'tablet', 'c' => 'desktop'];

    /**
     * Matchtypes translation, keys are the values from google delivered
     */
    private array $matchtypes = ['e' => 'exact', 'p' => 'phrase', 'b' => 'broad'];

    /**
     * Networks translation, keys are the values from google delivered
     */
    private array $networks = ['g' => 'search', 's' => 'network', 'd' => 'display'];

    /**
     * Translates the device
     */
    protected function getDevice(): string
    {
        return $this->devices[$this->attribute('device')] ?? '';
    }

    /**
     * Translates the matchtype
     */
    protected function getMatchtype(): string
    {
        return $this->matchtypes[$this->attribute('matchtype')] ?? '';
    }

    /**
     * Translates the network
     */
    protected function getNetwork(): string
    {
        return $this->networks[$this->attribute('network')] ?? '';
    }
}
