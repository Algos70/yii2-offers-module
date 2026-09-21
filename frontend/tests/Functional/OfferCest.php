<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use frontend\tests\Support\FunctionalTester;

/**
 * The public offers pages.
 *
 * Fixture layout the expectations rest on: casino 1 is active, casino 2 is not.
 * Offer 1 (welcome, never expires) and offer 2 (free spins, future expiry) are
 * live; offer 3 is a draft, offer 4 is expired and offer 5 is active but its
 * date has passed — and both of those belong to the inactive casino anyway.
 */
final class OfferCest
{
    public function _fixtures(): array
    {
        return [
            'casino' => ['class' => CasinoFixture::class],
            'offer' => ['class' => OfferFixture::class],
            'offerTerms' => ['class' => OfferTermsFixture::class],
        ];
    }

    public function listingShowsOnlyPubliclyVisibleOffers(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index');

        $I->see('Visible Welcome Bonus');
        $I->see('Visible Free Spins');

        $I->dontSee('Draft No Deposit');            // status draft
        $I->dontSee('Expired Welcome Bonus');       // status expired
        $I->dontSee('Lapsed Free Spins');           // active, but the date passed
    }

    public function listingRendersTheCardFacts(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index');

        $I->see('Welcome bonus');       // type badge label
        $I->see('€100');                // amount with its currency
        $I->see('spins');               // free-spins unit
        $I->see('35× wagering');        // terms chip
        $I->see('No expiry');           // null expires_at
        $I->see('Fixture Casino One');
        $I->see('Showing 1–2 of 2');
    }

    public function listingFiltersByType(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index', ['type' => 'free_spins']);

        $I->see('Visible Free Spins');
        $I->dontSee('Visible Welcome Bonus');
    }

    public function listingFiltersByCasinoSlug(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index', ['casino' => 'fixture-casino-one']);

        $I->see('Visible Welcome Bonus');
        $I->see('Showing 1–2 of 2');
    }

    public function unknownFilterValuesAreIgnoredRatherThanFailing(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index', ['type' => 'cashback', 'casino' => "' OR 1=1 -- "]);

        $I->seeResponseCodeIs(200);
        $I->see('Visible Welcome Bonus');
        $I->see('Visible Free Spins');
    }

    public function inactiveCasinosAreNotOfferedAsAFilter(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index');

        $I->seeElement('select[name=casino] option[value=fixture-casino-one]');
        $I->dontSeeElement('select[name=casino] option[value=fixture-casino-two]');
    }

    /**
     * The Apply button is disabled by a script once the form matches what is
     * already applied. That is an enhancement, so the served HTML must leave
     * it usable: with JavaScript off the filters still submit.
     */
    public function applyButtonIsUsableWithoutJavascript(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/index');

        $I->seeElement('[data-offer-filter-submit]');
        $I->dontSeeElement('[data-offer-filter-submit][disabled]');
    }

    public function emptyResultExplainsItself(FunctionalTester $I): void
    {
        // Casino one publishes no no-deposit offer.
        $I->amOnRoute('/offer/index', ['type' => 'no_deposit', 'casino' => 'fixture-casino-one']);

        $I->see('No offers match those filters');
        $I->see('Fixture Casino One has no matching offers right now.');
        $I->seeLink('Clear filters');
    }

    public function detailPageShowsTheOffer(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/view', ['slug' => 'visible-welcome-bonus']);

        $I->seeResponseCodeIs(200);
        $I->see('Visible Welcome Bonus', 'h1');
        $I->see('Bonus terms');
        $I->see('Min deposit');
        $I->see('Slots only.');                     // terms_note
        $I->seeLink('Fixture Casino One');
    }

    public function detailPageOfAnOfferWithoutTermsSaysSo(FunctionalTester $I): void
    {
        $I->amOnRoute('/offer/view', ['slug' => 'visible-free-spins']);

        $I->seeResponseCodeIs(200);
        $I->see('Wagering');
        $I->dontSee('Min deposit');                 // that column is null here
    }

    /**
     * A draft, an expired offer, one whose date lapsed, one belonging to an
     * inactive casino and a slug that never existed must be indistinguishable.
     */
    public function hiddenOffersAllAnswerWithTheSame404(FunctionalTester $I): void
    {
        $slugs = [
            'draft-no-deposit',
            'expired-welcome-bonus',
            'lapsed-free-spins',
            'never-existed-at-all',
        ];

        $pages = [];

        foreach ($slugs as $slug) {
            $I->amOnRoute('/offer/view', ['slug' => $slug]);
            $I->seeResponseCodeIs(404);
            $I->see('This page isn’t available');
            $I->dontSee($slug);                      // the slug is never echoed back
            $pages[$slug] = $I->grabTextFrom('.site-error');
        }

        // Identical wording every time: anything that distinguished "draft"
        // from "never existed" would let the 404 enumerate unpublished offers.
        $I->assertCount(1, array_unique($pages));
    }
}
