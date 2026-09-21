<?php

declare(strict_types=1);

namespace frontend\tests\Acceptance;

use frontend\tests\Support\AcceptanceTester;

/**
 * The casino page over real HTTP, which is what the sitemap advertises.
 */
final class CasinoCest
{
    public function casinoListingResolvesAsAPath(AcceptanceTester $I): void
    {
        $I->amOnPage('/casinos');

        $I->seeResponseCodeIs(200);
        $I->see('Casinos', 'h1');

        $I->click('Casinos');           // the nav entry points back here
        $I->seeCurrentUrlEquals('/casinos');
    }

    public function casinoPageResolvesAsAPath(AcceptanceTester $I): void
    {
        $I->amOnPage('/casino/neon-palace');

        $I->seeResponseCodeIs(200);
        $I->see('Neon Palace', 'h1');
    }

    public function everyCasinoUrlInTheSitemapResolves(AcceptanceTester $I): void
    {
        $I->amOnPage('/sitemap.xml');

        $xml = simplexml_load_string($I->grabPageSource());
        $I->assertNotFalse($xml, 'the sitemap must parse as XML');

        $casinoUrls = [];
        foreach ($xml->url as $url) {
            $loc = (string) $url->loc;
            if (str_contains($loc, '/casino/')) {
                $casinoUrls[] = $loc;
            }
        }

        $I->assertNotEmpty($casinoUrls, 'the sitemap must list casinos');

        // A sitemap that advertises 404s is worse than no sitemap.
        foreach ($casinoUrls as $loc) {
            $I->amOnUrl($loc);
            $I->seeResponseCodeIs(200);
        }
    }
}
