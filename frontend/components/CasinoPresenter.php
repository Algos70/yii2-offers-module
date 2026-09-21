<?php

declare(strict_types=1);

namespace frontend\components;

use common\models\Casino;
use Yii;

/**
 * View-model for a casino wherever it appears: on an offer card, on the offer
 * detail page and on the casino's own page.
 *
 * The rating lives here rather than on {@see OfferPresenter} because it is a
 * property of the casino, and the casino page shows it without an offer in
 * sight.
 */
final class CasinoPresenter
{
    public function __construct(private readonly Casino $casino)
    {
    }

    public function casino(): Casino
    {
        return $this->casino;
    }

    public function name(): string
    {
        return $this->casino->name;
    }

    public function url(): array
    {
        return ['/casino/view', 'slug' => $this->casino->slug];
    }

    /**
     * `casino.rating` defaults to 0.0, so zero means "unrated" far more often
     * than it means "terrible": those casinos show no stars at all.
     */
    public function isRated(): bool
    {
        return (float) $this->casino->rating > 0;
    }

    public function ratingNumber(): string
    {
        return Yii::$app->formatter->asDecimal($this->casino->rating, 1);
    }

    /**
     * Width of the clipped star row, as a percentage of five stars. The column
     * is decimal(2,1), so 4.6 has to read as 4.6 rather than round to a half.
     */
    public function ratingFillPercent(): string
    {
        $percent = ((float) $this->casino->rating / 5) * 100;

        return rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . '%';
    }

    public function ratingTitle(): string
    {
        return $this->isRated()
            ? 'Rated ' . $this->ratingNumber() . ' out of 5'
            : 'This casino has not been rated yet';
    }
}
