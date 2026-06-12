<?php

namespace Topoff\LaravelUserLogger\Tests\Repositories;

use Illuminate\Foundation\Auth\User;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Repositories\SessionRepository;
use Topoff\LaravelUserLogger\Support\IpHasher;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class SessionRepositoryTest extends TestCase
{
    public function test_hashes_ip_when_log_ip_is_enabled(): void
    {
        config()->set('user-logger.log_ip', true);
        $repository = new SessionRepository;

        $session = $repository->findOrCreate('00000000-0000-0000-0000-000000000101', clientIp: '127.0.0.1');

        $this->assertSame(IpHasher::hash('127.0.0.1'), $session->client_ip);
        $this->assertStringNotContainsString('127.0.0.1', (string) $session->client_ip);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $session->client_ip);
    }

    public function test_stores_plain_ip_when_hashing_is_disabled(): void
    {
        config()->set('user-logger.log_ip', true);
        config()->set('user-logger.hash_ip', false);
        $repository = new SessionRepository;

        $session = $repository->findOrCreate('00000000-0000-0000-0000-000000000106', clientIp: '2001:db8::8a2e:370:7334');

        $this->assertSame('2001:db8::8a2e:370:7334', $session->client_ip);
    }

    public function test_does_not_store_ip_when_log_ip_is_disabled(): void
    {
        config()->set('user-logger.log_ip', false);
        $repository = new SessionRepository;

        $session = $repository->findOrCreate('00000000-0000-0000-0000-000000000102', clientIp: '127.0.0.1');

        $this->assertNull($session->client_ip);
    }

    public function test_update_user_sets_user_id_when_session_has_no_user(): void
    {
        $repository = new SessionRepository;
        $session = Session::query()->create([
            'id' => '00000000-0000-0000-0000-000000000103',
            'user_id' => null,
        ]);

        $user = new class extends User
        {
            public int $id = 42;
        };

        $repository->updateUser($session, $user);
        $session->refresh();

        $this->assertSame(42, $session->user_id);
        $this->assertNotNull($session->updated_at);
    }

    public function test_update_user_invalidates_cached_session(): void
    {
        $repository = new SessionRepository;
        $uuid = '00000000-0000-0000-0000-000000000104';
        Session::query()->create(['id' => $uuid, 'user_id' => null]);

        $this->assertNull($repository->find($uuid)->user_id);

        $user = new class extends User
        {
            public int $id = 7;
        };
        $repository->updateUser(Session::query()->findOrFail($uuid), $user);

        $this->assertSame(7, $repository->find($uuid)->user_id);
    }

    public function test_set_robot_and_suspicious_invalidates_cached_session(): void
    {
        $repository = new SessionRepository;
        $uuid = '00000000-0000-0000-0000-000000000105';
        Session::query()->create(['id' => $uuid, 'is_robot' => false, 'is_suspicious' => false]);

        $this->assertFalse((bool) $repository->find($uuid)->is_robot);

        $repository->setRobotAndSuspicious(Session::query()->findOrFail($uuid));

        $fresh = $repository->find($uuid);
        $this->assertTrue((bool) $fresh->is_robot);
        $this->assertTrue((bool) $fresh->is_suspicious);
    }
}
