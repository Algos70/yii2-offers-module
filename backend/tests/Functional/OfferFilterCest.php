<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use common\fixtures\UserFixture;

final class OfferFilterCest
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

    public function _before(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/login');
        $I->fillField('Your Username', 'erau');
        $I->fillField('Your Password', 'password_0');
        $I->click('login-button');
    }

    public function listRendersEveryOffer(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index');

        $I->see('Visible Welcome Bonus');
        $I->see('Draft No Deposit');
        $I->see('Expired Welcome Bonus');
    }

    public function filterByType(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index', ['OfferSearch' => ['type' => 'free_spins']]);

        $I->see('Visible Free Spins');
        $I->see('Lapsed Free Spins');
        $I->dontSee('Visible Welcome Bonus');
    }

    public function filterByStatus(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index', ['OfferSearch' => ['status' => 'draft']]);

        $I->see('Draft No Deposit');
        $I->dontSee('Visible Welcome Bonus');
    }

    public function filterByCasino(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index', ['OfferSearch' => ['casino_id' => 2]]);

        $I->see('Expired Welcome Bonus');
        $I->dontSee('Draft No Deposit');
    }

    public function filterByMaxWagering(FunctionalTester $I): void
    {
        // Offer 1 is 35x, offer 2 is 45x.
        $I->amOnRoute('/offer/index', ['OfferSearch' => ['maxWagering' => '40']]);

        $I->see('Visible Welcome Bonus');
        $I->dontSee('Visible Free Spins');
    }

    public function unknownFilterValueIsIgnored(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index', ['OfferSearch' => ['type' => 'cashback']]);

        $I->seeResponseCodeIs(200);
        $I->see('Visible Welcome Bonus');
        $I->see('Draft No Deposit');
    }

    public function sortByTitleReordersTheFirstRow(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index', ['sort' => 'title']);
        $I->see('Draft No Deposit', 'tbody tr:first-child');

        $I->amOnRoute('/offer/index', ['sort' => '-title']);
        $I->see('Visible Welcome Bonus', 'tbody tr:first-child');
    }

    public function gridExposesTheFilterControls(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index');

        $I->seeElement('select', ['name' => 'OfferSearch[casino_id]']);
        $I->seeElement('select', ['name' => 'OfferSearch[type]']);
        $I->seeElement('select', ['name' => 'OfferSearch[status]']);
        $I->seeElement('input', ['name' => 'OfferSearch[maxWagering]']);
    }
}
