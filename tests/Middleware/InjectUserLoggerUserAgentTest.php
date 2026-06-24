<?php

namespace Topoff\LaravelUserLogger\Tests\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Topoff\LaravelUserLogger\Middleware\InjectUserLogger;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class InjectUserLoggerUserAgentTest extends TestCase
{
    public function test_skips_tracking_when_user_agent_matches_ignore_list(): void
    {
        $this->forceEnabled();
        config()->set('user-logger.ignore_user_agents', ['deploy-warmup']);

        $result = $this->bootFor('deploy-warmup');

        $this->assertFalse($result['booted']);
        $this->assertSame('ignore_user_agent', $result['skip_reason']);
    }

    public function test_match_is_case_insensitive_and_substring(): void
    {
        $this->forceEnabled();
        config()->set('user-logger.ignore_user_agents', ['deploy-warmup']);

        $result = $this->bootFor('Deploy-Warmup/2.0 (post-deploy opcache)');

        $this->assertFalse($result['booted']);
        $this->assertSame('ignore_user_agent', $result['skip_reason']);
    }

    public function test_does_not_skip_a_normal_user_agent(): void
    {
        $this->forceEnabled();
        config()->set('user-logger.log_robots', true);
        config()->set('user-logger.ignore_user_agents', ['deploy-warmup']);

        $result = $this->bootFor('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');

        $this->assertNotSame('ignore_user_agent', $result['skip_reason']);
        $this->assertTrue($result['booted']);
    }

    public function test_empty_ignore_list_tracks_the_warmup_user_agent(): void
    {
        $this->forceEnabled();
        config()->set('user-logger.log_robots', true);
        config()->set('user-logger.ignore_user_agents', []);

        $result = $this->bootFor('deploy-warmup');

        $this->assertNotSame('ignore_user_agent', $result['skip_reason']);
        $this->assertTrue($result['booted']);
    }

    /**
     * @return array{booted: bool, duration_ms: float|null, skip_reason: string|null}
     */
    private function bootFor(string $userAgent): array
    {
        $request = Request::create('/landing', 'GET', [], [], [], [
            'HTTP_HOST' => 'example.test',
            'HTTP_USER_AGENT' => $userAgent,
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
        ]);

        $session = new Store('test', new ArraySessionHandler(120));
        $session->start();
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);

        // The middleware reads the ignore list in its constructor, so resolve it
        // after the config has been set.
        return $this->app->make(InjectUserLogger::class)->bootUserLogger($request);
    }

    private function forceEnabled(): void
    {
        config()->set('user-logger.enabled', true);
        config()->set('user-logger.enabled_in_testing', true);
    }
}
