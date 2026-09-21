<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Offer $offer */

use backend\components\GridHelper;
use common\enums\OfferStatus;
use common\enums\OfferType;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $offer->title;
$this->params['breadcrumbs'][] = ['label' => 'Offers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$terms = $offer->terms;

$typeVariant = [
    OfferType::Welcome->value => 'primary',
    OfferType::NoDeposit->value => 'info',
    OfferType::FreeSpins->value => 'warning',
][$offer->type] ?? 'secondary';

$statusVariant = [
    OfferStatus::Active->value => 'success',
    OfferStatus::Draft->value => 'secondary',
    OfferStatus::Expired->value => 'danger',
][$offer->status] ?? 'secondary';

if ($offer->expires_at === null) {
    $expiry = GridHelper::blank('Never expires');
} else {
    $formatted = Html::encode(Yii::$app->formatter->asDatetime($offer->expires_at, 'php:Y-m-d H:i'));
    $expiry = $offer->isExpired()
        ? '<span class="text-danger"><i class="bi bi-clock-history me-1"></i>' . $formatted . '</span>'
        : $formatted;
}
?>
<div class="offer-view">
    <?= $this->render('//layouts/_page-header', [
        'title' => $this->title,
        'subtitle' => ($offer->casino->name ?? '') . ' · ' . OfferType::labelFor($offer->type),
        'actions' => Html::a('<i class="bi bi-arrow-left me-1"></i>Back', ['index'], [
            'class' => 'btn btn-outline-secondary',
        ])
            . Html::a('<i class="bi bi-pencil me-1"></i>Update', ['update', 'id' => $offer->id], [
                'class' => 'btn btn-primary',
            ])
            . Html::a('<i class="bi bi-trash me-1"></i>Delete', ['delete', 'id' => $offer->id], [
                'class' => 'btn btn-outline-danger',
                'data-confirm' => 'Delete this offer?',
                'data-method' => 'post',
            ]),
    ]) ?>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-header bg-transparent fw-semibold">Offer</div>
                <?= DetailView::widget([
                    'model' => $offer,
                    'options' => ['class' => 'table table-striped detail-view mb-0'],
                    'attributes' => [
                        'id',
                        [
                            'attribute' => 'casino_id',
                            'label' => 'Casino',
                            'format' => 'raw',
                            'value' => Html::a(
                                Html::encode($offer->casino->name),
                                ['/casino/view', 'id' => $offer->casino_id],
                                ['class' => 'text-decoration-none'],
                            ),
                        ],
                        'title',
                        [
                            'attribute' => 'slug',
                            'captionOptions' => ['style' => 'width:12rem'],
                            'contentOptions' => ['class' => 'font-monospace small'],
                        ],
                        [
                            'attribute' => 'type',
                            'format' => 'raw',
                            'value' => GridHelper::badge(OfferType::labelFor($offer->type), $typeVariant),
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'value' => GridHelper::badge(OfferStatus::labelFor($offer->status), $statusVariant),
                        ],
                        ['attribute' => 'amount', 'format' => ['decimal', 2]],
                        [
                            'attribute' => 'expires_at',
                            'format' => 'raw',
                            // A missing date means "never expires", which is a
                            // deliberate state rather than a gap in the data.
                            'value' => $expiry,
                        ],
                        ['attribute' => 'created_at', 'format' => ['datetime', 'php:Y-m-d H:i']],
                        ['attribute' => 'updated_at', 'format' => ['datetime', 'php:Y-m-d H:i']],
                    ],
                ]) ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm overflow-hidden h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Terms</span>
                    <?= Html::a('Edit terms', ['update', 'id' => $offer->id], [
                        'class' => 'btn btn-sm btn-outline-secondary',
                    ]) ?>
                </div>
                <?php if ($terms === null): ?>
                    <div class="card-body text-secondary">
                        No terms recorded for this offer.
                    </div>
                <?php else: ?>
                    <?= DetailView::widget([
                        'model' => $terms,
                        'options' => ['class' => 'table table-striped detail-view mb-0'],
                        'attributes' => [
                            [
                                'attribute' => 'wagering_multiplier',
                                'captionOptions' => ['style' => 'width:11rem'],
                            ],
                            ['attribute' => 'min_deposit', 'format' => ['decimal', 2]],
                            ['attribute' => 'max_bonus', 'format' => ['decimal', 2]],
                            ['attribute' => 'max_cashout', 'format' => ['decimal', 2]],
                            ['attribute' => 'valid_days', 'format' => 'integer'],
                            // `url` format renders an anchor; the attribute passed
                            // the `url` validator on input, and the formatter
                            // prefixes unknown schemes, so no javascript: href.
                            ['attribute' => 'terms_url', 'format' => 'url'],
                            [
                                'attribute' => 'terms_note',
                                // Encode first, then add the line breaks: the other
                                // order would escape the <br> tags just produced.
                                'format' => 'raw',
                                'value' => $terms->terms_note === null
                                    ? null
                                    : nl2br(Html::encode($terms->terms_note)),
                            ],
                        ],
                    ]) ?>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
