<?php

declare(strict_types=1);

namespace common\tests\Unit\Enums;

use Codeception\Test\Unit;
use common\enums\OfferType;
use Yii;

final class OfferTypeTest extends Unit
{
    public function testValuesAreTheValidatorRange(): void
    {
        self::assertSame(['welcome', 'no_deposit', 'free_spins'], OfferType::values());
    }

    public function testEveryCaseHasALabel(): void
    {
        foreach (OfferType::cases() as $case) {
            self::assertArrayHasKey($case->value, OfferType::labels());
            self::assertNotSame('', $case->label());
        }

        self::assertCount(count(OfferType::cases()), OfferType::labels());
    }

    public function testLabelForUnknownValueIsEmpty(): void
    {
        self::assertSame('', OfferType::labelFor('cashback'));
        self::assertSame('', OfferType::labelFor(null));
        self::assertSame('Free spins', OfferType::labelFor('free_spins'));
    }

    /**
     * The enum and the `type` ENUM column must not drift apart: the column is
     * the hard constraint, the enum is what the application validates against.
     */
    public function testValuesMatchTheDatabaseColumn(): void
    {
        $column = Yii::$app->db->getTableSchema('{{%offer}}', true)?->getColumn('type');

        self::assertNotNull($column);
        self::assertSame(OfferType::values(), $column->enumValues);
    }
}
