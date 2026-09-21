<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use frontend\tests\Support\FunctionalTester;

final class HomeCest
{
    public function _fixtures(): array
    {
        return [
            'casino' => ['class' => CasinoFixture::class],
            'offer' => ['class' => OfferFixture::class],
            'offerTerms' => ['class' => OfferTermsFixture::class],
        ];
    }

    public function homeCountsTheLiveCatalogue(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/index');

        $I->see('Every live offer, with the terms up front.');
        $I->see('live offers');
        $I->see('casinos listed');
    }

    public function homePreviewsTheLatestOffers(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/index');

        $I->see('Latest offers');
        $I->see('Visible Welcome Bonus');
        $I->see('Visible Free Spins');

        // The same visibility rule as /offers: nothing here may 404 when clicked.
        $I->dontSee('Draft No Deposit');
        $I->dontSee('Expired Welcome Bonus');
        $I->dontSee('Lapsed Free Spins');
    }

    public function homeLinksIntoTheListing(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/index');

        $I->click('Browse all offers');
        $I->seeInCurrentUrl('offer');
        $I->see('Showing 1–2 of 2');
    }

    public function typeShortcutsPreselectTheFilter(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/index');

        // The card's text starts with its badge, so match the link by target.
        $I->click('a[href*="type=free_spins"]');
        $I->see('Visible Free Spins');
        $I->dontSee('Visible Welcome Bonus');
    }
}
