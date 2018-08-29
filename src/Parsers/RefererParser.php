<?php

namespace Topoff\LaravelUserLogger\Parsers;

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
     * Referer: Result of Parsing
     *
     * @var Referer
     */
    protected $referer;

    /**
     * @var string
     */
    protected $refererUrl;

    /**
     * RefererParser constructor.
     *
     * @param string      $refererUrl
     * @param string|null $pageUrl
     */
    public function __construct(string $refererUrl = NULL, string $pageUrl = NULL)
    {
        if ($refererUrl) {
            $parser = new Parser();
            $this->referer = $parser->parse($refererUrl, $pageUrl);
        }

        $this->refererUrl = $refererUrl;
    }

    /**
     * Delivers the Attributes of the Referer
     *
     * @return array|null
     */
    public function getRefererAttributes()
    {
        try {
            if ($this->referer && $this->referer->isKnown()) {
                return [
                    'domain'       => $this->getHost(),
                    'medium'       => $this->referer->getMedium(),
                    'source'       => $this->referer->getSource(),
                    'search_terms' => $this->referer->getSearchTerm(),
                ];
            } else {
                return NULL;
            }
        } catch (\Exception $e) {
            return NULL;
        }
    }

    /**
     * Gets the host from the referer Url
     *
     * @return string
     */
    public function getHost(): string
    {
        return parse_url($this->refererUrl, PHP_URL_HOST);
    }
}