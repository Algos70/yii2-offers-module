<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;
use yii\web\HttpException;

$this->title = $name;
$statusCode = $exception instanceof HttpException ? $exception->statusCode : 500;
?>
<?php if ($statusCode === 404): ?>
    <?php // A draft offer, an expired one and a slug that never existed all land
          // here with the same words on purpose: naming the reason would confirm
          // that a slug exists, turning 404s into a way to enumerate unpublished
          // offers. ?>
    <div class="site-error py-5">
        <div class="mx-auto text-center" style="max-width: 34rem">
            <p
                class="font-display fw-bold text-body-secondary mb-2"
                style="font-size: clamp(3rem, 8vw, 5rem); line-height: 1; letter-spacing: -0.04em"
            >404</p>
            <h1 class="h4 fw-semibold mb-3">This page isn&rsquo;t available</h1>
            <p class="text-body-secondary mb-4">
                It may have expired, been withdrawn, or never existed. Browse the offers that are
                live right now instead.
            </p>
            <?= Html::a(
                'Back to all offers<i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>',
                ['/offer/index'],
                ['class' => 'btn btn-offer'],
            ) ?>
        </div>
    </div>
<?php else: ?>
    <div class="site-error d-flex align-items-center justify-content-center text-center">
        <div class="site-error-content mx-auto">
            <h1 class="display-1 fw-bold text-body-secondary mb-0"><?= Html::encode((string) $statusCode) ?></h1>

            <h2 class="display-6 fw-semibold mb-3"><?= Html::encode($message) ?></h2>

            <p class="text-body-secondary mb-4">
                The above error occurred while the Web server was processing your request.
                Please contact us if you think this is a server error. Thank you.
            </p>

            <?= Html::a(
                'Go to Homepage',
                Yii::$app->homeUrl,
                ['class' => 'btn btn-outline-primary btn-lg'],
            ) ?>
        </div>
    </div>
<?php endif ?>
