<?php

declare(strict_types=1);

/**
 * Fractional star rating.
 *
 * Two stacked star rows with the filled row clipped to `rating / 5`, because
 * `casino.rating` is decimal(2,1) and a half-star icon would round 4.6 to 4.5.
 * A rating of 0.0 is the column default, so it reads as "Unrated" with no stars
 * rather than as five empty ones.
 *
 * @var yii\web\View $this
 * @var frontend\components\CasinoPresenter $casino
 */

use yii\helpers\Html;

$stars = str_repeat('<i class="bi bi-star-fill"></i>', 5);
?>
<?php if ($casino->isRated()): ?>
    <span class="rating__stars" aria-hidden="true"><?= $stars ?><span
        class="rating__fill"
        style="width: <?= $casino->ratingFillPercent() ?>"
    ><?= $stars ?></span></span>
    <span class="fw-bold font-display"><?= Html::encode($casino->ratingNumber()) ?></span>
<?php else: ?>
    <span class="fst-italic text-body-secondary">Unrated</span>
<?php endif ?>
