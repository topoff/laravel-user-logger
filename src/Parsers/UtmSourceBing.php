<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class UtmSourceGoogle
 *
 * @package Topoff\LaravelUserLogger\Parsers
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
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        parent::__construct($url);
    }

    /**
     * @return string
     */
    protected function getAdgroupId(): string
    {
        return $this->attributes['utm_content'] ?? '';
    }

    /**
     * @return string
     */
    protected function getCampaignId(): string
    {
        return $this->attributes['utm_campaign'] ?? '';
    }

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
     * Translates the device
     *
     * @return string
     */
    protected function getDevice(): string
    {
        if (array_key_exists('device', $this->devices)) {
            return $this->devices[$this->attributes['device']];
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    protected function getKeywords(): string
    {
        return $this->attributes['utm_term'] ?? '';
    }

    /**
     * Translates the matchtype
     *
     * @return string
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
     *
     * @return string
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