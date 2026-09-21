<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Casino $model */
/** @var yii\bootstrap5\ActiveForm $form */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

?>
<div class="casino-form">
    <?php $form = ActiveForm::begin() ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => 120]) ?>

    <?= $form->field($model, 'slug')
        ->textInput(['maxlength' => 140])
        ->hint('Leave empty to generate it from the name.') ?>

    <?= $form->field($model, 'rating')->input('number', ['step' => '0.1', 'min' => 0, 'max' => 5]) ?>

    <?= $form->field($model, 'is_active')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end() ?>
</div>
