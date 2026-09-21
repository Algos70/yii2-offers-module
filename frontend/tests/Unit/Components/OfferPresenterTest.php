<?php

declare(strict_types=1);

namespace frontend\tests\Unit\Components;

use Codeception\Test\Unit;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use common\models\Offer;
use frontend\components\OfferPresenter;

final class OfferPresenterTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'casino' => ['class' => CasinoFixture::class],
            'offer' => ['class' => OfferFixture::class],
            'offerTerms' => ['class' => OfferTermsFixture::class],
        ];
    }

    public function testCurrencyOffersLeadWithTheSymbolAndCarryNoUnit(): void
    {
        $presenter = $this->presenter(1);   // welcome, amount 100.00

        self::assertSame('€100', $presenter->amount());
        self::assertNull($presenter->amountUnit());
    }

    public function testFreeSpinsAreACountWithATrailingUnit(): void
    {
        $presenter = $this->presenter(2);   // free_spins, amount 50.00

        self::assertSame('50', $presenter->amount());
        self::assertSame('spins', $presenter->amountUnit());
    }

    public function testNullExpiryReadsAsNeverRatherThanUnknown(): void
    {
        $expiry = $this->presenter(1)->expiry();

        self::assertSame('No expiry', $expiry['text']);
        self::assertStringContainsString('fst-italic', $expiry['class']);
    }

    public function testDistantExpiryShowsItsDate(): void
    {
        $offer = $this->offer(2);
        $offer->expires_at = date('Y-m-d H:i:s', strtotime('+90 days'));

        $expiry = (new OfferPresenter($offer))->expiry();

        self::assertStringStartsWith('Ends ', $expiry['text']);
        self::assertSame('bi bi-calendar-event', $expiry['icon']);
        self::assertStringNotContainsString('text-danger', $expiry['class']);
    }

    public function testImminentExpiryIsCalledOut(): void
    {
        $offer = $this->offer(2);
        $offer->expires_at = date('Y-m-d H:i:s', strtotime('+3 days'));

        $expiry = (new OfferPresenter($offer))->expiry();

        self::assertSame('Ends in 3 days', $expiry['text']);
        self::assertStringContainsString('text-danger-emphasis', $expiry['class']);
    }

    public function testCasinoPresenterCarriesTheRatingAndItsLink(): void
    {
        // The rating belongs to the casino, not the offer; the card and the
        // detail page both reach it through here.
        $casino = $this->presenter(1)->casinoPresenter();

        self::assertSame('Fixture Casino One', $casino->name());
        self::assertSame(['/casino/view', 'slug' => 'fixture-casino-one'], $casino->url());
        self::assertSame('4.5', $casino->ratingNumber());
    }

    public function testOnlyPopulatedTermsBecomeChips(): void
    {
        // Fixture terms for offer 1: wagering, min deposit, max bonus, max
        // cashout and valid days are set; offer 2 has no min deposit or bonus.
        $chips = $this->presenter(2)->termChips();
        $labels = array_column($chips, 'label');

        self::assertSame(['wagering', 'max cashout', 'days'], $labels);
        self::assertSame('45×', $chips[0]['value']);
        self::assertSame('€100', $chips[1]['value']);
        self::assertSame('7', $chips[2]['value']);
    }

    public function testAnOfferWithoutATermsRowHasNoChips(): void
    {
        // Offer 3 has no offer_terms row at all.
        self::assertSame([], $this->presenter(3)->termChips());
        self::assertSame([], $this->presenter(3)->termTiles());
    }

    public function testTilesRelabelChipsForTheDetailPage(): void
    {
        $tiles = $this->presenter(2)->termTiles();

        self::assertSame(['Wagering', 'Max cashout', 'Valid for'], array_column($tiles, 'label'));
        self::assertSame('7 days', $tiles[2]['value']);
    }

    public function testFactsSummariseTheOffer(): void
    {
        $facts = $this->presenter(1)->facts();

        self::assertSame(
            [
                ['label' => 'Offer type', 'value' => 'Welcome bonus'],
                ['label' => 'Casino', 'value' => 'Fixture Casino One'],
                ['label' => 'Expires', 'value' => 'Never'],
            ],
            $facts,
        );
    }

    public function testTypeClassBindsTheAccentColour(): void
    {
        self::assertSame('offer--welcome', $this->presenter(1)->typeClass());
        self::assertSame('offer--freespins', $this->presenter(2)->typeClass());
        self::assertSame('offer--nodeposit', $this->presenter(3)->typeClass());
    }

    private function offer(int $id): Offer
    {
        $offer = Offer::find()->withCasino()->withTerms()->andWhere(['offer.id' => $id])->one();
        self::assertInstanceOf(Offer::class, $offer);

        return $offer;
    }

    private function presenter(int $id): OfferPresenter
    {
        return new OfferPresenter($this->offer($id));
    }
}
