<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Casino $model */

use yii\helpers\Html;

$this->title = 'Create casino';
$this->params['breadcrumbs'][] = ['label' => 'Casinos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="casino-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', ['model' => $model]) ?>
</div>
