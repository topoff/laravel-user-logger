<?php

namespace Topoff\LaravelUserLogger\Tests\Parsers;

use Topoff\LaravelUserLogger\Parsers\UtmSourceParser;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class UtmSourceParserTest extends TestCase
{
    public function test_returns_null_when_utm_source_is_missing(): void
    {
        $parser = new UtmSourceParser('https://example.test/?utm_campaign=abc');

        $this->assertFalse($parser->hasUtmSource());
        $this->assertNull($parser->getResult());
    }

    public function test_parses_google_utm_values_and_translations(): void
    {
        $url = 'https://example.test/?utm_source=google&campaignid=c1&adgroupid=g1&matchtype=e&device=m&keyword=k1&adposition=p1&network=g&gclid=gc1';
        $result = (new UtmSourceParser($url))->getResult();

        $this->assertNotNull($result);
        $this->assertSame('google', $result->source);
        $this->assertSame('paid', $result->medium);
        $this->assertSame('c1', $result->campaign);
        $this->assertSame('g1', $result->adgroup);
        $this->assertSame('exact', $result->matchtype);
        $this->assertSame('mobile', $result->device);
        $this->assertSame('k1', $result->keywords);
        $this->assertSame('search', $result->network);
        $this->assertSame('gc1', $result->gclid);
    }

    public function test_parses_bing_utm_values_with_bing_specific_fields(): void
    {
        $url = 'https://example.test/?utm_source=bing&utm_campaign=bc1&utm_content=ag1&utm_term=kw1&matchtype=p&device=t&network=o';
        $result = (new UtmSourceParser($url))->getResult();

        $this->assertNotNull($result);
        $this->assertSame('bing', $result->source);
        $this->assertSame('bc1', $result->campaign);
        $this->assertSame('ag1', $result->adgroup);
        $this->assertSame('kw1', $result->keywords);
        $this->assertSame('phrase', $result->matchtype);
        $this->assertSame('tablet', $result->device);
        $this->assertSame('search', $result->network);
    }
}
