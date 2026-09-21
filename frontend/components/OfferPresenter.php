<?php

declare(strict_types=1);

namespace frontend\components;

use common\enums\OfferType;
use common\models\Casino;
use common\models\Offer;
use common\models\OfferTerms;
use Yii;

/**
 * View-model for the public offer screens.
 *
 * The listing card and the detail page show the same facts in two layouts, so
 * the wording, icons and number formatting live here instead of being written
 * twice in the views.
 */
final class OfferPresenter
{
    /**
     * `offer.amount` has no currency column; the design renders a single
     * currency and flags that a second one would be a migration, not a CSS fix.
     */
    public const CURRENCY = '€';

    /**
     * An expiry closer than this is called out in red.
     */
    public const SOON_DAYS = 7;

    /**
     * Type value => the class that binds the accent colour in css/site.css.
     */
    private const TYPE_CLASS = [
        OfferType::Welcome->value => 'offer--welcome',
        OfferType::NoDeposit->value => 'offer--nodeposit',
        OfferType::FreeSpins->value => 'offer--freespins',
    ];

    public function __construct(private readonly Offer $offer)
    {
    }

    public function offer(): Offer
    {
        return $this->offer;
    }

    public function casino(): Casino
    {
        return $this->offer->casino;
    }

    public function terms(): ?OfferTerms
    {
        return $this->offer->terms;
    }

    public function typeClass(): string
    {
        return self::TYPE_CLASS[$this->offer->type] ?? '';
    }

    public function typeLabel(): string
    {
        return OfferType::labelFor($this->offer->type);
    }

    public function url(): array
    {
        return ['/offer/view', 'slug' => $this->offer->slug];
    }

    /**
     * The number without its unit: currency offers lead with the symbol, free
     * spins are a plain count whose unit trails the number.
     */
    public function amount(): string
    {
        $amount = Yii::$app->formatter->asDecimal($this->offer->amount, $this->amountDecimals());

        return $this->offer->type === OfferType::FreeSpins->value
            ? $amount
            : self::CURRENCY . $amount;
    }

    /**
     * Trailing unit, or null when the number already carries its meaning.
     */
    public function amountUnit(): ?string
    {
        return $this->offer->type === OfferType::FreeSpins->value ? 'spins' : null;
    }

    /**
     * Expiry as text plus the icon and utility classes the design specifies:
     * a plain date, a red warning when it is close, or italic "No expiry".
     *
     * @return array{text: string, icon: string, class: string}
     */
    public function expiry(): array
    {
        $base = 'd-inline-flex align-items-center gap-1 small ';

        if ($this->offer->expires_at === null) {
            return [
                // NULL means "never expires", not "unknown", so it gets wording
                // of its own rather than an empty slot.
                'text' => 'No expiry',
                'icon' => 'bi bi-infinity',
                'class' => $base . 'text-body-secondary fst-italic',
            ];
        }

        $timestamp = (int) strtotime($this->offer->expires_at);
        $daysLeft = (int) ceil(($timestamp - time()) / 86400);

        if ($daysLeft <= self::SOON_DAYS) {
            return [
                'text' => $daysLeft <= 1 ? 'Ends today' : "Ends in $daysLeft days",
                'icon' => 'bi bi-exclamation-triangle-fill',
                'class' => $base . 'text-danger-emphasis fw-bold',
            ];
        }

        return [
            'text' => 'Ends ' . Yii::$app->formatter->asDate($timestamp, 'php:d M Y'),
            'icon' => 'bi bi-calendar-event',
            'class' => $base . 'text-body-secondary',
        ];
    }

    /**
     * The casino behind this offer, for its name, link and rating.
     */
    public function casinoPresenter(): CasinoPresenter
    {
        return new CasinoPresenter($this->casino());
    }

    /**
     * Populated terms only: every column is nullable and the whole row can be
     * missing, so the view never renders empty slots.
     *
     * @return list<array{icon: string, value: string, label: string}>
     */
    public function termChips(): array
    {
        $terms = $this->terms();

        if ($terms === null) {
            return [];
        }

        $chips = [];

        if ($terms->wagering_multiplier !== null) {
            $chips[] = [
                'icon' => 'bi bi-arrow-repeat',
                'value' => $this->trimDecimals($terms->wagering_multiplier) . '×',
                'label' => 'wagering',
            ];
        }

        if ($terms->min_deposit !== null) {
            $chips[] = [
                'icon' => 'bi bi-wallet2',
                'value' => $this->money($terms->min_deposit),
                'label' => 'min deposit',
            ];
        }

        if ($terms->max_bonus !== null) {
            $chips[] = [
                'icon' => 'bi bi-cash-stack',
                'value' => $this->money($terms->max_bonus),
                'label' => 'max bonus',
            ];
        }

        if ($terms->max_cashout !== null) {
            $chips[] = [
                'icon' => 'bi bi-box-arrow-up',
                'value' => $this->money($terms->max_cashout),
                'label' => 'max cashout',
            ];
        }

        if ($terms->valid_days !== null) {
            $chips[] = [
                'icon' => 'bi bi-hourglass-split',
                'value' => (string) $terms->valid_days,
                'label' => (int) $terms->valid_days === 1 ? 'day' : 'days',
            ];
        }

        return $chips;
    }

    /**
     * Same facts as the chips, labelled for the detail page's tiles.
     *
     * @return list<array{icon: string, label: string, value: string}>
     */
    public function termTiles(): array
    {
        $labels = [
            'wagering' => 'Wagering',
            'min deposit' => 'Min deposit',
            'max bonus' => 'Max bonus',
            'max cashout' => 'Max cashout',
            'days' => 'Valid for',
            'day' => 'Valid for',
        ];

        return array_map(
            static fn (array $chip): array => [
                'icon' => $chip['icon'],
                'label' => $labels[$chip['label']] ?? ucfirst($chip['label']),
                'value' => $chip['label'] === 'days' || $chip['label'] === 'day'
                    ? $chip['value'] . ' ' . $chip['label']
                    : $chip['value'],
            ],
            $this->termChips(),
        );
    }

    /**
     * Sidebar summary on the detail page.
     *
     * @return list<array{label: string, value: string}>
     */
    public function facts(): array
    {
        return [
            ['label' => 'Offer type', 'value' => $this->typeLabel()],
            ['label' => 'Casino', 'value' => $this->casino()->name],
            [
                'label' => 'Expires',
                'value' => $this->offer->expires_at === null
                    ? 'Never'
                    : Yii::$app->formatter->asDate($this->offer->expires_at, 'php:d M Y'),
            ],
        ];
    }

    private function money(string $value): string
    {
        return self::CURRENCY . Yii::$app->formatter->asDecimal($value, $this->decimalsFor($value));
    }

    private function amountDecimals(): int
    {
        return $this->offer->type === OfferType::FreeSpins->value
            ? 0
            : $this->decimalsFor($this->offer->amount);
    }

    /**
     * Whole amounts read better without ",00" behind them.
     */
    private function decimalsFor(string $value): int
    {
        return (float) $value === floor((float) $value) ? 0 : 2;
    }

    private function trimDecimals(string $value): string
    {
        return rtrim(rtrim($value, '0'), '.');
    }
}
