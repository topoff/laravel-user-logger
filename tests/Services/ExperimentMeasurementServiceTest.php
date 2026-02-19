<?php

namespace Topoff\LaravelUserLogger\Tests\Services;

use Illuminate\Support\Carbon;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Models\Log;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Services\ExperimentMeasurementService;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class ExperimentMeasurementServiceTest extends TestCase
{
    protected ExperimentMeasurementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExperimentMeasurementService;
        Carbon::setTestNow(Carbon::parse('2026-02-19 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_record_exposure_does_nothing_when_measurement_is_disabled(): void
    {
        config()->set('user-logger.experiments.enabled', false);
        config()->set('user-logger.experiments.features', ['headline']);

        $session = Session::query()->create(['id' => '00000000-0000-0000-0000-000000000001']);
        $log = Log::query()->create(['session_id' => $session->id]);

        $this->service->recordExposure($session, $log);

        $this->assertSame(0, ExperimentMeasurement::query()->count());
    }

    public function test_record_exposure_creates_and_updates_measurements_for_each_feature(): void
    {
        config()->set('user-logger.experiments.enabled', true);
        config()->set('user-logger.experiments.features', ['headline', 'checkout']);

        $session = Session::query()->create(['id' => '00000000-0000-0000-0000-000000000002']);
        $firstLog = Log::query()->create(['session_id' => $session->id]);
        $secondLog = Log::query()->create(['session_id' => $session->id]);

        $this->service->recordExposure($session, $firstLog);
        $this->service->recordExposure($session, $secondLog);

        $measurements = ExperimentMeasurement::query()
            ->where('session_id', $session->id)
            ->orderBy('feature')
            ->get()
            ->keyBy('feature');

        $this->assertCount(2, $measurements);
        $this->assertSame(2, $measurements['headline']->exposure_count);
        $this->assertNull($measurements['headline']->variant);
        $this->assertSame(2, $measurements['checkout']->exposure_count);
        $this->assertNull($measurements['checkout']->variant);
        $this->assertSame($firstLog->id, $measurements['headline']->first_log_id);
        $this->assertSame($secondLog->id, $measurements['headline']->last_log_id);
    }

    public function test_record_conversion_respects_event_and_entity_type_filters(): void
    {
        config()->set('user-logger.experiments.enabled', true);
        config()->set('user-logger.experiments.features', ['headline']);
        config()->set('user-logger.experiments.conversion_events', ['conversion']);
        config()->set('user-logger.experiments.conversion_entity_types', ['lead']);

        $session = Session::query()->create(['id' => '00000000-0000-0000-0000-000000000003']);
        $exposureLog = Log::query()->create(['session_id' => $session->id]);
        $this->service->recordExposure($session, $exposureLog);

        $wrongEventLog = Log::query()->create(['session_id' => $session->id]);
        $this->service->recordConversion($session, 'click', 'lead', '1', $wrongEventLog);
        $this->assertSame(0, ExperimentMeasurement::query()->firstOrFail()->conversion_count);

        $wrongEntityTypeLog = Log::query()->create(['session_id' => $session->id]);
        $this->service->recordConversion($session, 'conversion', 'order', '2', $wrongEntityTypeLog);
        $this->assertSame(0, ExperimentMeasurement::query()->firstOrFail()->conversion_count);

        $correctLog = Log::query()->create(['session_id' => $session->id]);
        $this->service->recordConversion($session, 'conversion', 'lead', '3', $correctLog);

        $measurement = ExperimentMeasurement::query()->firstOrFail();
        $this->assertSame(1, $measurement->conversion_count);
        $this->assertSame('conversion', $measurement->last_conversion_event);
        $this->assertSame('lead', $measurement->last_conversion_entity_type);
        $this->assertSame('3', $measurement->last_conversion_entity_id);
        $this->assertSame($correctLog->id, $measurement->last_log_id);
        $this->assertNotNull($measurement->first_converted_at);
        $this->assertNotNull($measurement->last_converted_at);
    }

    public function test_get_tracked_features_filters_invalid_values_and_preserves_order(): void
    {
        config()->set('user-logger.experiments.features', ['landing', '', null, 'checkout', 123]);

        $features = $this->service->getTrackedFeatures();

        $this->assertSame(['landing', 'checkout'], $features);
    }

    public function test_set_variant_updates_existing_measurement_without_resetting_counts(): void
    {
        config()->set('user-logger.experiments.enabled', true);

        $session = Session::query()->create(['id' => '00000000-0000-0000-0000-000000000004']);
        $log = Log::query()->create(['session_id' => $session->id]);

        $measurement = ExperimentMeasurement::query()->create([
            'session_id' => $session->id,
            'feature' => 'which-landingpage',
            'variant' => 'false',
            'first_log_id' => $log->id,
            'last_log_id' => $log->id,
            'exposure_count' => 7,
            'conversion_count' => 2,
            'first_exposed_at' => now(),
            'last_exposed_at' => now(),
        ]);

        $this->service->setVariant($session, 'which-landingpage', 'cityTemplate2025', $log);

        $measurement->refresh();
        $this->assertSame('cityTemplate2025', $measurement->variant);
        $this->assertSame(7, $measurement->exposure_count);
        $this->assertSame(2, $measurement->conversion_count);
    }

    public function test_set_variant_creates_measurement_if_missing(): void
    {
        config()->set('user-logger.experiments.enabled', true);

        $session = Session::query()->create(['id' => '00000000-0000-0000-0000-000000000005']);
        $log = Log::query()->create(['session_id' => $session->id]);

        $this->service->setVariant($session, 'which-landingpage', 'cityTemplate2025', $log);

        $measurement = ExperimentMeasurement::query()
            ->where('session_id', $session->id)
            ->where('feature', 'which-landingpage')
            ->firstOrFail();

        $this->assertSame('cityTemplate2025', $measurement->variant);
        $this->assertSame(1, $measurement->exposure_count);
        $this->assertSame(0, $measurement->conversion_count);
    }
}
