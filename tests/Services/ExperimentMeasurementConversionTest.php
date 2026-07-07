<?php

use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Services\ExperimentMeasurementService;

function makeConversionSession(string $uuid): Session
{
    return Session::query()->create(['id' => $uuid]);
}

it('updates the exact variant row and keeps first_converted_at on repeat conversions', function (): void {
    config()->set('user-logger.experiments.enabled', true);
    config()->set('user-logger.experiments.features', ['headline']);
    config()->set('user-logger.experiments.conversion_events', ['conversion']);

    $session = makeConversionSession('00000000-0000-0000-0000-000000000701');
    ExperimentMeasurement::query()->create([
        'session_id' => $session->id,
        'feature' => 'headline',
        'variant' => null,
        'exposure_count' => 1,
        'conversion_count' => 0,
    ]);

    $service = new ExperimentMeasurementService;
    $service->recordConversion($session, 'conversion');
    $measurement = ExperimentMeasurement::query()->firstOrFail();
    $firstConvertedAt = $measurement->first_converted_at;

    $this->travel(1)->hours();
    $service->recordConversion($session, 'conversion');
    $measurement->refresh();

    expect($measurement->conversion_count)->toBe(2)
        ->and($measurement->first_converted_at)->toEqual($firstConvertedAt)
        ->and($measurement->last_converted_at)->not->toEqual($firstConvertedAt);
});

it('falls back to only the most recent row when no exact variant row exists', function (): void {
    config()->set('user-logger.experiments.enabled', true);
    config()->set('user-logger.experiments.features', ['headline']);
    config()->set('user-logger.experiments.conversion_events', ['conversion']);

    $session = makeConversionSession('00000000-0000-0000-0000-000000000702');

    $older = ExperimentMeasurement::query()->create([
        'session_id' => $session->id,
        'feature' => 'headline',
        'variant' => 'a',
        'exposure_count' => 1,
        'conversion_count' => 0,
        'updated_at' => now()->subHour(),
    ]);
    $newer = ExperimentMeasurement::query()->create([
        'session_id' => $session->id,
        'feature' => 'headline',
        'variant' => 'b',
        'exposure_count' => 1,
        'conversion_count' => 0,
        'updated_at' => now(),
    ]);

    // No pennant_features table in the test schema, so the resolved variant is
    // null and no exact (null-variant) row exists -> fallback path.
    new ExperimentMeasurementService()->recordConversion($session, 'conversion');

    expect($older->refresh()->conversion_count)->toBe(0)
        ->and($newer->refresh()->conversion_count)->toBe(1);
});

it('attributes conversions to on-read features that are exposed but not listed in config', function (): void {
    config()->set('user-logger.experiments.enabled', true);
    config()->set('user-logger.experiments.features', ['headline']);
    config()->set('user-logger.experiments.conversion_events', ['conversion']);

    $session = makeConversionSession('00000000-0000-0000-0000-000000000703');

    // On-read experiment: the flag is intentionally kept out of the config
    // features list (so it is not eagerly exposed), but the page that rendered
    // it recorded an exposure row via setVariant().
    $onRead = ExperimentMeasurement::query()->create([
        'session_id' => $session->id,
        'feature' => 'landingpage-city-trustsignals',
        'variant' => null,
        'exposure_count' => 1,
        'conversion_count' => 0,
    ]);

    new ExperimentMeasurementService()->recordConversion($session, 'conversion');

    expect($onRead->refresh()->conversion_count)->toBe(1);
});
