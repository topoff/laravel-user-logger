<?php

namespace Topoff\LaravelUserLogger\Tests\Console;

use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class UpdateReferersCommandTest extends TestCase
{
    public function test_generates_snowplow_compatible_database_from_matomo_definitions(): void
    {
        $output = sys_get_temp_dir().'/user-logger-referers-test.json';
        @unlink($output);

        $this->artisan('user-logger:update-referers', ['--output' => $output])->assertSuccessful();

        $data = json_decode((string) file_get_contents($output), true);

        $this->assertSame(['email', 'search', 'social', 'ai'], array_keys($data));
        $this->assertContains('google.ch', $data['search']['Google']['domains']);
        $this->assertContains('q', $data['search']['Google']['parameters']);
        $this->assertContains('chatgpt.com', $data['ai']['ChatGPT']['domains']);
        $this->assertNotEmpty($data['social']);
        $this->assertNotEmpty($data['email']);

        foreach ($data as $sources) {
            foreach ($sources as $source) {
                $this->assertNotEmpty($source['domains']);
            }
        }

        @unlink($output);
    }
}
