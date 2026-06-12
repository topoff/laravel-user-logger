<?php

namespace Topoff\LaravelUserLogger\Tests\Repositories;

use Topoff\LaravelUserLogger\Models\Agent;
use Topoff\LaravelUserLogger\Repositories\AgentRepository;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class AgentRepositoryTest extends TestCase
{
    public function test_deduplicates_same_attributes_via_lookup_hash(): void
    {
        $repository = new AgentRepository;
        $attributes = ['name' => 'Mozilla/5.0 Test', 'browser' => 'Chrome', 'browser_version' => '120.0'];

        $first = $repository->findOrCreate($attributes);
        $second = $repository->findOrCreate($attributes);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Agent::query()->count());
        $this->assertNotNull($first->lookup_hash);
    }

    public function test_different_attributes_create_different_agents(): void
    {
        $repository = new AgentRepository;

        $first = $repository->findOrCreate(['name' => 'UA-1', 'browser' => 'Chrome', 'browser_version' => '1']);
        $second = $repository->findOrCreate(['name' => 'UA-2', 'browser' => 'Firefox', 'browser_version' => '2']);

        $this->assertNotSame($first->id, $second->id);
    }

    public function test_truncates_overlong_browser_values(): void
    {
        $repository = new AgentRepository;

        $agent = $repository->findOrCreate([
            'name' => 'UA-long',
            'browser' => str_repeat('b', 300),
            'browser_version' => str_repeat('v', 300),
        ]);

        $this->assertSame(255, mb_strlen($agent->browser));
        $this->assertSame(255, mb_strlen($agent->browser_version));
    }

    public function test_not_detected_agent_reuses_unknown_row(): void
    {
        $repository = new AgentRepository;

        $first = $repository->findOrCreateNotDetected();
        $second = $repository->findOrCreateNotDetected();

        $this->assertSame($first->id, $second->id);
        $this->assertSame('unknown', $first->name);
    }
}
