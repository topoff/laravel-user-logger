<?php

namespace Topoff\LaravelUserLogger\Tests\Models;

use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class ExperimentMeasurementTest extends TestCase
{
    public function test_conversion_rate_is_zero_when_exposure_count_is_zero(): void
    {
        $measurement = new ExperimentMeasurement([
            'exposure_count' => 0,
            'conversion_count' => 5,
        ]);

        $this->assertSame(0.0, $measurement->conversion_rate);
    }

    public function test_conversion_rate_is_calculated_and_rounded(): void
    {
        $measurement = new ExperimentMeasurement([
            'exposure_count' => 3,
            'conversion_count' => 1,
        ]);

        $this->assertSame(33.33, $measurement->conversion_rate);
    }

    public function test_visible_scope_hides_configured_features_but_keeps_rows(): void
    {
        ExperimentMeasurement::query()->create(['session_id' => '11111111-1111-1111-1111-111111111111', 'feature' => 'old-test', 'variant' => 'a', 'exposure_count' => 1]);
        ExperimentMeasurement::query()->create(['session_id' => '11111111-1111-1111-1111-111111111111', 'feature' => 'live-test', 'variant' => 'a', 'exposure_count' => 1]);

        config()->set('user-logger.experiments.hidden_features', ['old-test']);

        $this->assertSame(['live-test'], ExperimentMeasurement::visible()->pluck('feature')->all());
        $this->assertSame(2, ExperimentMeasurement::query()->count(), 'recorder queries must still see hidden features');
    }

    public function test_visible_scope_is_a_no_op_without_hidden_features(): void
    {
        ExperimentMeasurement::query()->create(['session_id' => '11111111-1111-1111-1111-111111111111', 'feature' => 'old-test', 'variant' => 'a', 'exposure_count' => 1]);

        config()->set('user-logger.experiments.hidden_features', []);

        $this->assertSame(1, ExperimentMeasurement::visible()->count());
    }
}
