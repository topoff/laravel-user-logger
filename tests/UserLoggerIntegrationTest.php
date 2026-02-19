<?php

namespace Topoff\LaravelUserLogger\Tests;

use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use ReflectionProperty;
use Topoff\LaravelUserLogger\Models\ExperimentMeasurement;
use Topoff\LaravelUserLogger\Models\Log;
use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\UserLogger;

require_once __DIR__.'/TestCase.php';

class UserLoggerIntegrationTest extends TestCase
{
    public function test_boot_creates_session_log_and_experiment_measurements(): void
    {
        config()->set('app.debug', true);
        config()->set('user-logger.debug', false);
        config()->set('user-logger.log_robots', true);
        config()->set('user-logger.experiments.enabled', true);
        config()->set('user-logger.experiments.features', ['headline', 'checkout']);

        $this->bindRequestWithSession(Request::create(
            '/landing',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_HOST' => 'example.test',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
            ],
        ));

        $userLogger = $this->app->make(UserLogger::class);
        $this->forceEnabled($userLogger);
        $userLogger->boot();

        $log = $userLogger->getCurrentLog();
        $session = $userLogger->getCurrentSession();

        $this->assertInstanceOf(Log::class, $log);
        $this->assertInstanceOf(Session::class, $session);
        $this->assertSame($session->id, $log->session_id);
        $this->assertCount(2, $userLogger->getCurrentExperimentMeasurements());
    }

    public function test_set_event_updates_current_log_and_records_conversion(): void
    {
        config()->set('app.debug', true);
        config()->set('user-logger.debug', false);
        config()->set('user-logger.log_robots', true);
        config()->set('user-logger.experiments.enabled', true);
        config()->set('user-logger.experiments.features', ['headline']);
        config()->set('user-logger.experiments.conversion_events', ['conversion']);

        $this->bindRequestWithSession(Request::create(
            '/checkout',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_HOST' => 'example.test',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'HTTP_ACCEPT_LANGUAGE' => 'en-US',
            ],
        ));

        $userLogger = $this->app->make(UserLogger::class);
        $this->forceEnabled($userLogger);
        $userLogger->boot();

        $updatedLog = $userLogger->setEvent('conversion', 'lead', '123');
        $measurement = ExperimentMeasurement::query()->firstOrFail();

        $this->assertInstanceOf(Log::class, $updatedLog);
        $this->assertSame('conversion', $updatedLog->event);
        $this->assertSame('lead', $updatedLog->entity_type);
        $this->assertSame('123', $updatedLog->entity_id);
        $this->assertSame(1, $measurement->conversion_count);
        $this->assertSame('conversion', $measurement->last_conversion_event);
    }

    public function test_set_event_with_session_id_creates_minimal_log_and_conversion(): void
    {
        config()->set('user-logger.experiments.enabled', true);
        config()->set('user-logger.experiments.conversion_events', ['conversion']);

        $this->bindRequestWithSession(Request::create('/event', 'GET'));
        $userLogger = $this->app->make(UserLogger::class);
        $this->forceEnabled($userLogger);

        ExperimentMeasurement::query()->create([
            'session_id' => '00000000-0000-0000-0000-000000000501',
            'feature' => 'headline',
            'exposure_count' => 1,
            'conversion_count' => 0,
        ]);

        $log = $userLogger->setEventWithSessionId('00000000-0000-0000-0000-000000000501', 'conversion', 'lead', 'x1');
        $measurement = ExperimentMeasurement::query()->firstOrFail();

        $this->assertInstanceOf(Log::class, $log);
        $this->assertSame('00000000-0000-0000-0000-000000000501', $log->session_id);
        $this->assertSame('conversion', $log->event);
        $this->assertSame(1, $measurement->conversion_count);
    }

    public function test_set_comment_updates_existing_log(): void
    {
        config()->set('app.debug', true);
        config()->set('user-logger.log_robots', true);

        $this->bindRequestWithSession(Request::create(
            '/comment',
            'GET',
            [],
            [],
            [],
            ['HTTP_HOST' => 'example.test', 'HTTP_USER_AGENT' => 'Mozilla/5.0'],
        ));

        $userLogger = $this->app->make(UserLogger::class);
        $this->forceEnabled($userLogger);
        $userLogger->boot();

        $updated = $userLogger->setComment('integration-test-comment');

        $this->assertInstanceOf(Log::class, $updated);
        $this->assertSame('integration-test-comment', $updated->comment);
    }

    private function bindRequestWithSession(Request $request): void
    {
        $session = new Store('test', new ArraySessionHandler(120));
        $session->start();
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);
    }

    private function forceEnabled(UserLogger $userLogger): void
    {
        $enabled = new ReflectionProperty($userLogger, 'enabled');
        $enabled->setAccessible(true);
        $enabled->setValue($userLogger, true);
    }
}
