<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class UtmSourceParser
 */
class UtmSourceParser
{
    protected array $attributes = [];

    /**
     * UtmSourceParser constructor.
     *
     * @param  string  $url  full url or query string with ? in the beginning
     */
    public function __construct(protected string $url)
    {
        // parse_url returns null/false for missing queries or invalid urls
        parse_str((string) parse_url($this->url, PHP_URL_QUERY), $this->attributes);
    }

    /**
     * Gets the Result from the utm_source parameter, chooses
     * the right Source (google, bing, etc)
     */
    public function getResult(): ?RefererResult
    {
        if ($this->hasUtmSource()) {
            $source = match ($this->attributes['utm_source']) {
                'google' => new UtmSourceGoogle($this->url),
                'bing' => new UtmSourceBing($this->url),
                default => new UtmSourceDefault($this->url),
            };

            return $source->getResult();
        }

        return null;
    }

    /**
     * Is there a utm_source parameter?
     * Array values (e.g. utm_source[]=x) are treated as absent.
     */
    public function hasUtmSource(): bool
    {
        return isset($this->attributes['utm_source'])
            && is_string($this->attributes['utm_source'])
            && $this->attributes['utm_source'] !== '';
    }
}
