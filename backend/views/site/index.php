<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\components\Dashboard $dashboard */

use backend\components\Dashboard;
use backend\components\GridHelper;
use common\enums\OfferStatus;
use common\enums\OfferType;
use common\models\Offer;
use yii\helpers\Html;

$this->title = 'Dashboard';

$username = Yii::$app->user->identity?->username;
$byStatus = $dashboard->offersByStatus();
$lapsed = $dashboard->lapsedOfferCount();

/**
 * @var list<array{label: string, value: int, note: string, icon: string, accent: string, url: array<mixed>}> $cards
 */
$cards = [
    [
        'label' => 'Casinos',
        'value' => $dashboard->casinoCount(),
        'note' => $dashboard->activeCasinoCount() . ' active',
        'icon' => 'bi-building',
        'accent' => 'primary',
        'url' => ['/casino/index'],
    ],
    [
        'label' => 'Offers',
        'value' => $dashboard->offerCount(),
        'note' => $byStatus[OfferStatus::Draft->value] . ' still draft',
        'icon' => 'bi-tags',
        'accent' => 'info',
        'url' => ['/offer/index'],
    ],
    [
        'label' => 'Publicly visible',
        'value' => $dashboard->visibleOfferCount(),
        'note' => 'active and not expired',
        'icon' => 'bi-broadcast',
        'accent' => 'success',
        'url' => ['/offer/index', 'OfferSearch' => ['status' => OfferStatus::Active->value]],
    ],
    [
        'label' => 'Expiring soon',
        'value' => $dashboard->expiringSoonCount(),
        'note' => 'within ' . Dashboard::EXPIRING_DAYS . ' days',
        'icon' => 'bi-hourglass-split',
        'accent' => 'warning',
        'url' => ['/offer/index', 'OfferSearch' => ['status' => OfferStatus::Active->value]],
    ],
];
?>
<div class="site-index">
    <?= $this->render('//layouts/_page-header', [
        'title' => 'Welcome back, ' . ($username ?? ''),
        'subtitle' => 'Casinos and their bonus offers at a glance.',
        'actions' => Html::a('<i class="bi bi-building-add me-1"></i>New casino', ['/casino/create'], [
            'class' => 'btn btn-outline-secondary',
        ])
            . Html::a('<i class="bi bi-plus-lg me-1"></i>New offer', ['/offer/create'], [
                'class' => 'btn btn-success',
            ]),
    ]) ?>

    <?php if ($lapsed > 0): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <?= $lapsed === 1
                    ? '1 offer is still marked active although its expiry has passed'
                    : Html::encode((string) $lapsed) . ' offers are still marked active although their expiry has passed' ?>
                — they no longer show publicly.
                <?= Html::a('Review them', ['/offer/index', 'OfferSearch' => [
                    'status' => OfferStatus::Active->value,
                ]], ['class' => 'alert-link']) ?>
            </div>
        </div>
    <?php endif ?>

    <div class="row g-3 mb-4">
        <?php foreach ($cards as $card): ?>
            <div class="col-sm-6 col-xl-3">
                <?= Html::a(
                    '<div class="card-body d-flex align-items-center gap-3">'
                        . '<span class="dashboard-stat-icon text-bg-' . $card['accent'] . '">'
                        . '<i class="bi ' . $card['icon'] . '"></i></span>'
                        . '<div>'
                        . '<div class="fs-3 fw-semibold lh-1">' . Html::encode((string) $card['value']) . '</div>'
                        . '<div class="text-secondary small">' . Html::encode($card['label']) . '</div>'
                        . '<div class="text-secondary small opacity-75">' . Html::encode($card['note']) . '</div>'
                        . '</div></div>',
                    $card['url'],
                    ['class' => 'card border-0 shadow-sm h-100 text-decoration-none text-body dashboard-stat'],
                ) ?>
            </div>
        <?php endforeach ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Latest offers</span>
                    <?= Html::a('All offers', ['/offer/index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                </div>
                <?php $recent = $dashboard->recentOffers() ?>
                <?php if ($recent === []): ?>
                    <div class="card-body text-secondary">
                        No offers yet. <?= Html::a('Create the first one', ['/offer/create']) ?>.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                            <?php foreach ($recent as $offer): ?>
                                <tr>
                                    <td>
                                        <?= Html::a(
                                            Html::encode($offer->title),
                                            ['/offer/view', 'id' => $offer->id],
                                            ['class' => 'fw-semibold text-decoration-none'],
                                        ) ?>
                                        <div class="text-secondary small">
                                            <?= Html::encode($offer->casino->name ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <?= GridHelper::badge(
                                            OfferType::labelFor($offer->type),
                                            match ($offer->type) {
                                                OfferType::Welcome->value => 'primary',
                                                OfferType::NoDeposit->value => 'info',
                                                OfferType::FreeSpins->value => 'warning',
                                                default => 'secondary',
                                            },
                                        ) ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?= GridHelper::badge(
                                            OfferStatus::labelFor($offer->status),
                                            match ($offer->status) {
                                                OfferStatus::Active->value => 'success',
                                                OfferStatus::Expired->value => 'danger',
                                                default => 'secondary',
                                            },
                                        ) ?>
                                    </td>
                                    <td class="text-end text-nowrap text-secondary small">
                                        <?= $offer->expires_at === null
                                            ? 'Never expires'
                                            : Html::encode(Yii::$app->formatter->asDatetime(
                                                $offer->expires_at,
                                                'php:Y-m-d',
                                            )) ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-header bg-transparent fw-semibold">Offers by status</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($byStatus as $status => $total): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                            <?= Html::a(
                                GridHelper::badge(
                                    OfferStatus::labelFor((string) $status),
                                    match ((string) $status) {
                                        OfferStatus::Active->value => 'success',
                                        OfferStatus::Expired->value => 'danger',
                                        default => 'secondary',
                                    },
                                ),
                                ['/offer/index', 'OfferSearch' => ['status' => $status]],
                                ['class' => 'text-decoration-none'],
                            ) ?>
                            <span class="fw-semibold"><?= Html::encode((string) $total) ?></span>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
    </div>
</div>
