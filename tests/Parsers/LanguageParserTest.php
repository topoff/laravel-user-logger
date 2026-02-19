<?php

namespace Topoff\LaravelUserLogger\Tests\Parsers;

use Illuminate\Http\Request;
use Topoff\LaravelUserLogger\Parsers\LanguageParser;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class LanguageParserTest extends TestCase
{
    public function test_returns_null_when_accept_language_header_is_missing(): void
    {
        $parser = new LanguageParser(Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => '',
        ]));

        $this->assertNull($parser->getLanguageAttributes());
    }

    public function test_parses_default_language_and_sorted_range(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'de-CH;q=0.8,en-US;q=0.9,fr;q=0.3',
        ]);

        $parser = new LanguageParser($request);

        $this->assertSame([
            'preference' => 'en-us',
            'range' => 'en-us, de-ch, fr',
        ], $parser->getLanguageAttributes());
    }
}
