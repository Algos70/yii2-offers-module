<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\OfferSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use backend\components\GridHelper;
use common\enums\OfferStatus;
use common\enums\OfferType;
use common\models\Casino;
use common\models\Offer;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Offers';
$this->params['breadcrumbs'][] = $this->title;

$statusVariants = [
    OfferStatus::Active->value => 'success',
    OfferStatus::Draft->value => 'secondary',
    OfferStatus::Expired->value => 'danger',
];

$typeVariants = [
    OfferType::Welcome->value => 'primary',
    OfferType::NoDeposit->value => 'info',
    OfferType::FreeSpins->value => 'warning',
];
?>
<div class="offer-index">
    <?= $this->render('//layouts/_page-header', [
        'title' => $this->title,
        'subtitle' => 'Bonus offers, their terms and publication state.',
        'actions' => Html::a(
            '<i class="bi bi-plus-lg me-1"></i>Create offer',
            ['create'],
            ['class' => 'btn btn-success'],
        ),
    ]) ?>

    <?= GridView::widget(GridHelper::options('No offers match these filters.') + [
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'attribute' => 'id',
                'headerOptions' => ['style' => 'width:5rem'],
                'filterInputOptions' => ['class' => 'form-control form-control-sm', 'placeholder' => '#'],
            ],
            [
                'attribute' => 'title',
                'format' => 'raw',
                'value' => static fn (Offer $offer): string => Html::a(
                    Html::encode($offer->title),
                    ['view', 'id' => $offer->id],
                    ['class' => 'fw-semibold text-decoration-none'],
                ),
                'headerOptions' => ['style' => 'min-width:17rem'],
                'filterInputOptions' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Search title'],
            ],
            [
                'attribute' => 'casino_id',
                'label' => 'Casino',
                // Eager-loaded by OfferSearch, so this costs no extra query.
                'value' => static fn (Offer $offer): ?string => $offer->casino->name ?? null,
                'contentOptions' => ['class' => 'text-nowrap'],
                'filter' => Casino::find()
                    ->select(['name', 'id'])
                    ->orderBy(['name' => SORT_ASC])
                    ->indexBy('id')
                    ->column(),
                'filterInputOptions' => ['class' => 'form-select form-select-sm', 'prompt' => 'Any casino'],
            ],
            [
                'attribute' => 'type',
                'format' => 'raw',
                'headerOptions' => ['style' => 'width:9rem'],
                'value' => static fn (Offer $offer): string => GridHelper::badge(
                    OfferType::labelFor($offer->type),
                    $typeVariants[$offer->type] ?? 'secondary',
                ),
                'filter' => OfferType::labels(),
                'filterInputOptions' => ['class' => 'form-select form-select-sm', 'prompt' => 'Any type'],
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'headerOptions' => ['style' => 'width:8rem'],
                'value' => static fn (Offer $offer): string => GridHelper::badge(
                    OfferStatus::labelFor($offer->status),
                    $statusVariants[$offer->status] ?? 'secondary',
                ),
                'filter' => OfferStatus::labels(),
                'filterInputOptions' => ['class' => 'form-select form-select-sm', 'prompt' => 'Any status'],
            ],
            [
                'attribute' => 'amount',
                'format' => ['decimal', 2],
                'headerOptions' => ['class' => 'text-end', 'style' => 'width:7rem'],
                'contentOptions' => ['class' => 'text-end text-nowrap'],
                'filter' => false,
            ],
            [
                'attribute' => 'maxWagering',
                'label' => 'Wagering',
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-end', 'style' => 'width:8rem'],
                'contentOptions' => ['class' => 'text-end text-nowrap'],
                'value' => static fn (Offer $offer): string => $offer->terms?->wagering_multiplier === null
                    ? GridHelper::blank()
                    : Html::encode(rtrim(rtrim($offer->terms->wagering_multiplier, '0'), '.')) . 'x',
                'filterInputOptions' => ['class' => 'form-control form-control-sm', 'placeholder' => '≤ 35'],
            ],
            [
                'attribute' => 'expires_at',
                'format' => 'raw',
                'headerOptions' => ['style' => 'width:11rem'],
                'contentOptions' => ['class' => 'text-nowrap small'],
                'value' => static function (Offer $offer): string {
                    if ($offer->expires_at === null) {
                        return GridHelper::blank('Never expires');
                    }

                    $formatted = Yii::$app->formatter->asDatetime($offer->expires_at, 'php:Y-m-d H:i');

                    // A date already in the past is the reason an offer stops
                    // being public, so it is worth spotting at a glance.
                    return $offer->isExpired()
                        ? '<span class="text-danger"><i class="bi bi-clock-history me-1"></i>'
                            . Html::encode($formatted) . '</span>'
                        : Html::encode($formatted);
                },
                'filter' => false,
            ],
            GridHelper::actionColumn(
                static fn (Offer $offer): string => 'Delete "' . $offer->title . '" and its terms?',
            ),
        ],
    ]) ?>
</div>
