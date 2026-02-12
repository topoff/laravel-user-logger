<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class AbstractUtmSource
 *
 * Abstract Class for all possible UTM Sources.
 */
abstract class AbstractUtmSource
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * UtmSourceGoogle constructor.
     */
    public function __construct(protected string $url)
    {
        parse_str(parse_url($this->url, PHP_URL_QUERY), $this->attributes);
    }

    /**
     * Parse
     */
    public function getResult(): RefererResult
    {
        $refererResult = new RefererResult;
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

    protected function getUtmSource(): string
    {
        return $this->attributes['utm_source'] ?? '';
    }

    protected function getCampaignId(): string
    {
        return $this->attributes['campaignid'] ?? '';
    }

    protected function getAdgroupId(): string
    {
        return $this->attributes['adgroupid'] ?? '';
    }

    abstract protected function getMatchtype(): string;

    abstract protected function getDevice(): string;

    protected function getKeywords(): string
    {
        return $this->attributes['keyword'] ?? '';
    }

    protected function getAdposition(): string
    {
        return $this->attributes['adposition'] ?? '';
    }

    abstract protected function getNetwork(): string;

    protected function getGclid(): string
    {
        return $this->attributes['gclid'] ?? '';
    }

    /**
     * Is there a utm_source parameter?
     */
    public function hasUtmSource(): bool
    {
        return ! empty($this->attributes['utm_source']);
    }

    abstract protected function getClass(): string;
}
