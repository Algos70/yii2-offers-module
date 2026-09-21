<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace frontend\assets;

use common\assets\ColorModeAsset;
use yii\bootstrap5\BootstrapAsset;
use yii\bootstrap5\BootstrapIconAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Main frontend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        // Archivo is the display face (amounts, badges, buttons, titles) and
        // Public Sans the body text; css/site.css binds them to Bootstrap's own
        // font variables.
        'https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=Public+Sans:wght@400;500;600;700&display=swap',
        'css/site.css',
    ];
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        // Offer cards use `bi` icons for expiry, terms chips and star ratings.
        BootstrapIconAsset::class,
        ColorModeAsset::class,
    ];
}
