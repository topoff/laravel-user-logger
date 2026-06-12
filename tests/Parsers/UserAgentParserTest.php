<?php

namespace Topoff\LaravelUserLogger\Tests\Parsers;

use Illuminate\Http\Request;
use Topoff\LaravelUserLogger\Parsers\UserAgentParser;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class UserAgentParserTest extends TestCase
{
    private function makeRequest(?string $userAgent): Request
    {
        $request = Request::create('/', 'GET', [], [], [], $userAgent === null ? [] : ['HTTP_USER_AGENT' => $userAgent]);

        if ($userAgent === null) {
            // Symfony injects a default "Symfony" user agent otherwise
            $request->headers->remove('User-Agent');
        }

        return $request;
    }

    public function test_parses_a_desktop_browser_user_agent(): void
    {
        $parser = new UserAgentParser($this->makeRequest(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ));

        $this->assertTrue($parser->hasResult());

        $agent = $parser->getAgentAttributes();
        $this->assertSame('Chrome', $agent['browser']);

        $device = $parser->getDeviceAttributes();
        $this->assertSame('desktop', $device['kind']);
        $this->assertSame('mac', $device['platform']);
        $this->assertFalse($device['is_mobile']);
        $this->assertFalse($device['is_robot']);
    }

    public function test_detects_bot_user_agent(): void
    {
        $parser = new UserAgentParser($this->makeRequest(
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ));

        $this->assertTrue($parser->hasResult());
        $this->assertTrue($parser->getDeviceAttributes()['is_robot']);
    }

    public function test_has_no_result_without_user_agent_header(): void
    {
        $parser = new UserAgentParser($this->makeRequest(null));

        $this->assertFalse($parser->hasResult());
        $this->assertNull($parser->getAgentAttributes());
        $this->assertNull($parser->getDeviceAttributes());
    }

    public function test_pre_classified_bot_skips_bot_detection_but_keeps_result(): void
    {
        $parser = new UserAgentParser($this->makeRequest(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ), true);

        $this->assertTrue($parser->hasResult());
        $this->assertFalse($parser->getDeviceAttributes()['is_robot']);
    }
}
