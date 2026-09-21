<?php

declare(strict_types=1);

namespace backend\tests\Unit\Models;

use backend\models\OfferSearch;
use Codeception\Test\Unit;
use common\enums\OfferStatus;
use common\enums\OfferType;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use common\models\Offer;
use Yii;
use yii\log\Logger;

final class OfferSearchTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'casino' => ['class' => CasinoFixture::class],
            'offer' => ['class' => OfferFixture::class],
            'offerTerms' => ['class' => OfferTermsFixture::class],
        ];
    }

    public function testUnfilteredSearchReturnsEverything(): void
    {
        $models = (new OfferSearch())->search([])->getModels();

        self::assertCount(5, $models);
    }

    public function testFilterByType(): void
    {
        $models = (new OfferSearch())
            ->search(['OfferSearch' => ['type' => OfferType::FreeSpins->value]])
            ->getModels();

        self::assertSame([2, 5], $this->ids($models));
    }

    public function testFilterByStatus(): void
    {
        $models = (new OfferSearch())
            ->search(['OfferSearch' => ['status' => OfferStatus::Draft->value]])
            ->getModels();

        self::assertSame([3], $this->ids($models));
    }

    public function testFilterByCasino(): void
    {
        $models = (new OfferSearch())
            ->search(['OfferSearch' => ['casino_id' => 2]])
            ->getModels();

        self::assertSame([4, 5], $this->ids($models));
    }

    public function testFilterByTitleIsAPartialMatch(): void
    {
        $models = (new OfferSearch())
            ->search(['OfferSearch' => ['title' => 'Visible']])
            ->getModels();

        self::assertSame([1, 2], $this->ids($models));
    }

    /**
     * Searching for a title that exists verbatim used to return nothing.
     *
     * `OfferSearch extends Offer`, so it inherited `SluggableBehavior`, which
     * runs on `beforeValidate` — triggered by `search()` — and slugified the
     * search box into `$this->slug`. `andFilterWhere()` then added that as a
     * second condition, and because `ensureUnique` saw the slug already taken
     * it appended `-2`, so the query asked for `slug LIKE '%…-2%'`.
     */
    public function testTitleSearchDoesNotInventASlugCondition(): void
    {
        $search = new OfferSearch();

        $models = $search->search(['OfferSearch' => ['title' => 'Visible Welcome Bonus']])->getModels();

        self::assertEmpty($search->getAttribute('slug'), 'nothing may write to slug during a search');
        self::assertSame([1], $this->ids($models));
    }

    public function testSearchModelHasNoPersistenceBehaviors(): void
    {
        self::assertSame([], (new OfferSearch())->behaviors());
    }

    public function testFilterByMaxWageringUsesTheRelatedTable(): void
    {
        // Fixtures: offer 1 has 35.0, offer 2 has 45.0, the rest have no terms.
        $models = (new OfferSearch())
            ->search(['OfferSearch' => ['maxWagering' => '40']])
            ->getModels();

        self::assertSame([1], $this->ids($models));
    }

    public function testUnknownFilterValueIsDroppedInsteadOfBreakingTheQuery(): void
    {
        $models = (new OfferSearch())
            ->search(['OfferSearch' => ['type' => 'cashback']])
            ->getModels();

        // The `in` rule rejects the value, so the whole filter set is ignored.
        self::assertCount(5, $models);
    }

    public function testSortingByRelatedCasinoName(): void
    {
        $names = static fn (array $models): array => array_map(
            static fn (Offer $offer): string => $offer->casino->name,
            $models,
        );

        $ascending = $names((new OfferSearch())->search(['sort' => 'casino_id'])->getModels());
        $descending = $names((new OfferSearch())->search(['sort' => '-casino_id'])->getModels());

        // Ties are not reversible (several offers share a casino), so assert
        // ordering rather than one list being the reverse of the other.
        $sorted = $ascending;
        sort($sorted);
        self::assertSame($sorted, $ascending);

        $reverseSorted = $descending;
        rsort($reverseSorted);
        self::assertSame($reverseSorted, $descending);

        self::assertSame('Fixture Casino One', $ascending[0]);
        self::assertSame('Fixture Casino Two', $descending[0]);
    }

    public function testDefaultPageSizeIsTwenty(): void
    {
        self::assertSame(20, (new OfferSearch())->search([])->getPagination()->pageSize);
    }

    /**
     * The listing must issue the same number of queries no matter how many rows
     * it renders. Dropping withCasino()/withTerms() turns the relations lazy and
     * this test fails on the post-loop count.
     */
    public function testListingDoesNotScaleQueriesWithRowCount(): void
    {
        Yii::getLogger()->flushInterval = PHP_INT_MAX;

        $before = $this->dbQueryCount();
        $models = (new OfferSearch())->search([])->getModels();
        $queriesForPage = $this->dbQueryCount() - $before;

        self::assertCount(5, $models);

        $touched = [];
        foreach ($models as $offer) {
            self::assertTrue($offer->isRelationPopulated('casino'));
            self::assertTrue($offer->isRelationPopulated('terms'));
            self::assertNotSame('', $offer->casino->name);
            // Reading through the optional relation must not trigger a query
            // either; three of the five fixture offers have no terms row.
            $touched[] = $offer->terms?->wagering_multiplier;
        }

        self::assertCount(5, $touched);
        self::assertSame(['35.0', '45.0'], array_values(array_filter($touched)));

        // Touching every relation added nothing.
        self::assertSame($queriesForPage, $this->dbQueryCount() - $before);
        // COUNT(*) + offers + casinos + terms.
        self::assertLessThanOrEqual(4, $queriesForPage);
    }

    /**
     * @return list<int>
     */
    private function ids(array $models): array
    {
        $ids = array_map(static fn (Offer $offer): int => $offer->id, $models);
        sort($ids);

        return $ids;
    }

    /**
     * Number of SQL statements executed so far.
     *
     * Every query is logged three times under the same category — once as info
     * and twice as profiling boundaries — so only the info records are counted.
     */
    private function dbQueryCount(): int
    {
        return count(array_filter(
            Yii::getLogger()->messages,
            static fn (array $message): bool => $message[1] === Logger::LEVEL_INFO
                && str_starts_with((string) $message[2], 'yii\db\Command::'),
        ));
    }
}
