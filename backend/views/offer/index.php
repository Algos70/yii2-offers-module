<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\enums\OfferStatus;
use common\enums\OfferType;
use common\models\Offer;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Offers';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="offer-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create offer', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'title',
            [
                'attribute' => 'casino_id',
                'label' => 'Casino',
                // Eager-loaded by the query, so this costs no extra round trip.
                'value' => static fn (Offer $offer): ?string => $offer->casino->name ?? null,
            ],
            [
                'attribute' => 'type',
                'value' => static fn (Offer $offer): string => OfferType::labelFor($offer->type),
            ],
            [
                'attribute' => 'status',
                'value' => static fn (Offer $offer): string => OfferStatus::labelFor($offer->status),
            ],
            [
                'attribute' => 'amount',
                'format' => ['decimal', 2],
            ],
            [
                'label' => 'Wagering',
                'value' => static fn (Offer $offer): ?string => $offer->terms?->wagering_multiplier === null
                    ? null
                    : rtrim(rtrim($offer->terms->wagering_multiplier, '0'), '.') . 'x',
            ],
            [
                'attribute' => 'expires_at',
                'format' => ['datetime', 'php:Y-m-d H:i'],
            ],
            [
                'class' => ActionColumn::class,
                'urlCreator' => static fn (string $action, Offer $model): string
                    => Url::to([$action, 'id' => $model->id]),
                'buttons' => [
                    'delete' => static fn (string $url, Offer $model): string => Html::a(
                        '<span class="bi bi-trash">delete</span>',
                        $url,
                        [
                            'title' => 'Delete',
                            'data-confirm' => 'Delete "' . $model->title . '"?',
                            'data-method' => 'post',
                        ],
                    ),
                ],
            ],
        ],
    ]) ?>
</div>
