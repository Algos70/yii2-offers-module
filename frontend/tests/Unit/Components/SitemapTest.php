<?php

declare(strict_types=1);

namespace frontend\tests\Unit\Components;

use Codeception\Test\Unit;
use common\enums\OfferStatus;
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

    public function testListsTheIndexPagesTheCasinoAndEveryVisibleOffer(): void
    {
        $locations = $this->locations();

        self::assertCount(6, $locations);
        self::assertStringEndsWith('/', $locations[0]);                     // home
        self::assertStringContainsString('/offers', $locations[1]);         // offer listing
        self::assertStringContainsString('/casinos', $locations[2]);        // casino listing
        self::assertStringContainsString('/casino/fixture-casino-one', $locations[3]);
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

    /**
     * An active casino with nothing published still has a page — it renders
     * "No live offers right now" — so leaving it out would hide a live URL
     * from crawlers. Only inactive casinos are omitted, because only they 404.
     */
    public function testActiveCasinosWithoutVisibleOffersAreStillListed(): void
    {
        // Hide both of casino 1's offers, leaving it with nothing to show.
        Offer::updateAll(['status' => OfferStatus::Draft->value], ['casino_id' => 1]);

        $all = implode(' ', $this->locations());

        self::assertStringContainsString(
            '/casino/fixture-casino-one',
            $all,
            'an active casino must be listed even with no live offers',
        );
        // ... while its now-hidden offers are gone.
        self::assertStringNotContainsString('visible-welcome-bonus', $all);
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
