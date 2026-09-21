<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var list<frontend\components\OfferPresenter> $presenters */
/** @var int $offerCount */
/** @var int $casinoCount */

use common\enums\OfferType;
use yii\helpers\Html;

$this->title = 'Casino bonus offers';
$this->params['meta_description'] = 'Live casino welcome bonuses, no-deposit offers and free spins, '
    . 'with their wagering requirements and expiry dates in plain sight.';

$typeLinks = [
    OfferType::Welcome->value => 'offer--welcome',
    OfferType::NoDeposit->value => 'offer--nodeposit',
    OfferType::FreeSpins->value => 'offer--freespins',
];
?>
<div class="site-index">
    <section class="py-4 py-lg-5 mb-4 border-bottom">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <p
                    class="small font-display text-uppercase text-body-secondary mb-2"
                    style="letter-spacing: 0.14em"
                >Casino bonuses, checked and dated</p>

                <h1 class="display-5 font-display fw-bold mb-3">
                    Every live offer, with the terms up front.
                </h1>

                <p class="fs-5 text-body-secondary mb-4">
                    Wagering, minimum deposit, maximum cashout and the expiry date sit on the card
                    itself &mdash; no unfolding the small print to find out what a bonus costs.
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <?= Html::a(
                        'Browse all offers<i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>',
                        ['/offer/index'],
                        ['class' => 'btn btn-offer'],
                    ) ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="row g-3 text-center">
                    <div class="col-6">
                        <div class="h-100 p-3 border rounded-3 bg-body-tertiary">
                            <p class="offer-amount mb-0"><?= Html::encode((string) $offerCount) ?></p>
                            <p class="small text-body-secondary mb-0">live offers</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="h-100 p-3 border rounded-3 bg-body-tertiary">
                            <p class="offer-amount mb-0"><?= Html::encode((string) $casinoCount) ?></p>
                            <p class="small text-body-secondary mb-0">casinos listed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <h2 class="h6 font-display text-uppercase text-body-secondary mb-3">Browse by type</h2>
        <div class="row row-cols-1 row-cols-md-3 g-3">
            <?php foreach (OfferType::labels() as $value => $label): ?>
                <div class="col">
                    <?= Html::a(
                        // .card is a flex column, so the badge needs to be told
                        // not to stretch across the whole card.
                        '<span class="badge offer-badge mb-2 align-self-start">'
                            . Html::encode($label) . '</span>'
                            . '<span class="d-flex align-items-center justify-content-between gap-2">'
                            . '<span class="fw-semibold">See ' . Html::encode(strtolower($label))
                            . ' offers</span>'
                            . '<i class="bi bi-arrow-right" aria-hidden="true"></i></span>',
                        ['/offer/index', 'type' => $value],
                        [
                            'class' => 'card offer-card h-100 card-body text-decoration-none '
                                . 'link-body-emphasis ' . $typeLinks[$value],
                        ],
                    ) ?>
                </div>
            <?php endforeach ?>
        </div>
    </section>

    <?php if ($presenters !== []): ?>
        <section>
            <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-3">
                <h2 class="h4 font-display fw-bold mb-0">Latest offers</h2>
                <?= Html::a('All offers', ['/offer/index'], ['class' => 'link-secondary small']) ?>
            </div>

            <div class="row row-cols-1 row-cols-lg-2 g-3">
                <?php foreach ($presenters as $presenter): ?>
                    <div class="col">
                        <?= $this->render('//offer/_card', ['presenter' => $presenter]) ?>
                    </div>
                <?php endforeach ?>
            </div>
        </section>
    <?php else: ?>
        <section>
            <div class="text-center py-5 px-3 bg-body-tertiary border border-dashed rounded-3">
                <i class="bi bi-hourglass-split fs-1 text-body-secondary" aria-hidden="true"></i>
                <h2 class="h5 font-display fw-bold mt-3 mb-2">No offers are live right now</h2>
                <p class="text-body-secondary mb-0">
                    Offers come and go as casinos publish and withdraw them. Check back shortly.
                </p>
            </div>
        </section>
    <?php endif ?>
</div>
