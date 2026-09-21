<?php

declare(strict_types=1);

namespace common\enums;

/**
 * The kinds of offer a casino can publish.
 *
 * Mirrors the `type` ENUM column of `{{%offer}}`: the database enforces the
 * domain, this enum is the single source of truth for the model's `in`
 * validator range and for every label shown in the UI.
 */
enum OfferType: string
{
    case Welcome = 'welcome';
    case NoDeposit = 'no_deposit';
    case FreeSpins = 'free_spins';

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
            self::Welcome->value => 'Welcome bonus',
            self::NoDeposit->value => 'No deposit',
            self::FreeSpins->value => 'Free spins',
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
