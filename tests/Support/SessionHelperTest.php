<?php

namespace Topoff\LaravelUserLogger\Tests\Support;

use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Topoff\LaravelUserLogger\Support\SessionHelper;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class SessionHelperTest extends TestCase
{
    public function test_creates_and_reuses_session_uuid(): void
    {
        config()->set('user-logger.session_name', 'user-logger-session');

        $request = Request::create('/', 'GET');
        $session = new Store('test', new ArraySessionHandler(10));
        $session->start();
        $request->setLaravelSession($session);

        $helper = new SessionHelper($request);
        $uuid1 = $helper->getSessionUuid();
        $uuid2 = $helper->getSessionUuid();

        $this->assertTrue($helper->isExistingSession());
        $this->assertSame($uuid1, $uuid2);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $uuid1);
    }
}
