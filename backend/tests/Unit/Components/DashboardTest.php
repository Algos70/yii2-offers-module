<?php

declare(strict_types=1);

namespace backend\tests\Unit\Components;

use backend\components\Dashboard;
use Codeception\Test\Unit;
use common\enums\OfferStatus;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use common\models\Offer;
use Yii;
use yii\log\Logger;

final class DashboardTest extends Unit
{
    protected Dashboard $dashboard;

    public function _fixtures(): array
    {
        return [
            'casino' => ['class' => CasinoFixture::class],
            'offer' => ['class' => OfferFixture::class],
            'offerTerms' => ['class' => OfferTermsFixture::class],
        ];
    }

    protected function _before(): void
    {
        $this->dashboard = new Dashboard();
    }

    public function testCatalogueCounters(): void
    {
        self::assertSame(2, $this->dashboard->casinoCount());
        self::assertSame(1, $this->dashboard->activeCasinoCount());
        self::assertSame(5, $this->dashboard->offerCount());
    }

    public function testOffersByStatusCountsEveryStatusIncludingEmptyOnes(): void
    {
        $byStatus = $this->dashboard->offersByStatus();

        // Fixtures: two active, one draft, one expired, one active-but-lapsed.
        self::assertSame(
            [OfferStatus::Draft->value => 1, OfferStatus::Active->value => 3, OfferStatus::Expired->value => 1],
            [
                OfferStatus::Draft->value => $byStatus[OfferStatus::Draft->value],
                OfferStatus::Active->value => $byStatus[OfferStatus::Active->value],
                OfferStatus::Expired->value => $byStatus[OfferStatus::Expired->value],
            ],
        );
        self::assertSame(OfferStatus::values(), array_keys($byStatus));
        self::assertSame($this->dashboard->offerCount(), array_sum($byStatus));
    }

    public function testVisibleCountMatchesThePublicPredicate(): void
    {
        self::assertSame(
            (int) Offer::find()->active()->notExpired()->count(),
            $this->dashboard->visibleOfferCount(),
        );
        self::assertSame(2, $this->dashboard->visibleOfferCount());
    }

    public function testLapsedCountFindsPublishedOffersWhoseDatePassed(): void
    {
        // Fixture row 5 is active with an expiry a year ago.
        self::assertSame(1, $this->dashboard->lapsedOfferCount());
    }

    public function testExpiringSoonIgnoresDistantAndMissingDates(): void
    {
        // Fixture expiries are a year out or null, so nothing is due this month.
        self::assertSame(0, $this->dashboard->expiringSoonCount());

        $offer = Offer::findOne(1);
        self::assertNotNull($offer);
        $offer->expires_at = date('Y-m-d H:i:s', strtotime('+3 days'));
        self::assertTrue($offer->save(), print_r($offer->getErrors(), true));

        self::assertSame(1, $this->dashboard->expiringSoonCount());
    }

    public function testRecentOffersAreBoundedAndEagerLoaded(): void
    {
        $recent = $this->dashboard->recentOffers();

        self::assertLessThanOrEqual(Dashboard::RECENT_LIMIT, count($recent));
        foreach ($recent as $offer) {
            self::assertTrue($offer->isRelationPopulated('casino'));
        }
    }

    /**
     * The dashboard is a fixed set of aggregates, so its query count must not
     * depend on how many casinos or offers exist.
     */
    public function testQueryCountIsFixed(): void
    {
        Yii::getLogger()->flushInterval = PHP_INT_MAX;

        $before = $this->dbQueryCount();
        $names = $this->render();
        $queries = $this->dbQueryCount() - $before;

        self::assertNotEmpty($names);

        self::assertLessThanOrEqual(9, $queries);

        // Same work again: the count must repeat exactly, not grow with data.
        $before = $this->dbQueryCount();
        $this->render();
        self::assertSame($queries, $this->dbQueryCount() - $before);
    }

    /**
     * Every query the dashboard view triggers, without the HTML.
     */
    private function render(): array
    {
        $names = [];

        $this->dashboard->casinoCount();
        $this->dashboard->activeCasinoCount();
        $this->dashboard->offerCount();
        $this->dashboard->offersByStatus();
        $this->dashboard->visibleOfferCount();
        $this->dashboard->expiringSoonCount();
        $this->dashboard->lapsedOfferCount();

        foreach ($this->dashboard->recentOffers() as $offer) {
            // Reading through the relation is part of what the view does; it
            // must not add queries.
            $names[] = $offer->casino->name;
        }

        return $names;
    }

    private function dbQueryCount(): int
    {
        return count(array_filter(
            Yii::getLogger()->messages,
            static fn (array $message): bool => $message[1] === Logger::LEVEL_INFO
                && str_starts_with((string) $message[2], 'yii\db\Command::'),
        ));
    }
}
