<?php

use Topoff\LaravelUserLogger\Support\IpHasher;

it('produces a deterministic 32 char hash', function (): void {
    config()->set('app.key', 'base64:abcdefghijklmnopqrstuvwxyz123456');

    $first = IpHasher::hash('203.0.113.42');
    $second = IpHasher::hash('203.0.113.42');

    expect($first)->toBe($second)
        ->and(strlen($first))->toBe(32)
        ->and($first)->toMatch('/^[0-9a-f]{32}$/');
});

it('produces different hashes for different ips', function (): void {
    config()->set('app.key', 'base64:abcdefghijklmnopqrstuvwxyz123456');

    expect(IpHasher::hash('203.0.113.42'))->not->toBe(IpHasher::hash('203.0.113.43'));
});

it('uses the configured ip salt over the app key', function (): void {
    config()->set('app.key', 'base64:abcdefghijklmnopqrstuvwxyz123456');

    $withAppKey = IpHasher::hash('203.0.113.42');

    config()->set('user-logger.ip_salt', 'my-dedicated-salt');
    $withSalt = IpHasher::hash('203.0.113.42');

    expect($withSalt)->not->toBe($withAppKey);
});
