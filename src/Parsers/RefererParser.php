<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Snowplow\RefererParser\Medium;
use Snowplow\RefererParser\Parser;
use Snowplow\RefererParser\Referer;

/**
 * Class RefererParser
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
     */
    public function __construct(?string $refererUrl = null, ?string $pageUrl = null)
    {
        if ($refererUrl) {
            $parser = new Parser(null, config('user-logger.internal_domains'));
            $this->referer = $parser->parse($refererUrl, $pageUrl);
        }

        $this->refererUrl = $refererUrl;
    }

    public function getResultFromPartnerUrl(): ?RefererResult
    {
        $refererResult = new RefererResult();

        $refererResult->url = $this->refererUrl;
        $refererResult->domain = $this->refererUrl;
        $refererResult->source = 'partner';
        $refererResult->medium = 'paid';
        $refererResult->campaign = '';
        $refererResult->adgroup = '';
        $refererResult->matchtype = '';
        $refererResult->device = '';
        $refererResult->keywords = '';
        $refererResult->adposition = '';
        $refererResult->network = '';
        $refererResult->gclid = '';
        $refererResult->domain_intern = false;

        return $refererResult;
    }

    /**
     * Delivers the Attributes of the Referer
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

    protected function getSource(): string
    {
        if ($this->referer && $this->referer->isKnown()) {
            return $this->referer->getSource() ?? '';
        } else {
            return '';
        }
    }

    protected function getMedium(): string
    {
        if ($this->referer && $this->referer->isKnown()) {
            return $this->referer->getMedium() ?? '';
        } else {
            return '';
        }
    }

    protected function getKeywords(): string
    {
        if ($this->referer && $this->referer->isKnown()) {
            return $this->referer->getSearchTerm() ?? '';
        } else {
            return '';
        }
    }
}
