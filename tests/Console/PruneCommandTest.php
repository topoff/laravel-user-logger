<?php

namespace Topoff\LaravelUserLogger\Tests\Console;

use Illuminate\Support\Facades\DB;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Models\Log;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class PruneCommandTest extends TestCase
{
    public function test_it_keeps_conversion_logs_and_prunes_the_rest(): void
    {
        config()->set('user-logger.retention.logs_days', 30);
        config()->set('user-logger.retention.preserve_events', ['conversion']);

        $session = '00000000-0000-0000-0000-0000000005a1';

        DB::connection('user-logger')->table('logs')->insert([
            // Old conversion -> must survive.
            ['session_id' => $session, 'event' => 'conversion', 'entity_id' => 'lead-1', 'created_at' => now()->subDays(60)],
            // Old non-conversion -> pruned.
            ['session_id' => $session, 'event' => 'pageview', 'entity_id' => null, 'created_at' => now()->subDays(60)],
            // Old null event -> pruned (null is not preserved).
            ['session_id' => $session, 'event' => null, 'entity_id' => null, 'created_at' => now()->subDays(60)],
            // Recent non-conversion -> survives (younger than retention).
            ['session_id' => $session, 'event' => 'pageview', 'entity_id' => null, 'created_at' => now()->subDays(1)],
        ]);

        $this->artisan('user-logger:prune')->assertSuccessful();

        $this->assertSame(1, Log::query()->where('event', 'conversion')->count());
        $this->assertSame(0, Log::query()->where('event', 'pageview')->where('created_at', '<', now()->subDays(30))->count());
        $this->assertSame(0, Log::query()->whereNull('event')->count());
        $this->assertSame(1, Log::query()->where('event', 'pageview')->where('created_at', '>=', now()->subDays(30))->count());
        $this->assertSame(2, Log::query()->count());
    }

    public function test_it_prunes_nothing_when_retention_is_disabled(): void
    {
        config()->set('user-logger.retention.logs_days', 0);

        $session = '00000000-0000-0000-0000-0000000005b1';

        DB::connection('user-logger')->table('logs')->insert([
            ['session_id' => $session, 'event' => 'pageview', 'entity_id' => null, 'created_at' => now()->subDays(400)],
        ]);

        $this->artisan('user-logger:prune')->assertSuccessful();

        $this->assertSame(1, Log::query()->count());
    }

    private function sessionWithMeasurement(string $id, bool $robot, int $ageDays, bool $withLog = false): void
    {
        DB::connection('user-logger')->table('sessions')->insert(['id' => $id, 'is_robot' => $robot, 'updated_at' => now()->subDays($ageDays)]);
        DB::connection('user-logger')->table('experiment_measurements')->insert(['session_id' => $id, 'feature' => 'umzug', 'variant' => 'B', 'exposure_count' => 1, 'conversion_count' => 0]);
        if ($withLog) {
            DB::connection('user-logger')->table('logs')->insert(['session_id' => $id, 'event' => 'conversion', 'created_at' => now()]);
        }
    }

    public function test_it_deletes_robot_session_measurements_ahead_of_the_session_prune(): void
    {
        config()->set('user-logger.retention.sessions_days', 60);

        $this->sessionWithMeasurement('00000000-0000-0000-0000-0000000006b1', robot: true, ageDays: 61);
        $this->sessionWithMeasurement('00000000-0000-0000-0000-0000000006b2', robot: true, ageDays: 58);
        $this->sessionWithMeasurement('00000000-0000-0000-0000-0000000006b3', robot: true, ageDays: 56);
        $this->sessionWithMeasurement('00000000-0000-0000-0000-0000000006b4', robot: true, ageDays: 70, withLog: true);
        $this->sessionWithMeasurement('00000000-0000-0000-0000-0000000006h1', robot: false, ageDays: 61);

        $this->artisan('user-logger:prune')->assertSuccessful();

        $this->assertSame(
            ['00000000-0000-0000-0000-0000000006b3', '00000000-0000-0000-0000-0000000006b4', '00000000-0000-0000-0000-0000000006h1'],
            ExperimentMeasurement::query()->orderBy('session_id')->pluck('session_id')->all(),
            'robot measurements go 3 days before the session retention; young robots, robots with logs and humans stay',
        );
        $this->assertFalse(Session::query()->whereKey('00000000-0000-0000-0000-0000000006b1')->exists());
        $this->assertTrue(Session::query()->whereKey('00000000-0000-0000-0000-0000000006b2')->exists());
    }

    public function test_pretend_and_disabled_retention_delete_no_measurements(): void
    {
        $this->sessionWithMeasurement('00000000-0000-0000-0000-0000000007b1', robot: true, ageDays: 400);

        $this->artisan('user-logger:prune')->assertSuccessful();
        $this->assertSame(1, ExperimentMeasurement::query()->count());

        config()->set('user-logger.retention.sessions_days', 60);
        $this->artisan('user-logger:prune', ['--pretend' => true])->assertSuccessful();
        $this->assertSame(1, ExperimentMeasurement::query()->count());
    }

    public function test_it_works_through_more_than_one_chunk_of_robot_sessions(): void
    {
        config()->set('user-logger.retention.sessions_days', 60);

        $sessions = [];
        $measurements = [];
        for ($i = 0; $i < 1001; $i++) {
            $id = sprintf('00000000-0000-0000-0000-%012d', 800000 + $i);
            $sessions[] = ['id' => $id, 'is_robot' => true, 'updated_at' => now()->subDays(61)];
            $measurements[] = ['session_id' => $id, 'feature' => 'umzug', 'variant' => 'B', 'exposure_count' => 1, 'conversion_count' => 0];
        }
        foreach (array_chunk($sessions, 200) as $chunk) {
            DB::connection('user-logger')->table('sessions')->insert($chunk);
        }
        foreach (array_chunk($measurements, 200) as $chunk) {
            DB::connection('user-logger')->table('experiment_measurements')->insert($chunk);
        }

        $this->artisan('user-logger:prune')->assertSuccessful();

        $this->assertSame(0, ExperimentMeasurement::query()->count());
        $this->assertSame(0, Session::query()->count());
    }
}
