<?php

declare(strict_types=1);

namespace common\tests\Unit\Models;

use Codeception\Test\Unit;
use common\enums\OfferStatus;
use common\enums\OfferType;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\models\Casino;
use common\models\Offer;

final class OfferTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'casino' => [
                'class' => CasinoFixture::class,
                'dataFile' => codecept_data_dir() . 'casino.php',
            ],
            'offer' => [
                'class' => OfferFixture::class,
                'dataFile' => codecept_data_dir() . 'offer.php',
            ],
        ];
    }

    public function testTypeMustBeAKnownValue(): void
    {
        $offer = $this->makeOffer(['type' => 'cashback']);

        self::assertFalse($offer->validate());
        self::assertArrayHasKey('type', $offer->getErrors());
    }

    public function testStatusMustBeAKnownValue(): void
    {
        $offer = $this->makeOffer(['status' => 'archived']);

        self::assertFalse($offer->validate());
        self::assertArrayHasKey('status', $offer->getErrors());
    }

    public function testCasinoMustExist(): void
    {
        $offer = $this->makeOffer(['casino_id' => 9999]);

        self::assertFalse($offer->validate());
        self::assertArrayHasKey('casino_id', $offer->getErrors());
    }

    public function testSlugIsDerivedFromTheTitleWhenLeftEmpty(): void
    {
        $offer = $this->makeOffer(['title' => 'Big Summer Bonus', 'slug' => '']);

        self::assertTrue($offer->save(), print_r($offer->getErrors(), true));
        self::assertSame('big-summer-bonus', $offer->slug);
    }

    public function testDerivedSlugIsMadeUniqueOnCollision(): void
    {
        $offer = $this->makeOffer(['title' => 'Visible Welcome Bonus', 'slug' => '']);

        self::assertTrue($offer->save(), print_r($offer->getErrors(), true));
        self::assertSame('visible-welcome-bonus-2', $offer->slug);
    }

    public function testAmountCannotBeNegative(): void
    {
        $offer = $this->makeOffer(['amount' => '-1']);

        self::assertFalse($offer->validate());
        self::assertArrayHasKey('amount', $offer->getErrors());
    }

    public function testNewExpiryMustBeInTheFuture(): void
    {
        $offer = $this->makeOffer(['expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]);

        self::assertFalse($offer->validate());
        self::assertArrayHasKey('expires_at', $offer->getErrors());
    }

    public function testFutureExpiryAndNullExpiryAreAccepted(): void
    {
        $future = $this->makeOffer(['expires_at' => date('Y-m-d H:i:s', strtotime('+1 day'))]);
        self::assertTrue($future->validate(), print_r($future->getErrors(), true));

        $never = $this->makeOffer(['expires_at' => null]);
        self::assertTrue($never->validate(), print_r($never->getErrors(), true));
    }

    public function testMalformedExpiryIsRejected(): void
    {
        $offer = $this->makeOffer(['expires_at' => '31/12/2030']);

        self::assertFalse($offer->validate());
        self::assertArrayHasKey('expires_at', $offer->getErrors());
    }

    public function testStoredPastExpiryCanStillBeEdited(): void
    {
        $offer = Offer::findOne(4);   // expired status, expiry a year ago
        self::assertNotNull($offer);

        $offer->title = 'Expired Welcome Bonus (archived)';

        self::assertTrue($offer->validate(), print_r($offer->getErrors(), true));
        self::assertTrue($offer->save(), print_r($offer->getErrors(), true));
    }

    public function testLapsedOfferCannotBePublished(): void
    {
        $offer = Offer::findOne(4);
        self::assertNotNull($offer);

        $offer->status = OfferStatus::Active->value;

        self::assertFalse($offer->validate());
        self::assertArrayHasKey('status', $offer->getErrors());
    }

    public function testStatusDefaultsToDraft(): void
    {
        $offer = new Offer([
            'casino_id' => 1,
            'title' => 'Defaulted Offer',
            'type' => OfferType::NoDeposit->value,
            'amount' => '15.00',
        ]);

        self::assertTrue($offer->save(), print_r($offer->getErrors(), true));
        self::assertSame(OfferStatus::Draft->value, $offer->status);
    }

    public function testActiveAndNotExpiredScopeHidesEverythingElse(): void
    {
        $visible = Offer::find()->active()->notExpired()->orderBy('id')->all();

        self::assertSame([1, 2], array_column($visible, 'id'));
    }

    public function testWithCasinoEagerLoadsTheRelation(): void
    {
        $offers = Offer::find()->withCasino()->orderBy('id')->all();

        self::assertNotEmpty($offers);
        foreach ($offers as $offer) {
            self::assertTrue($offer->isRelationPopulated('casino'));
        }
        self::assertSame('Fixture Casino One', $offers[0]->casino->name);
    }

    public function testCasinoOffersRelationReturnsItsOwnOffers(): void
    {
        $casino = Casino::findOne(2);
        self::assertNotNull($casino);

        $offers = $casino->offers;

        self::assertSame([4, 5], array_column($offers, 'id'));
    }

    public function testIsExpiredReflectsTheStoredDate(): void
    {
        self::assertTrue((bool) Offer::findOne(4)?->isExpired());
        self::assertFalse((bool) Offer::findOne(1)?->isExpired());
        self::assertFalse((bool) Offer::findOne(2)?->isExpired());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeOffer(array $attributes = []): Offer
    {
        return new Offer($attributes + [
            'casino_id' => 1,
            'title' => 'Test Offer',
            'slug' => 'test-offer',
            'type' => OfferType::Welcome->value,
            'amount' => '100.00',
            'status' => OfferStatus::Draft->value,
        ]);
    }
}
