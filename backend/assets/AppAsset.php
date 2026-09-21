<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace backend\assets;

use common\assets\ColorModeAsset;
use yii\bootstrap5\BootstrapAsset;
use yii\bootstrap5\BootstrapIconAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Main backend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
    ];
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        // GridView's ActionColumn renders Bootstrap 3 glyphicon markup, which
        // Bootstrap 5 dropped; the grids below replace those icons with `bi`
        // ones, which need this font bundle.
        BootstrapIconAsset::class,
        ColorModeAsset::class,
    ];
}
