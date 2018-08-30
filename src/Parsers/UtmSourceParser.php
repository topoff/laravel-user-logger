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
            switch($this->request->get('utm_source')) {
                case 'google':
                    $source = new UtmSourceGoogle($this->request);
                    break;

                case 'bing':
                    $source = new UtmSourceBing($this->request);
                    break;

                default:
                    $source = new UtmSourceDefault($this->request);
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
        return !empty($this->request->get('utm_source'));
    }

}