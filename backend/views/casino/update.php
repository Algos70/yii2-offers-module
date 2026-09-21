<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Casino $model */

use yii\helpers\Html;

$this->title = 'Update casino: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Casinos', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="casino-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', ['model' => $model]) ?>
</div>
