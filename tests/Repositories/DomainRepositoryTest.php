<?php

namespace Topoff\LaravelUserLogger\Tests\Repositories;

use Topoff\LaravelUserLogger\Repositories\DomainRepository;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class DomainRepositoryTest extends TestCase
{
    public function test_falls_back_to_unknown_when_name_is_empty(): void
    {
        $repository = new DomainRepository;
        $domain = $repository->findOrCreate(['name' => '', 'local' => true]);

        $this->assertSame('unknown', $domain->name);
    }

    public function test_uses_cached_domain_by_name(): void
    {
        $repository = new DomainRepository;

        $first = $repository->findOrCreate(['name' => 'example.test', 'local' => true]);
        $second = $repository->findOrCreate(['name' => 'example.test', 'local' => false]);

        $this->assertSame($first->id, $second->id);
        $this->assertTrue($second->local);
    }
}
