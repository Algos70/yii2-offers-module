<?php

declare(strict_types=1);

namespace common\tests\Unit\Models;

use Codeception\Test\Unit;
use common\enums\OfferType;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use common\models\Offer;
use common\models\OfferTerms;

final class OfferTermsTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'casino' => [
                'class' => CasinoFixture::class,
                'dataFile' => codecept_data_dir() . 'casino.php',
            ],
            'offer' => [
                'class' => OfferFixture::class,
                'dataFile' => codecept_data_dir() . 'offer.php',
            ],
            'offerTerms' => [
                'class' => OfferTermsFixture::class,
                'dataFile' => codecept_data_dir() . 'offer_terms.php',
            ],
        ];
    }

    public function testWageringMustStayInsideItsRange(): void
    {
        $terms = new OfferTerms(['wagering_multiplier' => '500']);
        self::assertFalse($terms->validate());
        self::assertArrayHasKey('wagering_multiplier', $terms->getErrors());

        $negative = new OfferTerms(['wagering_multiplier' => '-1']);
        self::assertFalse($negative->validate());

        $edge = new OfferTerms(['wagering_multiplier' => '200']);
        self::assertTrue($edge->validate(), print_r($edge->getErrors(), true));
    }

    public function testValidDaysMustBeAtLeastOneDay(): void
    {
        $zero = new OfferTerms(['valid_days' => 0]);
        self::assertFalse($zero->validate());
        self::assertArrayHasKey('valid_days', $zero->getErrors());

        $tooLong = new OfferTerms(['valid_days' => 366]);
        self::assertFalse($tooLong->validate());
    }

    public function testTermsUrlMustBeAUrl(): void
    {
        $script = new OfferTerms(['terms_url' => 'javascript:alert(1)']);
        self::assertFalse($script->validate());
        self::assertArrayHasKey('terms_url', $script->getErrors());

        $schemeless = new OfferTerms(['terms_url' => 'example.com/terms']);
        self::assertTrue($schemeless->validate(), print_r($schemeless->getErrors(), true));
        self::assertSame('https://example.com/terms', $schemeless->terms_url);
    }

    public function testCashoutCapCannotSitBelowTheBonusCap(): void
    {
        $terms = new OfferTerms(['max_bonus' => '100', 'max_cashout' => '50']);

        self::assertFalse($terms->validate());
        self::assertArrayHasKey('max_cashout', $terms->getErrors());
    }

    public function testWelcomeOfferRequiresAMinimumDeposit(): void
    {
        $terms = new OfferTerms(['wagering_multiplier' => '35']);
        $terms->offerType = OfferType::Welcome->value;

        self::assertFalse($terms->validate());
        self::assertArrayHasKey('min_deposit', $terms->getErrors());
    }

    public function testNoDepositOfferRejectsAMinimumDeposit(): void
    {
        $terms = new OfferTerms(['min_deposit' => '20']);
        $terms->offerType = OfferType::NoDeposit->value;

        self::assertFalse($terms->validate());
        self::assertArrayHasKey('min_deposit', $terms->getErrors());
    }

    public function testFreeSpinsOfferAcceptsAllNullNumerics(): void
    {
        $terms = new OfferTerms(['valid_days' => 7]);
        $terms->offerType = OfferType::FreeSpins->value;

        self::assertTrue($terms->validate(), print_r($terms->getErrors(), true));
    }

    public function testIsEmptyIgnoresTimestampsAndForeignKey(): void
    {
        $blank = new OfferTerms();
        $blank->offer_id = 1;
        self::assertTrue($blank->isEmpty());

        $filled = new OfferTerms(['terms_note' => 'Slots only.']);
        self::assertFalse($filled->isEmpty());
    }

    public function testRelationsAreWiredBothWays(): void
    {
        $offer = Offer::findOne(1);
        self::assertNotNull($offer);
        self::assertNotNull($offer->terms);
        self::assertSame('35.0', $offer->terms->wagering_multiplier);
        self::assertSame(1, $offer->terms->offer->id);

        $withoutTerms = Offer::findOne(3);
        self::assertNotNull($withoutTerms);
        self::assertNull($withoutTerms->terms);
    }

    public function testWithTermsEagerLoadsTheRelation(): void
    {
        $offers = Offer::find()->withTerms()->orderBy('id')->all();

        self::assertNotEmpty($offers);
        foreach ($offers as $offer) {
            self::assertTrue($offer->isRelationPopulated('terms'));
        }
    }

    public function testSaveWithTermsWritesBothRows(): void
    {
        $offer = $this->makeOffer();
        $terms = new OfferTerms(['wagering_multiplier' => '30', 'min_deposit' => '10']);
        $terms->offerType = $offer->type;

        self::assertTrue($offer->saveWithTerms($terms));

        $stored = OfferTerms::findOne($offer->id);
        self::assertNotNull($stored);
        self::assertSame('30.0', $stored->wagering_multiplier);
    }

    public function testSaveWithTermsSkipsAnEmptyTermsRow(): void
    {
        $offer = $this->makeOffer();

        self::assertTrue($offer->saveWithTerms(new OfferTerms()));
        self::assertNull(OfferTerms::findOne($offer->id));
        self::assertNull($offer->terms);
    }

    public function testBlankingOutTermsDeletesTheRow(): void
    {
        $offer = Offer::findOne(1);
        self::assertNotNull($offer);
        $terms = $offer->terms;
        self::assertNotNull($terms);

        $blankable = [
            'wagering_multiplier',
            'min_deposit',
            'max_bonus',
            'max_cashout',
            'valid_days',
            'terms_url',
            'terms_note',
        ];
        foreach ($blankable as $attribute) {
            $terms->$attribute = null;
        }

        self::assertTrue($offer->saveWithTerms($terms));
        self::assertNull(OfferTerms::findOne(1));
        self::assertNotNull(Offer::findOne(1));
    }

    public function testFailedTermsWriteLeavesNoOfferBehind(): void
    {
        $offer = $this->makeOffer(['slug' => 'rollback-probe']);
        // Trips chk-offer_terms-valid_days at the database level: the model rules
        // were bypassed on purpose to prove the transaction, not the validator.
        $terms = new OfferTerms();
        $terms->valid_days = 9999;

        $countBefore = Offer::find()->count();

        try {
            $offer->saveWithTerms($terms);
            self::fail('The out-of-range terms row should have been rejected by the check constraint.');
        } catch (\yii\db\Exception) {
            // expected
        }

        self::assertSame($countBefore, Offer::find()->count());
        self::assertNull(Offer::findOne(['slug' => 'rollback-probe']));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeOffer(array $attributes = []): Offer
    {
        $offer = new Offer($attributes + [
            'casino_id' => 1,
            'title' => 'Terms Test Offer',
            'slug' => 'terms-test-offer',
            'type' => OfferType::Welcome->value,
            'amount' => '100.00',
        ]);

        self::assertTrue($offer->validate(), print_r($offer->getErrors(), true));

        return $offer;
    }
}
