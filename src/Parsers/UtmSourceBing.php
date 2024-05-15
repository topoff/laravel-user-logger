<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class UtmSourceGoogle
 */
class UtmSourceBing extends AbstractUtmSource
{
    /**
     * Devices translation, keys are the values from google delivered
     *
     * @var array
     */
    private $devices = ['m' => 'mobile', 't' => 'tablet', 'c' => 'desktop'];

    /**
     * Matchtypes translation, keys are the values from google delivered
     *
     * @var array
     */
    private $matchtypes = ['e' => 'exact', 'p' => 'phrase', 'b' => 'broad'];

    /**
     * Networks translation, keys are the values from google delivered
     *
     * @var array
     */
    private $networks = ['o' => 'search', 's' => 'network'];

    /**
     * UtmSourceGoogle constructor.
     */
    public function __construct(string $url)
    {
        parent::__construct($url);
    }

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
        } else {
            return '';
        }
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
        } else {
            return '';
        }
    }

    /**
     * Translates the network
     */
    protected function getNetwork(): string
    {
        if (array_key_exists('network', $this->networks)) {
            return $this->networks[$this->attributes['network']];
        } else {
            return '';
        }
    }
}
