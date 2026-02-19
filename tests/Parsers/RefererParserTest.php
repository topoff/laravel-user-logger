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
}
