<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Snowplow\RefererParser\Medium;
use Snowplow\RefererParser\Parser;
use Snowplow\RefererParser\Referer;

/**
 * Class RefererParser
 *
 * @package Topoff\LaravelUserLogger\Parsers
 */
class RefererParser
{
    /**
     * @var string
     */
    protected $refererUrl;

    /**
     * Referer: Result of Parsing
     *
     * @var Referer
     */
    protected $referer;

    /**
     * RefererParser constructor.
     *
     * @param string      $refererUrl
     * @param string|null $pageUrl
     */
    public function __construct(string $refererUrl = NULL, string $pageUrl = NULL)
    {
        if ($refererUrl) {
            $parser = new Parser(null, config('user-logger.internal_domains'));
            $this->referer = $parser->parse($refererUrl, $pageUrl);
        }

        $this->refererUrl = $refererUrl;
    }

    /**
     * Delivers the Attributes of the Referer
     *
     * @return null|RefererResult
     */
    public function getResult(): ?RefererResult
    {
        $refererResult = new RefererResult();

        $refererResult->url = $this->refererUrl;
        $refererResult->domain = parse_url($this->refererUrl, PHP_URL_HOST);
        if (isset($this->referer) && $this->referer->isKnown() && $this->referer->isValid()) {
            $refererResult->source = $this->getSource();
            $refererResult->medium = $this->getMedium();
            $refererResult->campaign = '';
            $refererResult->adgroup = '';
            $refererResult->matchtype = '';
            $refererResult->device = '';
            $refererResult->keywords = $this->getKeywords();
            $refererResult->adposition = '';
            $refererResult->network = '';
            $refererResult->gclid = '';
            $refererResult->domain_intern = $this->referer->getMedium() === Medium::INTERNAL;
        }

        return $refererResult;
    }

    /**
     * @return string
     */
    protected function getSource(): string
    {
        if ($this->referer && $this->referer->isKnown()) {
            return $this->referer->getSource() ?? '';
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    protected function getMedium(): string
    {
        if ($this->referer && $this->referer->isKnown()) {
            return $this->referer->getMedium() ?? '';
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    protected function getKeywords(): string
    {
        if ($this->referer && $this->referer->isKnown()) {
            return $this->referer->getSearchTerm() ?? '';
        } else {
            return '';
        }
    }
}