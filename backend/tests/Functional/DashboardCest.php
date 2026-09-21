<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use common\fixtures\UserFixture;

final class DashboardCest
{
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'login_data.php',
            ],
            'casino' => ['class' => CasinoFixture::class],
            'offer' => ['class' => OfferFixture::class],
            'offerTerms' => ['class' => OfferTermsFixture::class],
        ];
    }

    public function guestSeesTheLoginPageInstead(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/index');

        $I->seeInCurrentUrl('site%2Flogin');
    }

    public function dashboardShowsCatalogueCountersAndLatestOffers(FunctionalTester $I): void
    {
        $this->login($I);
        $I->amOnRoute('/site/index');

        $I->see('Welcome back, erau');
        $I->see('Casinos');
        $I->see('Publicly visible');
        $I->see('Latest offers');
        $I->see('Offers by status');

        // Newest fixture offer, with its casino name underneath.
        $I->see('Lapsed Free Spins');
        $I->see('Fixture Casino Two');
    }

    public function dashboardWarnsAboutPublishedOffersThatSilentlyLapsed(FunctionalTester $I): void
    {
        $this->login($I);
        $I->amOnRoute('/site/index');

        // Fixture offer 5 is active with an expiry in the past.
        $I->see('still marked active although its expiry has passed');
        $I->seeLink('Review them');
    }

    public function statCardsLinkIntoTheFilteredLists(FunctionalTester $I): void
    {
        $this->login($I);
        $I->amOnRoute('/site/index');

        // The label sits in a nested div, so match the whole card anchor.
        $I->click('//a[contains(@class, "dashboard-stat")][contains(., "Publicly visible")]');
        $I->seeInCurrentUrl('offer%2Findex');
        $I->seeInCurrentUrl('status');
    }

    private function login(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/login');
        $I->fillField('Your Username', 'erau');
        $I->fillField('Your Password', 'password_0');
        $I->click('login-button');
    }
}
