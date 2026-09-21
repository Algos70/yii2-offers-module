<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use common\fixtures\UserFixture;
use common\models\Casino;
use common\models\Offer;
use common\models\OfferTerms;

/**
 * Security properties of the admin screens, kept as executable assertions so a
 * regression fails the suite instead of waiting for a reviewer to notice.
 */
final class OfferSecurityCest
{
    private const XSS = '<script>alert("xss")</script>';

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

    public function everyAdminRouteRejectsGuests(FunctionalTester $I): void
    {
        $routes = [
            '/casino/index', '/casino/create', '/casino/view', '/casino/update', '/casino/delete',
            '/offer/index', '/offer/create', '/offer/view', '/offer/update', '/offer/delete',
        ];

        foreach ($routes as $route) {
            $I->amOnRoute($route, ['id' => 1]);
            $I->seeInCurrentUrl('site%2Flogin');
        }

        // Nothing was touched while logged out.
        $I->seeRecord(Offer::class, ['id' => 1]);
        $I->seeRecord(Casino::class, ['id' => 1]);
    }

    public function postWithoutCsrfTokenIsRejected(FunctionalTester $I): void
    {
        $this->login($I);

        $I->sendAjaxPostRequest('/index-test.php?r=offer%2Fcreate', [
            'Offer' => [
                'casino_id' => 1,
                'title' => 'Forged Offer',
                'type' => 'welcome',
                'amount' => '10',
            ],
        ]);

        $I->seeResponseCodeIs(400);
        $I->dontSeeRecord(Offer::class, ['title' => 'Forged Offer']);
    }

    public function storedScriptInTermsNoteIsEscapedOnTheDetailPage(FunctionalTester $I): void
    {
        $terms = OfferTerms::findOne(1);
        $terms->terms_note = self::XSS;
        $terms->save(false);

        $this->login($I);
        $I->amOnRoute('/offer/view', ['id' => 1]);

        $I->see(self::XSS);                 // rendered as visible text
        $I->dontSeeInSource(self::XSS);     // never as executable markup
        $I->seeInSource('&lt;script&gt;');
    }

    public function storedScriptInTitleIsEscapedInTheGrid(FunctionalTester $I): void
    {
        $offer = Offer::findOne(1);
        $offer->title = self::XSS;
        $offer->save(false);

        $this->login($I);
        $I->amOnRoute('/offer/index');

        $I->dontSeeInSource(self::XSS);
        $I->seeInSource('&lt;script&gt;');
    }

    public function storedScriptInCasinoNameIsEscapedInTheOfferGrid(FunctionalTester $I): void
    {
        $casino = Casino::findOne(1);
        $casino->name = self::XSS;
        $casino->save(false);

        $this->login($I);
        $I->amOnRoute('/offer/index');

        // The related column goes through GridView's default encoding too.
        $I->dontSeeInSource(self::XSS);
    }

    public function aJavascriptUrlSmuggledIntoTermsCannotBecomeAnHref(FunctionalTester $I): void
    {
        // The `url` validator blocks this on input; this covers the case where
        // a row reaches the table another way (import, manual SQL, fixture).
        $terms = OfferTerms::findOne(1);
        $terms->terms_url = 'javascript:alert(1)';
        $terms->save(false);

        $this->login($I);
        $I->amOnRoute('/offer/view', ['id' => 1]);

        // Yii's `url` formatter prefixes unknown schemes, so the anchor is
        // inert: href="http://javascript:alert(1)".
        $I->dontSeeInSource('href="javascript:');
    }

    public function filterValuesAreBoundNotInterpolated(FunctionalTester $I): void
    {
        $this->login($I);

        // A classic injection payload must be treated as a literal search term.
        $I->amOnRoute('/offer/index', ['OfferSearch' => ['title' => "' OR 1=1 -- "]]);

        $I->seeResponseCodeIs(200);
        $I->dontSee('Visible Welcome Bonus');   // matches nothing, rather than everything
        $I->seeRecord(Offer::class, ['id' => 1]);
    }

    public function unknownSortColumnIsIgnored(FunctionalTester $I): void
    {
        $this->login($I);

        // Not in Sort::$attributes, so it must never reach ORDER BY.
        $I->amOnRoute('/offer/index', ['sort' => 'password_hash']);

        $I->seeResponseCodeIs(200);
        $I->see('Visible Welcome Bonus');
    }

    public function offerIdIsNotMassAssignableOnTerms(FunctionalTester $I): void
    {
        $this->login($I);

        $terms = new OfferTerms();
        $terms->load(['OfferTerms' => ['offer_id' => 999, 'wagering_multiplier' => '10']]);

        // The FK is not in rules(), so load() must leave it untouched.
        $I->assertEmpty($terms->getAttribute('offer_id'));
        $I->assertSame('10', $terms->wagering_multiplier);
    }

    public function missingRecordsReturnNotFound(FunctionalTester $I): void
    {
        $this->login($I);

        $I->amOnRoute('/offer/view', ['id' => 99999]);
        $I->seeResponseCodeIs(404);

        $I->amOnRoute('/casino/view', ['id' => 99999]);
        $I->seeResponseCodeIs(404);
    }

    private function login(FunctionalTester $I): void
    {
        $I->amOnRoute('/site/login');
        $I->fillField('Your Username', 'erau');
        $I->fillField('Your Password', 'password_0');
        $I->click('login-button');
    }
}
