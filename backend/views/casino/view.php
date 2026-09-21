<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Casino $model */

use backend\components\GridHelper;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Casinos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// One aggregate query; the offers themselves are never hydrated here.
$offerCount = (int) $model->getOffers()->count();
?>
<div class="casino-view">
    <?= $this->render('//layouts/_page-header', [
        'title' => $this->title,
        'subtitle' => $offerCount === 1 ? '1 offer' : "$offerCount offers",
        'actions' => Html::a('<i class="bi bi-arrow-left me-1"></i>Back', ['index'], [
            'class' => 'btn btn-outline-secondary',
        ])
            . Html::a('<i class="bi bi-pencil me-1"></i>Update', ['update', 'id' => $model->id], [
                'class' => 'btn btn-primary',
            ])
            . Html::a('<i class="bi bi-trash me-1"></i>Delete', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-outline-danger',
                'data-confirm' => $offerCount > 0
                    ? "Delete this casino and its {$offerCount} offer(s)?"
                    : 'Delete this casino?',
                'data-method' => 'post',
            ]),
    ]) ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-transparent fw-semibold">Casino</div>
                <?= DetailView::widget([
                    'model' => $model,
                    'options' => ['class' => 'table table-striped detail-view mb-0'],
                    'attributes' => [
                        'id',
                        ['attribute' => 'name', 'captionOptions' => ['style' => 'width:12rem']],
                        [
                            'attribute' => 'slug',
                            'contentOptions' => ['class' => 'font-monospace small'],
                        ],
                        [
                            'attribute' => 'rating',
                            'format' => 'raw',
                            'value' => '<i class="bi bi-star-fill text-warning me-1"></i>'
                                . Html::encode(Yii::$app->formatter->asDecimal($model->rating, 1)),
                        ],
                        [
                            'attribute' => 'is_active',
                            'format' => 'raw',
                            'value' => $model->is_active
                                ? GridHelper::badge('Active', 'success')
                                : GridHelper::badge('Inactive', 'secondary'),
                        ],
                        ['attribute' => 'created_at', 'format' => ['datetime', 'php:Y-m-d H:i']],
                        ['attribute' => 'updated_at', 'format' => ['datetime', 'php:Y-m-d H:i']],
                        [
                            'label' => 'Offers',
                            'format' => 'raw',
                            'value' => $offerCount === 0
                                ? GridHelper::blank('None yet')
                                : Html::a(
                                    $offerCount . ' offer' . ($offerCount === 1 ? '' : 's'),
                                    ['/offer/index', 'OfferSearch' => ['casino_id' => $model->id]],
                                    ['class' => 'text-decoration-none'],
                                ),
                        ],
                    ],
                ]) ?>
            </div>
        </div>
    </div>
</div>
