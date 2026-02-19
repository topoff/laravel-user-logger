<?php

namespace Topoff\LaravelUserLogger\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Topoff\LaravelUserLogger\UserLoggerServiceProvider;

class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            UserLoggerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('database.connections.user-logger', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->setUpUserLoggerSchema();
    }

    protected function setUpUserLoggerSchema(): void
    {
        Schema::connection('user-logger')->dropAllTables();

        Schema::connection('user-logger')->create('sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('referer_id')->nullable();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('client_ip')->nullable();
            $table->boolean('is_robot')->default(false);
            $table->boolean('is_suspicious')->default(false);
            $table->timestamp('updated_at')->nullable();
        });

        Schema::connection('user-logger')->create('logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('session_id');
            $table->unsignedBigInteger('domain_id')->nullable();
            $table->unsignedBigInteger('uri_id')->nullable();
            $table->string('event')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->text('comment')->nullable();
        });

        Schema::connection('user-logger')->create('domains', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->boolean('local')->default(false);
        });

        Schema::connection('user-logger')->create('uris', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('uri')->nullable();
        });

        Schema::connection('user-logger')->create('agents', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->boolean('is_robot')->nullable();
        });

        Schema::connection('user-logger')->create('devices', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('kind')->nullable();
            $table->string('model')->nullable();
            $table->string('platform')->nullable();
            $table->string('platform_version')->nullable();
            $table->boolean('is_mobile')->nullable();
            $table->boolean('is_robot')->nullable();
        });

        Schema::connection('user-logger')->create('languages', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('preference')->nullable();
            $table->string('range')->nullable();
        });

        Schema::connection('user-logger')->create('referers', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('domain_id');
            $table->string('url')->nullable();
            $table->string('source')->nullable();
            $table->string('medium')->nullable();
            $table->string('keywords')->nullable();
            $table->string('campaign')->nullable();
            $table->string('adgroup')->nullable();
            $table->string('matchtype')->nullable();
            $table->string('device')->nullable();
            $table->string('adposition')->nullable();
            $table->string('network')->nullable();
        });

        Schema::connection('user-logger')->create('experiment_measurements', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('session_id')->index();
            $table->string('feature', 100)->index();
            $table->string('variant', 120)->nullable()->index();
            $table->unsignedBigInteger('first_log_id')->nullable()->index();
            $table->unsignedBigInteger('last_log_id')->nullable()->index();
            $table->unsignedInteger('exposure_count')->default(0);
            $table->unsignedInteger('conversion_count')->default(0);
            $table->timestamp('first_exposed_at')->nullable();
            $table->timestamp('last_exposed_at')->nullable();
            $table->timestamp('first_converted_at')->nullable();
            $table->timestamp('last_converted_at')->nullable();
            $table->string('last_conversion_event', 100)->nullable();
            $table->string('last_conversion_entity_type', 100)->nullable();
            $table->string('last_conversion_entity_id', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['session_id', 'feature']);
        });
    }
}
