<?php

declare(strict_types=1);

namespace common\enums;

/**
 * Publication state of an offer.
 *
 * Mirrors the `status` ENUM column of `{{%offer}}`. Only `Active` offers are
 * ever shown publicly, and only together with the expiry check.
 */
enum OfferStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Expired = 'expired';

    /**
     * Range for the `in` validator.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Value => human label, for dropdowns and grid columns.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Active->value => 'Active',
            self::Expired->value => 'Expired',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value];
    }

    /**
     * Label for a raw column value, for use in views where the attribute is a string.
     */
    public static function labelFor(?string $value): string
    {
        return self::labels()[$value] ?? '';
    }
}
