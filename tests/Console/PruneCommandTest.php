<?php

namespace Topoff\LaravelUserLogger\Tests\Console;

use Illuminate\Support\Facades\DB;
use Topoff\LaravelUserLogger\Models\Log;
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
}
