<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use common\fixtures\UserFixture;
use common\models\Offer;
use common\models\OfferTerms;

final class OfferCrudCest
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

    public function guestIsSentToLogin(FunctionalTester $I): void
    {
        foreach (['index', 'create', 'view', 'update'] as $action) {
            $I->amOnRoute('/offer/' . $action, ['id' => 1]);
            $I->seeInCurrentUrl('site%2Flogin');
        }
    }

    public function listShowsOffersWithCasinoAndTerms(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/offer/index');
        $I->see('Visible Welcome Bonus');
        $I->see('Fixture Casino One');   // relation rendered, not the raw id
        $I->see('Welcome bonus');        // enum label, not the raw value
        $I->see('35x');                  // terms relation rendered
    }

    public function createWritesOfferAndTerms(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/offer/create');
        $I->selectOption('Offer[casino_id]', '1');
        $I->fillField('Offer[title]', 'Midweek Reload');
        $I->fillField('Offer[slug]', '');
        $I->selectOption('Offer[type]', 'welcome');
        $I->fillField('Offer[amount]', '75.00');
        $I->fillField('OfferTerms[wagering_multiplier]', '30');
        $I->fillField('OfferTerms[min_deposit]', '15');
        $I->click('Save');

        $I->see('Offer created.');
        $offer = Offer::findOne(['slug' => 'midweek-reload']);
        $I->assertNotNull($offer);
        $I->assertSame('draft', $offer->status);
        $I->seeRecord(OfferTerms::class, ['offer_id' => $offer->id, 'min_deposit' => '15.00']);
    }

    public function createWithoutTermsWritesNoTermsRow(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/offer/create');
        $I->selectOption('Offer[casino_id]', '1');
        $I->fillField('Offer[title]', 'Bare Offer');
        $I->selectOption('Offer[type]', 'free_spins');
        $I->fillField('Offer[amount]', '20');
        $I->click('Save');

        $I->see('Offer created.');
        $offer = Offer::findOne(['slug' => 'bare-offer']);
        $I->assertNotNull($offer);
        $I->dontSeeRecord(OfferTerms::class, ['offer_id' => $offer->id]);
        $I->see('No terms recorded for this offer.');
    }

    public function welcomeOfferWithoutMinimumDepositIsRejectedAndNothingIsWritten(
        FunctionalTester $I,
    ): void {
        $this->login($I);

        $I->amOnRoute('/offer/create');
        $I->selectOption('Offer[casino_id]', '1');
        $I->fillField('Offer[title]', 'Incomplete Welcome');
        $I->selectOption('Offer[type]', 'welcome');
        $I->fillField('Offer[amount]', '50');
        $I->fillField('OfferTerms[wagering_multiplier]', '40');
        $I->click('Save');

        $I->see('Min deposit cannot be blank.');
        $I->dontSeeRecord(Offer::class, ['title' => 'Incomplete Welcome']);
    }

    public function pastExpiryIsRejected(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/offer/create');
        $I->selectOption('Offer[casino_id]', '1');
        $I->fillField('Offer[title]', 'Stale Offer');
        $I->selectOption('Offer[type]', 'no_deposit');
        $I->fillField('Offer[amount]', '5');
        $I->fillField('Offer[expires_at]', date('Y-m-d H:i:s', strtotime('-1 day')));
        $I->click('Save');

        $I->see('Expiry date must be in the future.');
        $I->dontSeeRecord(Offer::class, ['title' => 'Stale Offer']);
    }

    public function updateReusesTheExistingTermsRow(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/offer/update', ['id' => 1]);
        $I->fillField('OfferTerms[wagering_multiplier]', '25');
        $I->click('Save');

        $I->see('Offer updated.');
        $I->seeRecord(OfferTerms::class, ['offer_id' => 1, 'wagering_multiplier' => '25.0']);
        $I->assertSame(1, (int) OfferTerms::find()->where(['offer_id' => 1])->count());
    }

    public function storedNoteIsEscapedOnTheDetailPage(FunctionalTester $I): void
    {
        $terms = OfferTerms::findOne(1);
        $terms->terms_note = '<script>alert(1)</script>';
        $terms->save(false);

        $this->login($I);
        $I->amOnRoute('/offer/view', ['id' => 1]);

        $I->see('<script>alert(1)</script>');                    // visible as text
        $I->dontSeeInSource('<script>alert(1)</script>');        // never as markup
    }

    public function deleteOverGetIsRejected(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/offer/delete', ['id' => 1]);

        $I->seeResponseCodeIs(405);
        $I->seeRecord(Offer::class, ['id' => 1]);
    }

    public function deleteRemovesOfferAndItsTerms(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/offer/view', ['id' => 1]);
        $token = $I->grabAttributeFrom('meta[name="csrf-token"]', 'content');

        $I->sendAjaxPostRequest('/index-test.php?r=offer%2Fdelete&id=1', ['_csrf-backend' => $token]);

        $I->dontSeeRecord(Offer::class, ['id' => 1]);
        $I->dontSeeRecord(OfferTerms::class, ['offer_id' => 1]);
    }

    private function login(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/login');
        $I->fillField('Your Username', 'erau');
        $I->fillField('Your Password', 'password_0');
        $I->click('login-button');
    }
}
