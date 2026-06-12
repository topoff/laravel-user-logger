<?php

use Topoff\LaravelUserLogger\Support\AttributeLimiter;

it('truncates string values above their limit', function (): void {
    $result = AttributeLimiter::apply(['source' => str_repeat('a', 100)], ['source' => 30]);

    expect($result['source'])->toBe(str_repeat('a', 30));
});

it('keeps values within their limit untouched', function (): void {
    $result = AttributeLimiter::apply(['source' => 'google'], ['source' => 30]);

    expect($result['source'])->toBe('google');
});

it('ignores missing keys and non string values', function (): void {
    $result = AttributeLimiter::apply(['domain_id' => 5, 'flag' => null], ['domain_id' => 2, 'other' => 3]);

    expect($result)->toBe(['domain_id' => 5, 'flag' => null]);
});

it('truncates multibyte strings by characters not bytes', function (): void {
    $result = AttributeLimiter::apply(['source' => str_repeat('ä', 40)], ['source' => 30]);

    expect($result['source'])->toBe(str_repeat('ä', 30));
});
