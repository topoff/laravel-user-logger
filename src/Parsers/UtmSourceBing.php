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
        return $this->attribute('utm_content');
    }

    #[Override]
    protected function getCampaignId(): string
    {
        return $this->attribute('utm_campaign');
    }

    /**
     * Translates the device
     */
    protected function getDevice(): string
    {
        return $this->devices[$this->attribute('device')] ?? '';
    }

    #[Override]
    protected function getKeywords(): string
    {
        return $this->attribute('utm_term');
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
