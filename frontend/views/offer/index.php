<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var frontend\models\OfferFilter $filter */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\enums\OfferType;
use common\models\Offer;
use frontend\assets\OfferFilterAsset;
use frontend\components\OfferPresenter;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use yii\widgets\ListView;

// Enables the Apply button only once a selection differs from what is applied.
OfferFilterAsset::register($this);

$this->title = 'Offers';

// The heading counts the whole catalogue: it describes the site, so narrowing
// the filters must not make it read "0 active offers".
$total = $filter->visibleTotal();
$casinos = $filter->casinoOptions();
$typeOptions = ['' => 'All types'] + OfferType::labels();
?>
<div class="offer-index">
    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-4">
        <h1 class="display-6 font-display fw-bold text-uppercase mb-0"><?= Html::encode($this->title) ?></h1>
        <p class="small text-body-secondary mb-0">
            <?= $total === 1
                ? '1 active offer'
                : Html::encode(Yii::$app->formatter->asInteger($total)) . ' active offers' ?>
            across <?= count($casinos) === 1 ? '1 casino' : Html::encode((string) count($casinos)) . ' casinos' ?>
        </p>
    </div>

    <?= Html::beginForm(['/offer/index'], 'get', [
        'class' => 'd-flex flex-wrap align-items-center gap-2 p-3 mb-4 bg-body-tertiary border rounded-3',
        'data-offer-filter' => true,
    ]) ?>
        <div class="d-flex flex-wrap gap-1" role="group" aria-label="Filter by offer type">
            <?php foreach ($typeOptions as $value => $label): ?>
                <?php $id = 'type-' . ($value === '' ? 'all' : $value) ?>
                <?= Html::radio('type', (string) $filter->type === (string) $value, [
                    'value' => $value,
                    'id' => $id,
                    'class' => 'btn-check',
                    'label' => null,
                ]) ?>
                <label class="btn btn-filter" for="<?= $id ?>"><?= Html::encode($label) ?></label>
            <?php endforeach ?>
        </div>

        <label class="visually-hidden" for="filter-casino">Casino</label>
        <?= Html::dropDownList('casino', $filter->casino, $casinos, [
            'id' => 'filter-casino',
            'class' => 'form-select w-auto',
            'prompt' => 'All casinos',
        ]) ?>

        <?= Html::submitButton('Apply', [
            'class' => 'btn btn-offer',
            'data-offer-filter-submit' => true,
        ]) ?>

        <?php if ($filter->isFiltered()): ?>
            <?= Html::a('Reset', ['/offer/index'], ['class' => 'link-secondary small ms-auto']) ?>
        <?php endif ?>
    <?= Html::endForm() ?>

    <?php if ($dataProvider->getTotalCount() === 0): ?>
        <?php // Rendered outside ListView: its items container is the card grid
              // row, and the empty panel is full width, not a grid cell. ?>
        <?= $this->render('_empty', ['filter' => $filter]) ?>
    <?php else: ?>
        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            // Cards, not a table: the amount is what a visitor scans for.
            'options' => ['class' => 'row row-cols-1 row-cols-lg-2 g-3'],
            'itemOptions' => ['class' => 'col'],
            'itemView' => static function (Offer $offer, mixed $key, int $index, ListView $widget): string {
                return $widget->getView()->render('_card', ['presenter' => new OfferPresenter($offer)]);
            },
            'layout' => "{items}\n"
                . '<nav class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4"'
                . ' aria-label="Offer pages">{summary}{pager}</nav>',
            'summaryOptions' => ['tag' => 'p', 'class' => 'small text-body-secondary mb-0'],
            'summary' => 'Showing {begin}&ndash;{end} of {totalCount}',
            'showOnEmpty' => false,
            'pager' => [
                'class' => LinkPager::class,
                'options' => ['class' => 'pagination pagination-offer mb-0'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'linkOptions' => ['class' => 'page-link'],
                'disabledListItemSubTagOptions' => ['tag' => 'span', 'class' => 'page-link'],
                'prevPageLabel' => '<i class="bi bi-chevron-left"></i>',
                'nextPageLabel' => '<i class="bi bi-chevron-right"></i>',
                'maxButtonCount' => 5,
            ],
        ]) ?>
    <?php endif ?>
</div>
