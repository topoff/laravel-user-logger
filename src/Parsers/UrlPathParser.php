<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class UtmSourceParser
 *
 * @package Topoff\LaravelUserLogger\Parsers
 */
class UrlPathParser
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
     * @var array
     */
    protected $internalHosts;

    /**
     * UtmSourceParser constructor.
     *
     * @param string $url
     * @param array  $internalHosts
     */
    public function __construct(string $url, array $internalHosts = [])
    {
        $this->url = $url;
        $this->internalHosts = $internalHosts;
    }

    /**
     * Parse
     *
     * @return RefererResult
     */
    public function getResult(): ?RefererResult
    {
        if ($this->urlContainsAutologin()) {
            $host = parse_url($this->url, PHP_URL_HOST);

            $refererResult = new RefererResult();
            $refererResult->parser = self::class;
            $refererResult->url = $this->url;
            $refererResult->domain = $host;
            $refererResult->source = $this->getSource();
            $refererResult->medium = $this->getMedium();
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
        } else {
            // If it's not mail source, than the URL shouldn't be used as referer
            // otherwise all request would be loggt as local
            return NULL;
        }
    }

    /**
     * Check if the url contains a string from config which reveals autologin url
     *
     * @return bool
     */
    private function urlContainsAutologin(): bool
    {
        return (str_contains($this->url, config('user-logger.path_is_mail')));
    }

    /**
     * @return null|string
     */
    private function getSource(): ?string
    {
        return $this->urlContainsAutologin() ? 'email' : NULL;
    }

    /**
     * @return null|string
     */
    private function getMedium(): ?string
    {
        return $this->urlContainsAutologin() ? 'autologin' : NULL;
    }
}