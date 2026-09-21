<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Offer $offer */
/** @var common\models\OfferTerms $terms */

use common\enums\OfferStatus;
use common\enums\OfferType;
use common\models\Casino;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

// One query, no model hydration: id => name for the dropdown.
$casinos = Casino::find()
    ->select(['name', 'id'])
    ->orderBy(['name' => SORT_ASC])
    ->indexBy('id')
    ->column();
?>
<div class="offer-form">
    <?php $form = ActiveForm::begin() ?>

    <?= $form->field($offer, 'casino_id')->dropDownList($casinos, ['prompt' => 'Select a casino']) ?>

    <?= $form->field($offer, 'title')->textInput(['maxlength' => 160]) ?>

    <?= $form->field($offer, 'slug')
        ->textInput(['maxlength' => 180])
        ->hint('Leave empty to generate it from the title.') ?>

    <?= $form->field($offer, 'type')->dropDownList(OfferType::labels()) ?>

    <?= $form->field($offer, 'amount')
        ->input('number', ['step' => '0.01', 'min' => 0])
        ->hint('Currency amount, or the number of spins for a free-spins offer.') ?>

    <?= $form->field($offer, 'status')->dropDownList(OfferStatus::labels()) ?>

    <?= $form->field($offer, 'expires_at')
        ->textInput(['placeholder' => 'YYYY-MM-DD HH:MM:SS'])
        ->hint('Leave empty if the offer never expires.') ?>

    <fieldset class="border rounded p-3 mb-3">
        <legend class="float-none w-auto fs-6 px-2">Terms</legend>
        <p class="text-muted small">Leave every field empty if this offer has no terms yet.</p>

        <?= $form->field($terms, 'wagering_multiplier')
            ->input('number', ['step' => '0.1', 'min' => 0, 'max' => 200]) ?>

        <?= $form->field($terms, 'min_deposit')->input('number', ['step' => '0.01', 'min' => 0]) ?>

        <?= $form->field($terms, 'max_bonus')->input('number', ['step' => '0.01', 'min' => 0]) ?>

        <?= $form->field($terms, 'max_cashout')->input('number', ['step' => '0.01', 'min' => 0]) ?>

        <?= $form->field($terms, 'valid_days')->input('number', ['min' => 1, 'max' => 365]) ?>

        <?= $form->field($terms, 'terms_url')->input('url', ['maxlength' => 255]) ?>

        <?= $form->field($terms, 'terms_note')->textarea(['rows' => 3, 'maxlength' => 500]) ?>
    </fieldset>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end() ?>
</div>
