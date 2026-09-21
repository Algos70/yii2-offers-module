<?php

declare(strict_types=1);

namespace common\tests\Unit\Enums;

use Codeception\Test\Unit;
use common\enums\OfferStatus;
use Yii;

final class OfferStatusTest extends Unit
{
    public function testValuesAreTheValidatorRange(): void
    {
        self::assertSame(['draft', 'active', 'expired'], OfferStatus::values());
    }

    public function testEveryCaseHasALabel(): void
    {
        foreach (OfferStatus::cases() as $case) {
            self::assertArrayHasKey($case->value, OfferStatus::labels());
            self::assertNotSame('', $case->label());
        }

        self::assertCount(count(OfferStatus::cases()), OfferStatus::labels());
    }

    public function testLabelForUnknownValueIsEmpty(): void
    {
        self::assertSame('', OfferStatus::labelFor('archived'));
        self::assertSame('', OfferStatus::labelFor(null));
        self::assertSame('Active', OfferStatus::labelFor('active'));
    }

    /**
     * The enum and the `status` ENUM column must not drift apart.
     */
    public function testValuesMatchTheDatabaseColumn(): void
    {
        $column = Yii::$app->db->getTableSchema('{{%offer}}', true)?->getColumn('status');

        self::assertNotNull($column);
        self::assertSame(OfferStatus::values(), $column->enumValues);
    }
}
