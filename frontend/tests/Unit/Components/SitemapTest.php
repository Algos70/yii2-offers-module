<?php

declare(strict_types=1);

namespace frontend\tests\Unit\Components;

use Codeception\Test\Unit;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use common\models\Offer;
use common\models\OfferTerms;
use frontend\components\Sitemap;

/**
 * Fixture layout: casino 1 is active, casino 2 is not. Offers 1 and 2 are
 * visible; offer 3 is a draft, 4 is expired, 5 is active with a lapsed date —
 * and 4 and 5 belong to the inactive casino anyway.
 */
final class SitemapTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'casino' => ['class' => CasinoFixture::class],
            'offer' => ['class' => OfferFixture::class],
            'offerTerms' => ['class' => OfferTermsFixture::class],
        ];
    }

    public function testListsTheStaticPagesTheCasinoAndEveryVisibleOffer(): void
    {
        $locations = $this->locations();

        self::assertCount(5, $locations);
        self::assertStringEndsWith('/', $locations[0]);                     // home
        self::assertStringContainsString('/offers', $locations[1]);         // listing
        self::assertStringContainsString('/casino/fixture-casino-one', $locations[2]);
        self::assertStringContainsString('visible-welcome-bonus', implode(' ', $locations));
        self::assertStringContainsString('visible-free-spins', implode(' ', $locations));
    }

    public function testDraftExpiredAndLapsedOffersAreAbsent(): void
    {
        $all = implode(' ', $this->locations());

        self::assertStringNotContainsString('draft-no-deposit', $all);
        self::assertStringNotContainsString('expired-welcome-bonus', $all);
        self::assertStringNotContainsString('lapsed-free-spins', $all);
    }

    public function testInactiveCasinosAndTheirOffersAreAbsent(): void
    {
        $all = implode(' ', $this->locations());

        self::assertStringNotContainsString('fixture-casino-two', $all);
    }

    public function testEveryEntryCarriesAW3cLastmod(): void
    {
        foreach ((new Sitemap())->urls() as $url) {
            self::assertArrayHasKey('lastmod', $url);
            self::assertNotFalse(
                \DateTimeImmutable::createFromFormat(\DateTimeInterface::W3C, $url['lastmod']),
                "not a W3C datetime: {$url['lastmod']}",
            );
        }
    }

    /**
     * An offer page renders its bonus terms, so editing the terms has to move
     * the offer's `lastmod` — and with it the casino page that lists it.
     */
    public function testTermsUpdatesDateTheOfferAndItsCasino(): void
    {
        $touched = time();

        $terms = OfferTerms::findOne(1);
        self::assertNotNull($terms);
        $terms->updateAttributes(['updated_at' => $touched]);

        $urls = [];
        foreach ((new Sitemap())->urls() as $url) {
            $urls[$url['loc']] = $url['lastmod'];
        }

        $offerUrl = $this->urlContaining($urls, 'visible-welcome-bonus');
        $casinoUrl = $this->urlContaining($urls, '/casino/fixture-casino-one');

        self::assertSame(date('c', $touched), $urls[$offerUrl]);
        self::assertSame(date('c', $touched), $urls[$casinoUrl]);
    }

    public function testAnOfferWithoutTermsStillGetsItsOwnDate(): void
    {
        $offer = Offer::findOne(2);
        self::assertNotNull($offer);
        // Offer 2 has terms; strip them so only offer.updated_at remains.
        OfferTerms::deleteAll(['offer_id' => 2]);

        $urls = [];
        foreach ((new Sitemap())->urls() as $url) {
            $urls[$url['loc']] = $url['lastmod'];
        }

        $offerUrl = $this->urlContaining($urls, 'visible-free-spins');
        self::assertSame(date('c', (int) $offer->updated_at), $urls[$offerUrl]);
    }

    /**
     * @return list<string>
     */
    private function locations(): array
    {
        return array_column((new Sitemap())->urls(), 'loc');
    }

    /**
     * @param array<string, string> $urls
     */
    private function urlContaining(array $urls, string $needle): string
    {
        foreach (array_keys($urls) as $loc) {
            if (str_contains($loc, $needle)) {
                return $loc;
            }
        }

        self::fail("no sitemap entry contains \"$needle\"");
    }
}
