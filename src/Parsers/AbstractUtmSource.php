<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class AbstractUtmSource
 *
 * Abstract Class for all possible UTM Sources.
 *
 * @package Topoff\LaravelUserLogger\Parsers
 */
abstract class AbstractUtmSource
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var string
     */
    protected $url;

    /**
     * UtmSourceGoogle constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->url = $url;
        parse_str(parse_url($url, PHP_URL_QUERY), $this->attributes);
    }

    /**
     * Parse
     *
     * @return RefererResult
     */
    public function getResult(): RefererResult
    {
        $refererResult = new RefererResult();
        $refererResult->parser = $this->getClass();
        $refererResult->url = $this->url;
        $refererResult->domain = $this->getUtmSource();
        $refererResult->source = $this->getUtmSource();
        $refererResult->medium = 'paid';
        $refererResult->campaign = $this->getCampaignId();
        $refererResult->adgroup = $this->getAdgroupId();
        $refererResult->matchtype = $this->getMatchtype();
        $refererResult->device = $this->getDevice();
        $refererResult->keywords = $this->getKeywords();
        $refererResult->adposition = $this->getAdposition();
        $refererResult->network = $this->getNetwork();
        $refererResult->gclid = $this->getGclid();

        return $refererResult;
    }

    /**
     * @return string
     */
    protected function getUtmSource(): string
    {
        return $this->attributes['utm_source'] ?? '';
    }

    /**
     * @return string
     */
    protected function getCampaignId(): string
    {
        return $this->attributes['campaignid'] ?? '';
    }

    /**
     * @return string
     */
    protected function getAdgroupId(): string
    {
        return $this->attributes['adgroupid'] ?? '';
    }

    /**
     * @return string
     */
    protected abstract function getMatchtype(): string;

    /**
     * @return string
     */
    protected abstract function getDevice(): string;

    /**
     * @return string
     */
    protected function getKeywords(): string
    {
        return $this->attributes['keyword'] ?? '';
    }

    /**
     * @return string
     */
    protected function getAdposition(): string
    {
        return $this->attributes['adposition'] ?? '';
    }

    /**
     * @return string
     */
    protected abstract function getNetwork(): string;

    /**
     * @return string
     */
    protected function getGclid(): string
    {
        return $this->attributes['gclid'] ?? '';
    }

    /**
     * Is there a utm_source parameter?
     *
     * @return bool
     */
    public function hasUtmSource(): bool
    {
        return !empty($this->attributes['utm_source']);
    }

    abstract protected function getClass(): string;
}