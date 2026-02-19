<?php

namespace Topoff\LaravelUserLogger\Tests\Repositories;

use Illuminate\Foundation\Auth\User;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Repositories\SessionRepository;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class SessionRepositoryTest extends TestCase
{
    public function test_hashes_ip_when_log_ip_is_enabled(): void
    {
        config()->set('user-logger.log_ip', true);
        $repository = new SessionRepository;

        $session = $repository->findOrCreate('00000000-0000-0000-0000-000000000101', clientIp: '127.0.0.1');
        $hashed = md5('127.0.0.1');
        $expected = substr($hashed, 0, 10).substr($hashed, 20, 12).substr($hashed, 11, 10);

        $this->assertSame($expected, $session->client_ip);
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
}
