<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var backend\models\CasinoSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\Casino;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Casinos';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="casino-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create casino', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            'name',
            'slug',
            'rating',
            [
                'attribute' => 'is_active',
                'format' => 'boolean',
                'filter' => [1 => 'Yes', 0 => 'No'],
            ],
            [
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:Y-m-d H:i'],
                'filter' => false,
            ],
            [
                'class' => ActionColumn::class,
                'urlCreator' => static fn (string $action, Casino $model): string
                    => Url::to([$action, 'id' => $model->id]),
                'buttons' => [
                    'delete' => static fn (string $url, Casino $model): string => Html::a(
                        '<span class="bi bi-trash">delete</span>',
                        $url,
                        [
                            'title' => 'Delete',
                            'data-confirm' => 'Delete "' . $model->name . '" and all of its offers?',
                            'data-method' => 'post',
                        ],
                    ),
                ],
            ],
        ],
    ]) ?>
</div>
