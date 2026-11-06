<?php

namespace Topoff\LaravelUserLogger\Tests\Models;

use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Models\Session;
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

    public function test_visible_scope_combines_hidden_features_and_robot_filter_on_the_user_logger_connection(): void
    {
        config()->set('user-logger.experiments.hidden_features', ['old-test']);

        Session::query()->create(['id' => '00000000-0000-0000-0000-00000000v0b1', 'is_robot' => true]);
        Session::query()->create(['id' => '00000000-0000-0000-0000-00000000v0h1', 'is_robot' => false]);
        ExperimentMeasurement::query()->create(['session_id' => '00000000-0000-0000-0000-00000000v0b1', 'feature' => 'live-test', 'variant' => 'a']);
        ExperimentMeasurement::query()->create(['session_id' => '00000000-0000-0000-0000-00000000v0h1', 'feature' => 'live-test', 'variant' => 'a']);
        ExperimentMeasurement::query()->create(['session_id' => '00000000-0000-0000-0000-00000000v0h1', 'feature' => 'old-test', 'variant' => 'a']);

        $visible = ExperimentMeasurement::visible();

        $this->assertSame('user-logger', $visible->getConnection()->getName());
        $this->assertSame(['00000000-0000-0000-0000-00000000v0h1'], $visible->pluck('session_id')->all());
        $this->assertSame(3, ExperimentMeasurement::query()->count());
    }
}
