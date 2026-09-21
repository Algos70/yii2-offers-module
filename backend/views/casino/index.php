<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\CasinoSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use backend\components\GridHelper;
use common\models\Casino;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Casinos';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="casino-index">
    <?= $this->render('//layouts/_page-header', [
        'title' => $this->title,
        'subtitle' => 'Operators whose offers are published by this site.',
        'actions' => Html::a(
            '<i class="bi bi-plus-lg me-1"></i>Create casino',
            ['create'],
            ['class' => 'btn btn-success'],
        ),
    ]) ?>

    <?= GridView::widget(GridHelper::options('No casinos match these filters.') + [
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'attribute' => 'id',
                'headerOptions' => ['style' => 'width:5rem'],
                'filterInputOptions' => ['class' => 'form-control form-control-sm', 'placeholder' => '#'],
            ],
            [
                'attribute' => 'name',
                // The name doubles as the link to the record, which is what a
                // reader reaches for first.
                'format' => 'raw',
                'value' => static fn (Casino $casino): string => Html::a(
                    Html::encode($casino->name),
                    ['view', 'id' => $casino->id],
                    ['class' => 'fw-semibold text-decoration-none'],
                ),
                'filterInputOptions' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Search name'],
            ],
            [
                'attribute' => 'slug',
                'contentOptions' => ['class' => 'text-secondary font-monospace small'],
                'filterInputOptions' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Search slug'],
            ],
            [
                'attribute' => 'rating',
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-end', 'style' => 'width:8rem'],
                'contentOptions' => ['class' => 'text-end text-nowrap'],
                'value' => static fn (Casino $casino): string
                    => '<i class="bi bi-star-fill text-warning me-1"></i>'
                        . Html::encode(Yii::$app->formatter->asDecimal($casino->rating, 1)),
                'filter' => false,
            ],
            [
                'attribute' => 'is_active',
                'format' => 'raw',
                'headerOptions' => ['style' => 'width:8rem'],
                'value' => static fn (Casino $casino): string => $casino->is_active
                    ? GridHelper::badge('Active', 'success')
                    : GridHelper::badge('Inactive', 'secondary'),
                'filter' => [1 => 'Active', 0 => 'Inactive'],
                'filterInputOptions' => ['class' => 'form-select form-select-sm', 'prompt' => 'Any'],
            ],
            [
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:Y-m-d H:i'],
                'contentOptions' => ['class' => 'text-secondary text-nowrap small'],
                'headerOptions' => ['style' => 'width:11rem'],
                'filter' => false,
            ],
            GridHelper::actionColumn(
                static fn (Casino $casino): string
                    => 'Delete "' . $casino->name . '" and all of its offers?',
            ),
        ],
    ]) ?>
</div>
