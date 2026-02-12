<?php

namespace Topoff\LaravelUserLogger\Parsers;

/**
 * Class RefererResult
 *
 * Referer Result, which has to be delivered from Referer or UTM Source
 */
class RefererResult
{
    public string $parser = 'RefererResult';

    public ?string $url = 'unknown';

    public string $source = '';

    public ?string $domain = '';

    public string $medium = '';

    public string $keywords = '';

    public string $campaign = '';

    public string $adgroup = '';

    public string $matchtype = '';

    public string $device = '';

    public string $adposition = '';

    public string $network = '';

    public string $gclid = '';

    public bool $domain_intern = false;
}
