<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use frontend\tests\Support\FunctionalTester;

/**
 * Fixtures: casino 1 is active with two visible offers; casino 2 is inactive
 * and owns the expired and lapsed ones.
 */
final class CasinoCest
{
    public function _fixtures(): array
    {
        return [
            'casino' => ['class' => CasinoFixture::class],
            'offer' => ['class' => OfferFixture::class],
            'offerTerms' => ['class' => OfferTermsFixture::class],
        ];
    }

    public function listingShowsActiveCasinosWithTheirLiveOfferCount(FunctionalTester $I): void
    {
        $I->amOnRoute('/casino/index');

        $I->seeResponseCodeIs(200);
        $I->see('Fixture Casino One');
        $I->see('2 live offers');
        $I->see('1 casino listed');

        // The inactive casino has no page, so it is not advertised either.
        $I->dontSee('Fixture Casino Two');
    }

    public function listingLinksThroughToTheCasinoPage(FunctionalTester $I): void
    {
        $I->amOnRoute('/casino/index');

        $I->click('Fixture Casino One');
        $I->seeInCurrentUrl('fixture-casino-one');
        $I->see('Visible Welcome Bonus');
    }

    public function casinoPageListsItsVisibleOffers(FunctionalTester $I): void
    {
        $I->amOnRoute('/casino/view', ['slug' => 'fixture-casino-one']);

        $I->seeResponseCodeIs(200);
        $I->see('Fixture Casino One', 'h1');
        $I->see('2 live offers');
        $I->see('Visible Welcome Bonus');
        $I->see('Visible Free Spins');
        $I->dontSee('Draft No Deposit');
    }

    public function casinoPageShowsTheRating(FunctionalTester $I): void
    {
        $I->amOnRoute('/casino/view', ['slug' => 'fixture-casino-one']);

        $I->see('4.5');
        $I->seeElement('.rating__fill');
    }

    public function inactiveAndUnknownCasinosAnswerTheSame(FunctionalTester $I): void
    {
        $pages = [];

        foreach (['fixture-casino-two', 'never-existed'] as $slug) {
            $I->amOnRoute('/casino/view', ['slug' => $slug]);
            $I->seeResponseCodeIs(404);
            $pages[$slug] = $I->grabTextFrom('.site-error');
        }

        // Same words either way: a distinguishable 404 would reveal which
        // casinos exist but are unpublished.
        $I->assertCount(1, array_unique($pages));
    }

    public function offerCardsLinkToTheCasinoPage(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index');

        $I->click('Fixture Casino One');
        $I->seeInCurrentUrl('casino');
        $I->see('Fixture Casino One', 'h1');
    }

    public function offerDetailLinksToTheCasinoPage(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/view', ['slug' => 'visible-welcome-bonus']);

        $I->click('Fixture Casino One');
        $I->see('2 live offers');
    }
}
