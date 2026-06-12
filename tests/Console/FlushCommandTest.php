<?php

namespace Topoff\LaravelUserLogger\Tests\Console;

use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class FlushCommandTest extends TestCase
{
    public function test_truncates_experiment_measurements_with_force(): void
    {
        ExperimentMeasurement::query()->create([
            'session_id' => '00000000-0000-0000-0000-000000000201',
            'feature' => 'headline',
            'exposure_count' => 2,
            'conversion_count' => 1,
        ]);

        $this->assertSame(1, ExperimentMeasurement::query()->count());

        $this->artisan('user-logger:flush', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, ExperimentMeasurement::query()->count());
    }

    public function test_aborts_without_confirmation(): void
    {
        ExperimentMeasurement::query()->create([
            'session_id' => '00000000-0000-0000-0000-000000000202',
            'feature' => 'headline',
            'exposure_count' => 1,
            'conversion_count' => 0,
        ]);

        $this->artisan('user-logger:flush')
            ->expectsConfirmation('This will delete ALL experiment measurements. Continue?', 'no')
            ->assertFailed();

        $this->assertSame(1, ExperimentMeasurement::query()->count());
    }
}
