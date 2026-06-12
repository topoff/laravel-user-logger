<?php

use Topoff\LaravelUserLogger\Models\Domain;
use Topoff\LaravelUserLogger\Parsers\RefererResult;
use Topoff\LaravelUserLogger\Repositories\LanguageRepository;
use Topoff\LaravelUserLogger\Repositories\RefererRepository;
use Topoff\LaravelUserLogger\Repositories\UriRepository;

it('truncates overlong uris to the column limit', function (): void {
    $uri = new UriRepository()->findOrCreate(['uri' => '/'.str_repeat('x', 400)]);

    expect(mb_strlen($uri->uri))->toBe(255);
});

it('truncates overlong accept language values to the column limits', function (): void {
    $language = new LanguageRepository()->findOrCreate([
        'preference' => str_repeat('a', 50),
        'range' => str_repeat('b', 400),
    ]);

    expect(mb_strlen($language->preference))->toBe(30)
        ->and(mb_strlen($language->range))->toBe(255);
});

it('truncates overlong utm values before storing the referer', function (): void {
    $domain = Domain::query()->create(['name' => 'example.test', 'local' => false]);

    $result = new RefererResult;
    $result->url = 'https://example.test/?utm_source='.str_repeat('s', 100);
    $result->source = str_repeat('s', 100);
    $result->medium = str_repeat('m', 100);
    $result->keywords = str_repeat('k', 300);
    $result->campaign = str_repeat('c', 100);
    $result->matchtype = str_repeat('t', 100);

    $referer = new RefererRepository()->findOrCreate($domain, $result);

    expect(mb_strlen($referer->source))->toBe(30)
        ->and(mb_strlen($referer->medium))->toBe(20)
        ->and(mb_strlen($referer->keywords))->toBe(255)
        ->and(mb_strlen($referer->campaign))->toBe(70)
        ->and(mb_strlen($referer->matchtype))->toBe(6);
});

it('deduplicates referers whose values only differ after the column limit', function (): void {
    $domain = Domain::query()->create(['name' => 'dedupe.test', 'local' => false]);

    $first = new RefererResult;
    $first->url = 'https://dedupe.test/a';
    $first->source = str_repeat('s', 80).'AAA';

    $second = new RefererResult;
    $second->url = 'https://dedupe.test/a';
    $second->source = str_repeat('s', 80).'BBB';

    $repository = new RefererRepository;

    expect($repository->findOrCreate($domain, $first)->id)
        ->toBe($repository->findOrCreate($domain, $second)->id);
});
