<?php

declare(strict_types=1);

/**
 * Page heading with its primary actions on the same row.
 *
 * @var yii\web\View $this
 * @var string $title
 * @var string|null $subtitle one-line description under the title, or null
 * @var string $actions pre-rendered buttons, right aligned (already escaped)
 */

use yii\helpers\Html;

?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-0"><?= Html::encode($title) ?></h1>
        <?php if ($subtitle !== null): ?>
            <p class="text-secondary mb-0 small"><?= Html::encode($subtitle) ?></p>
        <?php endif ?>
    </div>
    <?php if ($actions !== ''): ?>
        <div class="d-flex flex-wrap gap-2"><?= $actions ?></div>
    <?php endif ?>
</div>
