<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class AbstractUtmSource
 *
 * Abstract Class for all possible UTM Sources.
 */
abstract class AbstractUtmSource
{
    protected array $attributes = [];

    /**
     * UtmSourceGoogle constructor.
     */
    public function __construct(protected string $url)
    {
        // parse_url returns null/false for missing queries or invalid urls
        parse_str((string) parse_url($this->url, PHP_URL_QUERY), $this->attributes);
    }

    /**
     * Parse
     */
    public function getResult(): RefererResult
    {
        $refererResult = new RefererResult;
        $refererResult->parser = static::class;
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
     * Query parameters can be arrays (e.g. keyword[]=x) - only accept strings.
     */
    protected function attribute(string $key): string
    {
        $value = $this->attributes[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    protected function getUtmSource(): string
    {
        return $this->attribute('utm_source');
    }

    protected function getCampaignId(): string
    {
        return $this->attribute('campaignid');
    }

    protected function getAdgroupId(): string
    {
        return $this->attribute('adgroupid');
    }

    abstract protected function getMatchtype(): string;

    abstract protected function getDevice(): string;

    protected function getKeywords(): string
    {
        return $this->attribute('keyword');
    }

    protected function getAdposition(): string
    {
        return $this->attribute('adposition');
    }

    abstract protected function getNetwork(): string;

    protected function getGclid(): string
    {
        return $this->attribute('gclid');
    }

    /**
     * Is there a utm_source parameter?
     */
    public function hasUtmSource(): bool
    {
        return ! empty($this->attributes['utm_source']);
    }
}
