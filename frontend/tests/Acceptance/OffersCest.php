<?php

declare(strict_types=1);

namespace frontend\tests\Acceptance;

use frontend\tests\Support\AcceptanceTester;

/**
 * The pretty URLs over real HTTP.
 *
 * The functional suite drives routes directly, so it would still pass if
 * `enablePrettyUrl` or the built-in server's router script were broken. This
 * suite is what proves /offers and /offer/<slug> actually resolve as paths.
 */
final class OffersCest
{
    public function offersListingResolvesAsAPath(AcceptanceTester $I): void
    {
        $I->amOnPage('/offers');

        $I->seeResponseCodeIs(200);
        $I->see('Offers');
        $I->seeElement('select[name=casino]');
    }

    public function navigationReachesTheListing(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Offers');

        $I->seeCurrentUrlEquals('/offers');
    }

    public function unknownOfferSlugIsA404(AcceptanceTester $I): void
    {
        $I->amOnPage('/offer/no-such-offer');

        $I->seeResponseCodeIs(404);
        $I->see('This page isn’t available');
    }
}
