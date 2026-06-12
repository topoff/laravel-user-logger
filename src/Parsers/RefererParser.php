<?php

namespace Topoff\LaravelUserLogger\Parsers;

use ReflectionClass;
use Snowplow\RefererParser\Config\JsonConfigReader;
use Snowplow\RefererParser\Medium;
use Snowplow\RefererParser\Parser;
use Snowplow\RefererParser\Referer;

/**
 * Class RefererParser
 */
class RefererParser
{
    protected ?string $refererUrl;

    protected ?Referer $referer = null;

    /**
     * The snowplow config reader parses a ~120KB json file eagerly on
     * construction - share one parsed instance per PHP process.
     */
    protected static ?JsonConfigReader $configReader = null;

    /**
     * RefererParser constructor.
     */
    public function __construct(?string $refererUrl = null, ?string $pageUrl = null)
    {
        if ($refererUrl) {
            $parser = new Parser(self::configReader(), config('user-logger.internal_domains') ?: []);
            $this->referer = $parser->parse($refererUrl, $pageUrl);
        }

        $this->refererUrl = $refererUrl;
    }

    protected static function configReader(): JsonConfigReader
    {
        return self::$configReader ??= new JsonConfigReader(
            dirname((string) new ReflectionClass(Parser::class)->getFileName()).'/../../../data/referers.json',
        );
    }

    public function getResultFromPartnerUrl(): ?RefererResult
    {
        $refererResult = new RefererResult;

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
        $refererResult = new RefererResult;

        $refererResult->url = $this->refererUrl;
        // parse_url returns false for invalid urls and null for missing hosts
        $refererResult->domain = parse_url((string) $this->refererUrl, PHP_URL_HOST) ?: null;
        if ($this->referer instanceof Referer && $this->referer->isKnown() && $this->referer->isValid()) {
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
        }

        // Internal referers are NOT "known" in snowplow terms (isKnown()
        // excludes Medium::INTERNAL), so the flag must be set outside the
        // known-branch - inside it was dead code and always false.
        if ($this->referer instanceof Referer && $this->referer->isValid()) {
            $refererResult->domain_intern = $this->referer->getMedium() === Medium::INTERNAL;
        }

        return $refererResult;
    }

    protected function getSource(): string
    {
        return $this->referer?->getSource() ?? '';
    }

    protected function getMedium(): string
    {
        return $this->referer?->getMedium() ?? '';
    }

    protected function getKeywords(): string
    {
        return $this->referer?->getSearchTerm() ?? '';
    }
}
