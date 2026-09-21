<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Offer $offer */
/** @var common\models\OfferTerms $terms */

use yii\helpers\Html;

$this->title = 'Create offer';
$this->params['breadcrumbs'][] = ['label' => 'Offers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="offer-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', ['offer' => $offer, 'terms' => $terms]) ?>
</div>
