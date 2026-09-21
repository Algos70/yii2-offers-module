<?php

declare(strict_types=1);

namespace frontend\tests\Acceptance;

use frontend\tests\Support\AcceptanceTester;

final class HomeCest
{
    public function checkHome(AcceptanceTester $I): void
    {
        $I->amOnPage('/');

        $I->see('Every live offer, with the terms up front.');

        $I->seeLink('Offers');
        $I->click('Offers');

        $I->seeCurrentUrlEquals('/offers');
    }
}
