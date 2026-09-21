<?php

declare(strict_types=1);

namespace frontend\tests\Acceptance;

use frontend\tests\Support\AcceptanceTester;

/**
 * /sitemap.xml over real HTTP: the path, the header and the document.
 *
 * A crawler fetches this by path and reads the header, neither of which the
 * functional suite exercises.
 */
final class SitemapCest
{
    public function sitemapResolvesAsAPath(AcceptanceTester $I): void
    {
        $I->amOnPage('/sitemap.xml');

        $I->seeResponseCodeIs(200);
        // The Content-Type header is asserted in SitemapControllerTest; neither
        // browser module here exposes response headers.
        $I->assertStringContainsString('<urlset', $I->grabPageSource());
    }

    public function sitemapListsAbsoluteUrlsThatResolve(AcceptanceTester $I): void
    {
        $I->amOnPage('/sitemap.xml');

        $xml = simplexml_load_string($I->grabPageSource());
        $I->assertNotFalse($xml, 'the sitemap must parse as XML');
        $I->assertGreaterThan(0, $xml->count());

        $locations = [];
        foreach ($xml->url as $url) {
            $locations[] = (string) $url->loc;
        }

        foreach ($locations as $loc) {
            $I->assertStringStartsWith('http', $loc, 'lastmod entries must be absolute URLs');
        }

        // Follow one offer URL end to end: a sitemap that lists 404s is worse
        // than no sitemap.
        $offerUrls = array_values(array_filter(
            $locations,
            static fn (string $loc): bool => str_contains($loc, '/offer/'),
        ));
        $I->assertNotEmpty($offerUrls);

        $I->amOnUrl($offerUrls[0]);
        $I->seeResponseCodeIs(200);
    }
}
