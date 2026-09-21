<?php

declare(strict_types=1);

namespace common\assets;

use yii\bootstrap5\BootstrapPluginAsset;
use yii\web\AssetBundle;

/**
 * Marker styling plus the tooltip initialiser, shared by both applications.
 *
 * Registered by {@see \common\widgets\HelpTip} rather than by the layouts, so a
 * page with no explanations ships neither the CSS nor Bootstrap's JavaScript.
 */
class HelpTipAsset extends AssetBundle
{
    public $sourcePath = '@common/assets/helptip';
    public $css = [
        'css/help-tip.css',
    ];
    public $js = [
        'js/help-tip.js',
    ];
    public $depends = [
        // The only page element that needs Bootstrap's JavaScript; without it
        // the markers fall back to the browser's own tooltips.
        BootstrapPluginAsset::class,
    ];
}
