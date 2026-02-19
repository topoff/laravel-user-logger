<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Override;

/**
 * Class UtmSourceBing
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

    #[Override]
    protected function getAdgroupId(): string
    {
        return $this->attributes['utm_content'] ?? '';
    }

    #[Override]
    protected function getCampaignId(): string
    {
        return $this->attributes['utm_campaign'] ?? '';
    }

    /**
     * Translates the device
     */
    protected function getDevice(): string
    {
        if (isset($this->attributes['device']) && array_key_exists($this->attributes['device'], $this->devices)) {
            return $this->devices[$this->attributes['device']];
        }

        return '';
    }

    #[Override]
    protected function getKeywords(): string
    {
        return $this->attributes['utm_term'] ?? '';
    }

    /**
     * Translates the matchtype
     */
    protected function getMatchtype(): string
    {
        if (isset($this->attributes['matchtype']) && array_key_exists($this->attributes['matchtype'], $this->matchtypes)) {
            return $this->matchtypes[$this->attributes['matchtype']];
        }

        return '';
    }

    /**
     * Translates the network
     */
    protected function getNetwork(): string
    {
        if (isset($this->attributes['network']) && array_key_exists($this->attributes['network'], $this->networks)) {
            return $this->networks[$this->attributes['network']];
        }

        return '';
    }
}
