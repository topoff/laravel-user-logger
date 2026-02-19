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
}
