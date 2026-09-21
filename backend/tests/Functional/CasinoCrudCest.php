<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\fixtures\CasinoFixture;
use common\fixtures\UserFixture;
use common\models\Casino;

final class CasinoCrudCest
{
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'login_data.php',
            ],
            'casino' => ['class' => CasinoFixture::class],
        ];
    }

    public function guestIsSentToLogin(FunctionalTester $I): void
    {
        foreach (['index', 'create', 'view', 'update'] as $action) {
            $I->amOnRoute('/casino/' . $action, ['id' => 1]);
            $I->seeInCurrentUrl('site%2Flogin');
        }
    }

    public function listShowsSeededCasinos(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/casino/index');
        $I->see('Fixture Casino One');
        $I->see('Fixture Casino Two');
    }

    public function filterNarrowsTheList(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/casino/index', ['CasinoSearch' => ['name' => 'Two']]);
        $I->see('Fixture Casino Two');
        $I->dontSee('Fixture Casino One');
    }

    public function createDerivesTheSlug(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/casino/create');
        $I->fillField('Casino[name]', 'Neon Nights');
        $I->fillField('Casino[slug]', '');
        $I->fillField('Casino[rating]', '4.2');
        $I->click('Save');

        $I->see('Casino created.');
        // Active is pre-checked on the create form, so an untouched field stores 1.
        $I->seeRecord(Casino::class, ['name' => 'Neon Nights', 'slug' => 'neon-nights', 'is_active' => 1]);
    }

    public function createRejectsAnOutOfRangeRating(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/casino/create');
        $I->fillField('Casino[name]', 'Broken Rating');
        $I->fillField('Casino[rating]', '9');
        $I->click('Save');

        $I->see('Rating (0-5) must be no greater than 5');
        $I->dontSeeRecord(Casino::class, ['name' => 'Broken Rating']);
    }

    public function updateChangesTheRecord(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/casino/update', ['id' => 1]);
        $I->fillField('Casino[name]', 'Fixture Casino One Renamed');
        $I->click('Save');

        $I->see('Casino updated.');
        $I->seeRecord(Casino::class, ['id' => 1, 'name' => 'Fixture Casino One Renamed']);
    }

    public function deleteOverGetIsRejected(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/casino/delete', ['id' => 1]);

        $I->seeResponseCodeIs(405);
        $I->seeRecord(Casino::class, ['id' => 1]);
    }

    public function deleteWithoutCsrfTokenIsRejected(FunctionalTester $I): void
    {
        $this->login($I);

        $I->sendAjaxPostRequest('/index-test.php?r=casino%2Fdelete&id=2');

        $I->seeResponseCodeIs(400);
        $I->seeRecord(Casino::class, ['id' => 2]);
    }

    public function deleteOverPostRemovesTheRecord(FunctionalTester $I): void
    {
        $this->login($I);

        // The delete link is a data-method="post" anchor, so the token that the
        // real request carries is the one published in the page's meta tag.
        $I->amOnRoute('/casino/view', ['id' => 2]);
        $token = $I->grabAttributeFrom('meta[name="csrf-token"]', 'content');

        $I->sendAjaxPostRequest('/index-test.php?r=casino%2Fdelete&id=2', ['_csrf-backend' => $token]);

        $I->dontSeeRecord(Casino::class, ['id' => 2]);
    }

    private function login(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/login');
        $I->fillField('Your Username', 'erau');
        $I->fillField('Your Password', 'password_0');
        $I->click('login-button');
    }
}
