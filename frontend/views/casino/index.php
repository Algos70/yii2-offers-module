<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array<int, int> $offerCounts */

use common\models\Casino;
use frontend\components\CasinoPresenter;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\widgets\ListView;

$this->title = 'Casinos';
$this->params['meta_description'] = 'The casinos whose bonus offers are listed here, with their '
    . 'ratings and how many offers each one currently publishes.';

$total = $dataProvider->getTotalCount();
?>
<div class="casino-index">
    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-4">
        <h1 class="display-6 font-display fw-bold text-uppercase mb-0"><?= Html::encode($this->title) ?></h1>
        <p class="small text-body-secondary mb-0">
            <?= $total === 1 ? '1 casino listed' : Html::encode((string) $total) . ' casinos listed' ?>
        </p>
    </div>

    <?php if ($total === 0): ?>
        <div class="text-center py-5 px-3 bg-body-tertiary border border-dashed rounded-3">
            <i class="bi bi-hourglass-split fs-1 text-body-secondary" aria-hidden="true"></i>
            <h2 class="h5 font-display fw-bold mt-3 mb-2">No casinos listed yet</h2>
            <p class="text-body-secondary mb-0">Check back shortly.</p>
        </div>
    <?php else: ?>
        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'options' => ['class' => 'row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3'],
            'itemOptions' => ['class' => 'col'],
            'itemView' => static function (
                Casino $casino,
                mixed $key,
                int $index,
                ListView $widget,
            ) use ($offerCounts): string {
                return $widget->getView()->render('_card', [
                    'presenter' => new CasinoPresenter($casino),
                    // Counted once for the whole page, not per card.
                    'offerCount' => $offerCounts[$casino->id] ?? 0,
                ]);
            },
            'layout' => "{items}\n"
                // w-100: ListView wraps its whole layout in `options`, which is the
                // card grid row, so the pager has to claim a line of its own.
                . '<nav class="w-100 d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4"'
                . ' aria-label="Casino pages">{summary}{pager}</nav>',
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
