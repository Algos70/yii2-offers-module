<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var frontend\components\OfferPresenter $presenter */

use common\widgets\HelpTip;
use yii\helpers\Html;

$offer = $presenter->offer();
$casino = $presenter->casino();
$casinoPresenter = $presenter->casinoPresenter();
$terms = $presenter->terms();
$expiry = $presenter->expiry();
$tiles = $presenter->termTiles();

$this->title = $offer->title;

?>
<div class="offer-view <?= $presenter->typeClass() ?>">
    <?php // Written out rather than via the Breadcrumbs widget: the design needs
          // `link-secondary` on the anchors, which the widget cannot set. ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item">
                <?= Html::a('Offers', ['/offer/index'], ['class' => 'link-secondary']) ?>
            </li>
            <li class="breadcrumb-item">
                <?= Html::a(
                    Html::encode($casino->name),
                    $casinoPresenter->url(),
                    ['class' => 'link-secondary'],
                ) ?>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= Html::encode($presenter->typeLabel()) ?>
            </li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="badge offer-badge"><?= Html::encode($presenter->typeLabel()) ?></span>
                <span class="<?= $expiry['class'] ?>">
                    <i class="<?= $expiry['icon'] ?>" aria-hidden="true"></i><?= Html::encode($expiry['text']) ?>
                </span>
            </div>

            <p class="offer-amount d-flex align-items-baseline gap-1 mb-2"><?= Html::encode($presenter->amount()) ?>
                <?php if ($presenter->amountUnit() !== null): ?>
                    <span class="offer-amount__unit"><?= Html::encode($presenter->amountUnit()) ?></span>
                <?php endif ?>
            </p>

            <h1 class="h3 fw-semibold lh-base mb-3"><?= Html::encode($offer->title) ?></h1>

            <div class="d-flex flex-wrap align-items-center gap-3 pb-4 mb-4 border-bottom">
                <?= Html::a(
                    '<i class="bi bi-suit-spade-fill text-body-secondary" aria-hidden="true"></i>'
                        . Html::encode($casino->name),
                    $casinoPresenter->url(),
                    [
                        'class' => 'd-inline-flex align-items-center gap-2 link-body-emphasis '
                            . 'text-decoration-none fw-semibold',
                    ],
                ) ?>
                <span
                    class="d-inline-flex align-items-center gap-1 small"
                    title="<?= Html::encode($casinoPresenter->ratingTitle()) ?>"
                >
                    <?= $this->render('_rating', ['casino' => $casinoPresenter]) ?>
                </span>
            </div>

            <?php if ($tiles !== []): ?>
                <h2 class="h6 font-display text-uppercase text-body-secondary mb-3">Bonus terms</h2>
                <div class="row row-cols-2 row-cols-md-3 g-3 mb-4">
                    <?php foreach ($tiles as $tile): ?>
                        <div class="col">
                            <div class="h-100 p-3 border rounded-3 bg-body-tertiary">
                                <p class="small text-body-secondary mb-1 d-flex align-items-center gap-1">
                                    <i class="<?= $tile['icon'] ?>" aria-hidden="true"></i>
                                    <?= Html::encode($tile['label']) ?>
                                    <?= HelpTip::for($tile['help']) ?>
                                </p>
                                <p class="h4 font-display fw-bold mb-0"><?= Html::encode($tile['value']) ?></p>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <?php if ($terms?->terms_note !== null && $terms->terms_note !== ''): ?>
                <div class="p-3 border-start border-3 bg-body-tertiary rounded-end-3 mb-3">
                    <?php // Encode first, then add the line breaks, or nl2br's tags get escaped. ?>
                    <p class="mb-0 small"><?= nl2br(Html::encode($terms->terms_note)) ?></p>
                </div>
            <?php endif ?>

            <?php if ($terms?->terms_url !== null): ?>
                <?= Html::a(
                    'Full terms and conditions<i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>',
                    $terms->terms_url,
                    [
                        'class' => 'd-inline-flex align-items-center gap-1 link-secondary small',
                        'rel' => 'nofollow noopener',
                        'target' => '_blank',
                    ],
                ) ?>
            <?php endif ?>

            <?php if ($tiles === [] && $terms === null): ?>
                <p class="small fst-italic text-body-secondary mb-0">
                    This offer has no published bonus terms.
                </p>
            <?php endif ?>
        </div>

        <div class="col-lg-4">
            <div class="card offer-card">
                <div class="card-body">
                    <p class="small text-body-secondary mb-1">Bonus value</p>
                    <p class="offer-amount d-flex align-items-baseline gap-1 mb-3">
                        <?= Html::encode($presenter->amount()) ?>
                        <?php if ($presenter->amountUnit() !== null): ?>
                            <span class="offer-amount__unit"><?= Html::encode($presenter->amountUnit()) ?></span>
                        <?php endif ?>
                    </p>

                    <?php
                    // The design's "Claim offer" CTA has nowhere to point: neither
                    // `casino` nor `offer` carries an outbound URL, and
                    // `offer_terms.terms_url` points at terms, not at the casino.
                    // Rather than wire a button to the wrong destination, the slot
                    // holds the terms link until a column exists for it.
                    ?>
                    <?php if ($terms?->terms_url !== null): ?>
                        <?= Html::a(
                            'Read the full terms<i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>',
                            $terms->terms_url,
                            [
                                'class' => 'btn btn-offer w-100 justify-content-center mb-2',
                                'rel' => 'nofollow noopener',
                                'target' => '_blank',
                            ],
                        ) ?>
                    <?php endif ?>

                    <p class="small text-body-secondary text-center mb-0">18+ only. Terms apply.</p>
                </div>

                <ul class="list-group list-group-flush">
                    <?php foreach ($presenter->facts() as $fact): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2
                            bg-transparent small">
                            <span class="text-body-secondary"><?= Html::encode($fact['label']) ?></span>
                            <span class="fw-semibold text-end"><?= Html::encode($fact['value']) ?></span>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
    </div>
</div>
