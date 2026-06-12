<?php

namespace Topoff\LaravelUserLogger\Tests\Console;

use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class PruneIpsCommandTest extends TestCase
{
    public function test_removes_ips_only_from_sessions_older_than_retention(): void
    {
        config()->set('user-logger.retention.ip_days', 30);

        Session::query()->create([
            'id' => '00000000-0000-0000-0000-000000000301',
            'client_ip' => '203.0.113.1',
            'updated_at' => now()->subDays(45),
        ]);
        Session::query()->create([
            'id' => '00000000-0000-0000-0000-000000000302',
            'client_ip' => '203.0.113.2',
            'updated_at' => now()->subDays(5),
        ]);

        $this->artisan('user-logger:prune-ips')->assertSuccessful();

        $this->assertNull(Session::query()->findOrFail('00000000-0000-0000-0000-000000000301')->client_ip);
        $this->assertSame('203.0.113.2', Session::query()->findOrFail('00000000-0000-0000-0000-000000000302')->client_ip);
        $this->assertNotNull(Session::query()->findOrFail('00000000-0000-0000-0000-000000000301')->updated_at);
    }

    public function test_fails_when_retention_is_not_configured(): void
    {
        config()->set('user-logger.retention.ip_days', 0);

        Session::query()->create([
            'id' => '00000000-0000-0000-0000-000000000303',
            'client_ip' => '203.0.113.3',
            'updated_at' => now()->subDays(400),
        ]);

        $this->artisan('user-logger:prune-ips')->assertFailed();

        $this->assertSame('203.0.113.3', Session::query()->findOrFail('00000000-0000-0000-0000-000000000303')->client_ip);
    }

    public function test_days_option_overrides_config(): void
    {
        config()->set('user-logger.retention.ip_days', 0);

        Session::query()->create([
            'id' => '00000000-0000-0000-0000-000000000304',
            'client_ip' => '203.0.113.4',
            'updated_at' => now()->subDays(10),
        ]);

        $this->artisan('user-logger:prune-ips', ['--days' => 7])->assertSuccessful();

        $this->assertNull(Session::query()->findOrFail('00000000-0000-0000-0000-000000000304')->client_ip);
    }
}
