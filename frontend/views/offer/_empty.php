<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var frontend\models\OfferFilter $filter */

use yii\helpers\Html;

$casinoName = $filter->casinoName();
?>
<div class="text-center py-5 px-3 bg-body-tertiary border border-dashed rounded-3">
    <i class="bi bi-search fs-1 text-body-secondary" aria-hidden="true"></i>

    <h2 class="h5 font-display fw-bold mt-3 mb-2">
        <?= $filter->isFiltered() ? 'No offers match those filters' : 'No offers are live right now' ?>
    </h2>

    <p class="text-body-secondary mx-auto mb-4" style="max-width: 34rem">
        <?php if ($casinoName !== null): ?>
            <?= Html::encode($casinoName) ?> has no matching offers right now.
        <?php endif ?>
        Offers come and go as casinos publish and withdraw them.
    </p>

    <?php if ($filter->isFiltered()): ?>
        <?= Html::a('Clear filters', ['/offer/index'], ['class' => 'btn btn-offer']) ?>
    <?php endif ?>
</div>
