<?php

namespace Topoff\LaravelUserLogger\Tests\Parsers;

use Topoff\LaravelUserLogger\Parsers\UrlPathParser;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class UrlPathParserTest extends TestCase
{
    public function test_returns_null_when_url_does_not_match_mail_markers(): void
    {
        config()->set('user-logger.path_is_mail', ['autologin']);
        $parser = new UrlPathParser('https://example.test/no-mail', ['example.test']);

        $this->assertNull($parser->getResult());
    }

    public function test_returns_autologin_email_result_when_path_matches_marker(): void
    {
        config()->set('user-logger.path_is_mail', ['autologin']);
        $parser = new UrlPathParser('https://example.test/autologin/token', ['example.test']);

        $result = $parser->getResult();

        $this->assertNotNull($result);
        $this->assertSame('autologin', $result->source);
        $this->assertSame('email', $result->medium);
        $this->assertSame('example.test', $result->domain);
        $this->assertTrue($result->domain_intern);
    }
}
