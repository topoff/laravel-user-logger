<?php

namespace Topoff\LaravelUserLogger\Tests\Console;

use Topoff\LaravelUserLogger\Console\Commands\Flush;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class FlushCommandTest extends TestCase
{
    public function test_truncates_experiment_measurements(): void
    {
        ExperimentMeasurement::query()->create([
            'session_id' => '00000000-0000-0000-0000-000000000201',
            'feature' => 'headline',
            'exposure_count' => 2,
            'conversion_count' => 1,
        ]);

        $this->assertSame(1, ExperimentMeasurement::query()->count());

        (new Flush)->handle();

        $this->assertSame(0, ExperimentMeasurement::query()->count());
    }
}
