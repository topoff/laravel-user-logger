<?php

namespace Topoff\LaravelUserLogger\Tests\Parsers;

use Topoff\LaravelUserLogger\Parsers\RefererParser;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class RefererParserTest extends TestCase
{
    public function test_partner_url_result_has_expected_defaults(): void
    {
        $parser = new RefererParser('https://partner.example/campaign');
        $result = $parser->getResultFromPartnerUrl();

        $this->assertSame('partner', $result->source);
        $this->assertSame('paid', $result->medium);
        $this->assertSame('https://partner.example/campaign', $result->url);
        $this->assertFalse($result->domain_intern);
    }

    public function test_get_result_returns_url_and_domain_even_for_unknown_referer(): void
    {
        $parser = new RefererParser('https://not-a-known-ref.example/path');
        $result = $parser->getResult();

        $this->assertNotNull($result);
        $this->assertSame('https://not-a-known-ref.example/path', $result->url);
        $this->assertSame('not-a-known-ref.example', $result->domain);
    }

    public function test_detects_known_search_engine_referer(): void
    {
        $parser = new RefererParser('https://www.google.com/search?q=laravel+logger');
        $result = $parser->getResult();

        $this->assertNotNull($result);
        $this->assertSame('Google', $result->source);
        $this->assertSame('search', $result->medium);
        $this->assertSame('laravel logger', $result->keywords);
    }

    public function test_detects_internal_referer_via_page_url(): void
    {
        $parser = new RefererParser('https://shop.example/checkout', 'https://shop.example/cart');
        $result = $parser->getResult();

        $this->assertNotNull($result);
        $this->assertTrue($result->domain_intern);
    }

    public function test_handles_invalid_referer_url_without_error(): void
    {
        $parser = new RefererParser('http:///broken');
        $result = $parser->getResult();

        $this->assertNotNull($result);
        $this->assertNull($result->domain);
        $this->assertSame('', $result->source);
    }
}
