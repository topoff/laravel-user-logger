<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class UtmSourceParser
 *
 * @package Topoff\LaravelUserLogger\Parsers
 */
class UtmSourceParser
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * UtmSourceParser constructor.
     *
     * @param string $url full url or query string with ? in the beginning
     */
    public function __construct(string $url)
    {
        $this->url = $url;
        parse_str(parse_url($url, PHP_URL_QUERY), $this->attributes);
    }

    /**
     * Gets the Result from the utm_source parameter, chooses
     * the right Source (google, bing, etc)
     *
     * @return null|RefererResult
     */
    public function getResult(): ?RefererResult
    {
        if ($this->hasUtmSource()) {
            switch ($this->attributes['utm_source']) {
                case 'google':
                    $source = new UtmSourceGoogle($this->url);
                    break;

                case 'bing':
                    $source = new UtmSourceBing($this->url);
                    break;

                default:
                    $source = new UtmSourceDefault($this->url);
                    break;
            }

            return $source->getResult();
        } else {
            return NULL;
        }
    }

    /**
     * Is there a utm_source parameter?
     *
     * @return bool
     */
    public function hasUtmSource(): bool
    {
        return !empty($this->attributes['utm_source']);
    }

}