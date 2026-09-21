<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use frontend\tests\Support\FunctionalTester;

/**
 * The public site has no accounts.
 *
 * This is a security boundary, not a styling choice: both applications share
 * the `user` table and the backend authorises on `roles => ['@']`, so any
 * active user is an administrator. A public signup or password-reset flow
 * would therefore hand out admin access, and these routes must stay gone.
 */
final class NoPublicAccountsCest
{
    public function authRoutesDoNotExist(FunctionalTester $I): void
    {
        $routes = [
            '/site/login',
            '/site/logout',
            '/site/signup',
            '/site/request-password-reset',
            '/site/reset-password',
            '/site/verify-email',
            '/site/resend-verification-email',
        ];

        foreach ($routes as $route) {
            $I->amOnRoute($route);
            $I->seeResponseCodeIs(404);
        }
    }

    public function navigationOffersNoWayToSignIn(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/index');

        $I->dontSeeLink('Login');
        $I->dontSeeLink('Signup');
        $I->dontSeeLink('Logout');
        $I->seeLink('Offers');
    }
}
