<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var frontend\components\CasinoPresenter $presenter */
/** @var int $offerCount */

use yii\helpers\Html;
use yii\helpers\Url;

$url = Url::to($presenter->url());
?>
<article class="card offer-card offer--neutral h-100">
    <div class="card-body">
        <h2 class="h5 font-display fw-bold mb-2">
            <?= Html::a(
                Html::encode($presenter->name()),
                $url,
                ['class' => 'stretched-link link-body-emphasis text-decoration-none'],
            ) ?>
        </h2>

        <span
            class="d-inline-flex align-items-center gap-1 small"
            title="<?= Html::encode($presenter->ratingTitle()) ?>"
        >
            <?= $this->render('//offer/_rating', ['casino' => $presenter]) ?>
        </span>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center gap-2">
        <span class="small text-body-secondary">
            <?= $offerCount === 1
                ? '1 live offer'
                : Html::encode((string) $offerCount) . ' live offers' ?>
        </span>

        <?= Html::a(
            'View offers<i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>',
            $url,
            // The card is already a link; this sits above it and out of the tab
            // order so the destination is not announced twice.
            ['class' => 'btn btn-offer position-relative z-2', 'tabindex' => -1],
        ) ?>
    </div>
</article>
