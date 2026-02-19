<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Illuminate\Support\Str;

/**
 * Class UrlPathParser
 */
class UrlPathParser
{
    /**
     * UtmSourceParser constructor.
     */
    public function __construct(protected string $url, protected array $internalHosts = []) {}

    /**
     * Parse
     */
    public function getResult(): ?RefererResult
    {
        $containsAutologin = $this->urlContainsAutologin();
        if ($containsAutologin) {
            $host = parse_url($this->url, PHP_URL_HOST);

            $refererResult = new RefererResult;
            $refererResult->parser = self::class;
            $refererResult->url = $this->url;
            $refererResult->domain = $host;
            $refererResult->source = 'autologin';
            $refererResult->medium = 'email';
            $refererResult->campaign = '';
            $refererResult->adgroup = '';
            $refererResult->matchtype = '';
            $refererResult->device = '';
            $refererResult->keywords = '';
            $refererResult->adposition = '';
            $refererResult->network = '';
            $refererResult->gclid = '';
            $refererResult->domain_intern = in_array($host, $this->internalHosts);

            return $refererResult;
        }

        // If it's not mail source, than the URL shouldn't be used as referer
        // otherwise all request would be loggt as local
        return null;
    }

    /**
     * Check if the url contains a string from config which reveals autologin url
     */
    private function urlContainsAutologin(): bool
    {
        return Str::contains($this->url, config('user-logger.path_is_mail'));
    }

}
