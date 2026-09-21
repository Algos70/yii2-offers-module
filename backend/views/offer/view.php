<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Offer $offer */

use common\enums\OfferStatus;
use common\enums\OfferType;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $offer->title;
$this->params['breadcrumbs'][] = ['label' => 'Offers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$terms = $offer->terms;
?>
<div class="offer-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $offer->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $offer->id], [
            'class' => 'btn btn-danger',
            'data-confirm' => 'Delete this offer?',
            'data-method' => 'post',
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $offer,
        'attributes' => [
            'id',
            [
                'attribute' => 'casino_id',
                'label' => 'Casino',
                'value' => $offer->casino->name ?? null,
            ],
            'title',
            'slug',
            [
                'attribute' => 'type',
                'value' => OfferType::labelFor($offer->type),
            ],
            [
                'attribute' => 'status',
                'value' => OfferStatus::labelFor($offer->status),
            ],
            ['attribute' => 'amount', 'format' => ['decimal', 2]],
            [
                'attribute' => 'expires_at',
                'format' => ['datetime', 'php:Y-m-d H:i'],
                'value' => $offer->expires_at,
            ],
            ['attribute' => 'created_at', 'format' => ['datetime', 'php:Y-m-d H:i']],
            ['attribute' => 'updated_at', 'format' => ['datetime', 'php:Y-m-d H:i']],
        ],
    ]) ?>

    <h2 class="h5 mt-4">Terms</h2>

    <?php if ($terms === null): ?>
        <p class="text-muted">No terms recorded for this offer.</p>
    <?php else: ?>
        <?= DetailView::widget([
            'model' => $terms,
            'attributes' => [
                ['attribute' => 'wagering_multiplier', 'format' => 'text'],
                ['attribute' => 'min_deposit', 'format' => ['decimal', 2]],
                ['attribute' => 'max_bonus', 'format' => ['decimal', 2]],
                ['attribute' => 'max_cashout', 'format' => ['decimal', 2]],
                ['attribute' => 'valid_days', 'format' => 'integer'],
                // `url` format renders an anchor; the attribute already passed
                // the `url` validator on input, so no javascript: can reach href.
                ['attribute' => 'terms_url', 'format' => 'url'],
                [
                    'attribute' => 'terms_note',
                    // Encode first, then add the line breaks: the other order
                    // would escape the <br> tags this just produced.
                    'format' => 'raw',
                    'value' => $terms->terms_note === null
                        ? null
                        : nl2br(Html::encode($terms->terms_note)),
                ],
            ],
        ]) ?>
    <?php endif ?>
</div>
