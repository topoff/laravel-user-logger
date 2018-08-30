<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Illuminate\Http\Request;

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
     * @var Request
     */
    protected $request;

    /**
     * UtmSourceGoogle constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Parse
     *
     * @return RefererResult
     */
    public function getResult(): RefererResult
    {
        $refererResult = new RefererResult();
        $refererResult->parser = self::class;
        $refererResult->url = $this->request->fullUrl();
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
     * Is there a utm_source parameter?
     *
     * @return bool
     */
    public function hasUtmSource(): bool
    {
        return !empty($this->request->get('utm_source'));
    }

    /**
     * @return string
     */
    protected function getUtmSource(): string
    {
        return $this->request->get('utm_source') ?? '';
    }

    /**
     * @return string
     */
    protected function getCampaignId(): string
    {
        return $this->request->get('campaignid') ?? '';
    }

    /**
     * @return string
     */
    protected function getAdgroupId(): string
    {
        return $this->request->get('adgroupid') ?? '';
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
        return $this->request->get('keyword') ?? '';
    }

    /**
     * @return string
     */
    protected function getAdposition(): string
    {
        return $this->request->get('adposition') ?? '';
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
        return $this->request->get('gclid') ?? '';
    }
}