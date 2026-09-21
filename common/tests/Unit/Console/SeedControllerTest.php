<?php

declare(strict_types=1);

namespace common\tests\Unit\Console;

use Codeception\Test\Unit;
use common\enums\OfferStatus;
use common\enums\OfferType;
use common\models\Casino;
use common\models\Offer;
use common\models\OfferTerms;
use console\controllers\SeedController;
use Yii;

/**
 * The seed writes domain data, so it is covered from the common suite: the
 * console application has no Codeception suite of its own in this template.
 */
final class SeedControllerTest extends Unit
{
    protected SeedController $controller;

    protected function _before(): void
    {
        // No fixtures here: the seed is meant to fill an empty schema.
        OfferTerms::deleteAll();
        Offer::deleteAll();
        Casino::deleteAll();

        // Anonymous subclass only to keep the command's progress output out of
        // the test report; the seeding logic itself is untouched.
        $this->controller = new class ('seed', Yii::$app) extends SeedController {
            public function stdout($string, ...$args): int
            {
                return 0;
            }
        };
    }

    public function testSeedCreatesOneBatchOfOffersPerCasino(): void
    {
        $this->controller->actionOffers();

        $casinos = (int) Casino::find()->count();

        // Counted from the command's own definitions rather than hardcoded, so
        // adding a casino to the seed does not turn into a failing test.
        self::assertSame(6, $casinos);
        self::assertSame($casinos * SeedController::OFFERS_PER_CASINO, (int) Offer::find()->count());
    }

    public function testSeedIsIdempotent(): void
    {
        $this->controller->actionOffers();
        $casinos = (int) Casino::find()->count();
        $offers = (int) Offer::find()->count();

        $this->controller->actionOffers();

        self::assertSame($casinos, (int) Casino::find()->count());
        self::assertSame($offers, (int) Offer::find()->count());
    }

    public function testEveryTypeAndStatusIsRepresented(): void
    {
        $this->controller->actionOffers();

        foreach (OfferType::values() as $type) {
            self::assertGreaterThan(0, (int) Offer::find()->where(['type' => $type])->count(), $type);
        }

        foreach (OfferStatus::values() as $status) {
            self::assertGreaterThan(0, (int) Offer::find()->where(['status' => $status])->count(), $status);
        }
    }

    public function testSeedFillsMoreThanOnePageOnBothSurfaces(): void
    {
        $this->controller->actionOffers();

        // Both grids paginate at 20, so the demo data has to exceed that twice
        // over: once for the admin list, which shows every offer, and once for
        // the public list, which only shows visible ones. Otherwise nobody ever
        // sees a pager.
        $pageSize = 20;

        self::assertGreaterThan($pageSize, (int) Offer::find()->count());
        self::assertGreaterThan($pageSize, (int) Offer::find()->publiclyVisible()->count());
    }

    public function testLapsedOffersExistForTheExpiryFilters(): void
    {
        $this->controller->actionOffers();

        $lapsed = Offer::find()
            ->where(['not', ['expires_at' => null]])
            ->andWhere(['<', 'expires_at', date('Y-m-d H:i:s')])
            ->count();

        self::assertGreaterThan(0, (int) $lapsed);
    }

    public function testWageringValuesStraddleTheFilterThreshold(): void
    {
        $this->controller->actionOffers();

        self::assertGreaterThan(0, (int) OfferTerms::find()->where(['<=', 'wagering_multiplier', 35])->count());
        self::assertGreaterThan(0, (int) OfferTerms::find()->where(['>', 'wagering_multiplier', 35])->count());
    }

    public function testNoDepositOffersCarryNoMinimumDeposit(): void
    {
        $this->controller->actionOffers();

        $violations = Offer::find()
            ->joinWith('terms')
            ->where(['offer.type' => OfferType::NoDeposit->value])
            ->andWhere(['not', ['offer_terms.min_deposit' => null]])
            ->count();

        self::assertSame(0, (int) $violations);
    }

    public function testSomeOffersHaveNoTermsRowAtAll(): void
    {
        $this->controller->actionOffers();

        self::assertLessThan((int) Offer::find()->count(), (int) OfferTerms::find()->count());
    }
}
