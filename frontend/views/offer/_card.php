<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var frontend\components\OfferPresenter $presenter */

use yii\helpers\Html;
use yii\helpers\Url;

$offer = $presenter->offer();
$casino = $presenter->casinoPresenter();
$expiry = $presenter->expiry();
$chips = $presenter->termChips();
$termsUrl = $presenter->terms()?->terms_url;
$url = Url::to($presenter->url());
?>
<article class="card offer-card h-100 <?= $presenter->typeClass() ?>">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <span class="badge offer-badge"><?= Html::encode($presenter->typeLabel()) ?></span>
            <span class="<?= $expiry['class'] ?>">
                <i class="<?= $expiry['icon'] ?>" aria-hidden="true"></i><?= Html::encode($expiry['text']) ?>
            </span>
        </div>

        <p class="offer-amount d-flex align-items-baseline gap-1 mb-0"><?= Html::encode($presenter->amount()) ?>
            <?php if ($presenter->amountUnit() !== null): ?>
                <span class="offer-amount__unit"><?= Html::encode($presenter->amountUnit()) ?></span>
            <?php endif ?>
        </p>

        <h2 class="h6 fw-semibold lh-base mt-3 mb-2">
            <?= Html::a(
                Html::encode($offer->title),
                $url,
                ['class' => 'stretched-link link-body-emphasis text-decoration-none'],
            ) ?>
        </h2>

        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <?php // Lifted above the card's stretched link so it stays clickable. ?>
            <?= Html::a(
                '<i class="bi bi-suit-spade-fill text-body-secondary" aria-hidden="true"></i>'
                    . Html::encode($casino->name()),
                $casino->url(),
                [
                    'class' => 'd-inline-flex align-items-center gap-1 small fw-semibold text-truncate '
                        . 'link-body-emphasis text-decoration-none position-relative z-2',
                ],
            ) ?>
            <span
                class="d-inline-flex align-items-center gap-1 small"
                title="<?= Html::encode($casino->ratingTitle()) ?>"
            >
                <?= $this->render('_rating', ['casino' => $casino]) ?>
            </span>
        </div>

        <?php if ($chips !== []): ?>
            <ul class="list-inline d-flex flex-wrap gap-1 mb-0">
                <?php foreach ($chips as $chip): ?>
                    <li class="list-inline-item me-0 d-inline-flex align-items-baseline gap-1 border rounded-pill
                        px-2 py-1 small text-body-secondary">
                        <i class="<?= $chip['icon'] ?>" aria-hidden="true"></i>
                        <span class="fw-bold text-body-emphasis"><?= Html::encode($chip['value']) ?></span>
                        <?= Html::encode($chip['label']) ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php else: ?>
            <p class="small fst-italic text-body-secondary mb-0">No bonus terms published.</p>
        <?php endif ?>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center gap-2">
        <?php if ($termsUrl !== null): ?>
            <?= Html::a('Full T&amp;Cs', $termsUrl, [
                'class' => 'link-secondary small position-relative z-2',
                'rel' => 'nofollow noopener',
                'target' => '_blank',
            ]) ?>
        <?php else: ?>
            <span class="small text-body-secondary"></span>
        <?php endif ?>

        <?= Html::a(
            'View offer<i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>',
            $url,
            // The whole card is already a link through .stretched-link, so this
            // button is lifted above it and taken out of the tab order to avoid
            // announcing the same destination twice.
            ['class' => 'btn btn-offer position-relative z-2', 'tabindex' => -1],
        ) ?>
    </div>
</article>
