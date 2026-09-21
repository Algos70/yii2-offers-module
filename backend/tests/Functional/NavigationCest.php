<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\fixtures\UserFixture;

final class NavigationCest
{
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'login_data.php',
            ],
        ];
    }

    public function guestSeesNoAdminSections(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/login');

        $I->dontSeeLink('Casinos');
        $I->dontSeeLink('Offers');
    }

    public function signedInUserSeesAdminSections(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/login');
        $I->fillField('Your Username', 'erau');
        $I->fillField('Your Password', 'password_0');
        $I->click('login-button');

        $I->seeLink('Casinos');
        $I->seeLink('Offers');
    }
}
