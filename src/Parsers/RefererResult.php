<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class RefererResult
 *
 * Referer Result, which has to be delivered from Referer or UTM Source
 */
class RefererResult
{
    /**
     * @var string
     */
    public $parser = 'RefererResult';

    /**
     * @var string
     */
    public $url = 'unknown';

    /**
     * @var string
     */
    public $source = '';

    /**
     * @var string
     */
    public $domain = '';

    /**
     * @var string
     */
    public $medium = '';

    /**
     * @var string
     */
    public $keywords = '';

    /**
     * @var string
     */
    public $campaign = '';

    /**
     * @var string
     */
    public $adgroup = '';

    /**
     * @var string
     */
    public $matchtype = '';

    /**
     * @var string
     */
    public $device = '';

    /**
     * @var string
     */
    public $adposition = '';

    /**
     * @var string
     */
    public $network = '';

    /**
     * @var string
     */
    public $gclid = '';

    /**
     * @var bool
     */
    public $domain_intern = false;
}
