<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use frontend\tests\Support\FunctionalTester;

final class SitemapCest
{
    public function _fixtures(): array
    {
        return [
            'casino' => ['class' => CasinoFixture::class],
            'offer' => ['class' => OfferFixture::class],
            'offerTerms' => ['class' => OfferTermsFixture::class],
        ];
    }

    public function sitemapIsServedAsXml(FunctionalTester $I): void
    {
        $I->amOnRoute('/sitemap/index');

        $I->seeResponseCodeIs(200);
        // The Content-Type header is asserted in the acceptance suite; the
        // functional tester has no access to response headers.

        $source = $I->grabPageSource();
        $I->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', trim($source));
        $I->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $source);
    }

    public function sitemapIsWellFormedAndDatesEveryEntry(FunctionalTester $I): void
    {
        $I->amOnRoute('/sitemap/index');

        $xml = simplexml_load_string($I->grabPageSource());
        $I->assertNotFalse($xml, 'the sitemap must parse as XML');

        $I->assertGreaterThan(0, $xml->count());

        foreach ($xml->url as $url) {
            $I->assertNotEmpty((string) $url->loc);
            $I->assertNotEmpty((string) $url->lastmod);
        }
    }

    public function sitemapOmitsEverythingThePublicSiteHides(FunctionalTester $I): void
    {
        $I->amOnRoute('/sitemap/index');
        $source = $I->grabPageSource();

        $I->assertStringContainsString('visible-welcome-bonus', $source);
        $I->assertStringContainsString('visible-free-spins', $source);

        // Draft, expired, lapsed, and anything under an inactive casino.
        $I->assertStringNotContainsString('draft-no-deposit', $source);
        $I->assertStringNotContainsString('expired-welcome-bonus', $source);
        $I->assertStringNotContainsString('lapsed-free-spins', $source);
        $I->assertStringNotContainsString('fixture-casino-two', $source);
    }
}
