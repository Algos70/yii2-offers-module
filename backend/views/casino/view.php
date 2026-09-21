<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Casino $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Casinos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// One aggregate query; the offers themselves are never hydrated here.
$offerCount = (int) $model->getOffers()->count();
?>
<div class="casino-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data-confirm' => $offerCount > 0
                ? "Delete this casino and its {$offerCount} offer(s)?"
                : 'Delete this casino?',
            'data-method' => 'post',
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'slug',
            'rating',
            ['attribute' => 'is_active', 'format' => 'boolean'],
            ['attribute' => 'created_at', 'format' => ['datetime', 'php:Y-m-d H:i']],
            ['attribute' => 'updated_at', 'format' => ['datetime', 'php:Y-m-d H:i']],
            ['label' => 'Offers', 'value' => (string) $offerCount],
        ],
    ]) ?>
</div>
