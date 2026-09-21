<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Casino $casino */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\Offer;
use frontend\components\CasinoPresenter;
use frontend\components\OfferPresenter;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\widgets\ListView;

$presenter = new CasinoPresenter($casino);
$total = $dataProvider->getTotalCount();

$this->title = $casino->name;
$this->params['meta_description'] = 'Live bonus offers from ' . $casino->name
    . ', with wagering requirements and expiry dates.';
?>
<div class="casino-view">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item">
                <?= Html::a('Offers', ['/offer/index'], ['class' => 'link-secondary']) ?>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= Html::encode($casino->name) ?>
            </li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-4 mb-4 border-bottom">
        <div>
            <h1 class="display-6 font-display fw-bold mb-2"><?= Html::encode($casino->name) ?></h1>
            <span
                class="d-inline-flex align-items-center gap-1"
                title="<?= Html::encode($presenter->ratingTitle()) ?>"
            >
                <?= $this->render('//offer/_rating', ['casino' => $presenter]) ?>
            </span>
        </div>

        <p class="small text-body-secondary mb-0">
            <?= $total === 1
                ? '1 live offer'
                : Html::encode((string) $total) . ' live offers' ?>
        </p>
    </div>

    <?php if ($total === 0): ?>
        <div class="text-center py-5 px-3 bg-body-tertiary border border-dashed rounded-3">
            <i class="bi bi-hourglass-split fs-1 text-body-secondary" aria-hidden="true"></i>
            <h2 class="h5 font-display fw-bold mt-3 mb-2">No live offers right now</h2>
            <p class="text-body-secondary mb-4">
                <?= Html::encode($casino->name) ?> has nothing published at the moment. Offers come
                and go as casinos publish and withdraw them.
            </p>
            <?= Html::a('Browse all offers', ['/offer/index'], ['class' => 'btn btn-offer']) ?>
        </div>
    <?php else: ?>
        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'options' => ['class' => 'row row-cols-1 row-cols-lg-2 g-3'],
            'itemOptions' => ['class' => 'col'],
            'itemView' => static function (Offer $offer, mixed $key, int $index, ListView $widget): string {
                return $widget->getView()->render('//offer/_card', ['presenter' => new OfferPresenter($offer)]);
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
