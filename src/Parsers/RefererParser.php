<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Snowplow\RefererParser\Parser;
use Snowplow\RefererParser\Referer;

class RefererParser
{
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
            $parser = new Parser();
            $this->referer = $parser->parse($refererUrl, $pageUrl);
        }
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
                    'domain'       => $this->referer->getMedium(),
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
}