<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Illuminate\Http\Request;

/**
 * Class UtmSourceParser
 *
 * @package Topoff\LaravelUserLogger\Parsers
 */
class UtmSourceParser
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * UtmSourceParser constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
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
            if ($this->request->get('utm_source') === 'google') {
                $source = new UtmSourceGoogle($this->request);
            } else {
                $source = new UtmSourceDefault($this->request);
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
        return !empty($this->request->get('utm_source'));
    }

}