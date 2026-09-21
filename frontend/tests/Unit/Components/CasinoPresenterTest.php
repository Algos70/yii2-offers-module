<?php

declare(strict_types=1);

namespace frontend\tests\Unit\Components;

use Codeception\Test\Unit;
use common\fixtures\CasinoFixture;
use common\models\Casino;
use frontend\components\CasinoPresenter;

final class CasinoPresenterTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'casino' => ['class' => CasinoFixture::class],
        ];
    }

    public function testLinksToTheCasinoPage(): void
    {
        self::assertSame(
            ['/casino/view', 'slug' => 'fixture-casino-one'],
            $this->presenter(1)->url(),
        );
    }

    /**
     * `casino.rating` is decimal(2,1), so a tenth has to survive: rounding to
     * the nearest half star would show 4.6 as 4.5, a wrong number on a page
     * about money.
     */
    public function testRatingIsNotRoundedToHalfStars(): void
    {
        $casino = Casino::findOne(1);
        self::assertNotNull($casino);
        $casino->updateAttributes(['rating' => '4.6']);

        $presenter = new CasinoPresenter($casino);

        self::assertTrue($presenter->isRated());
        self::assertSame('4.6', $presenter->ratingNumber());
        self::assertSame('92%', $presenter->ratingFillPercent());
        self::assertSame('Rated 4.6 out of 5', $presenter->ratingTitle());
    }

    public function testFullMarksFillEveryStar(): void
    {
        $casino = Casino::findOne(1);
        self::assertNotNull($casino);
        $casino->updateAttributes(['rating' => '5.0']);

        self::assertSame('100%', (new CasinoPresenter($casino))->ratingFillPercent());
    }

    /**
     * Zero is the column default, so it means "unrated" far more often than it
     * means "terrible".
     */
    public function testUnratedCasinosShowNoStars(): void
    {
        $casino = Casino::findOne(1);
        self::assertNotNull($casino);
        $casino->updateAttributes(['rating' => '0.0']);

        $presenter = new CasinoPresenter($casino);

        self::assertFalse($presenter->isRated());
        self::assertSame('This casino has not been rated yet', $presenter->ratingTitle());
    }

    private function presenter(int $id): CasinoPresenter
    {
        $casino = Casino::findOne($id);
        self::assertNotNull($casino);

        return new CasinoPresenter($casino);
    }
}
