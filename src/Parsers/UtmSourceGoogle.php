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
        if (array_key_exists('device', $this->devices)) {
            return $this->devices[$this->attributes['device']];
        }

        return '';
    }

    /**
     * Translates the matchtype
     */
    protected function getMatchtype(): string
    {
        if (array_key_exists('matchtype', $this->matchtypes)) {
            return $this->matchtypes[$this->attributes['matchtype']];
        }

        return '';
    }

    /**
     * Translates the network
     */
    protected function getNetwork(): string
    {
        if (array_key_exists('network', $this->networks)) {
            return $this->networks[$this->attributes['network']];
        }

        return '';
    }

    /**
     * Which class parsed the result
     */
    protected function getClass(): string
    {
        return self::class;
    }
}
