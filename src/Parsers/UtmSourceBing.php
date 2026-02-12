<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class UtmSourceGoogle
 */
class UtmSourceBing extends AbstractUtmSource
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
    private array $networks = ['o' => 'search', 's' => 'network'];

    protected function getAdgroupId(): string
    {
        return $this->attributes['utm_content'] ?? '';
    }

    protected function getCampaignId(): string
    {
        return $this->attributes['utm_campaign'] ?? '';
    }

    /**
     * Which class parsed the result
     */
    protected function getClass(): string
    {
        return self::class;
    }

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

    protected function getKeywords(): string
    {
        return $this->attributes['utm_term'] ?? '';
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
}
