<?php

declare(strict_types=1);

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Behaviour for the offer listing's filter bar.
 *
 * Registered by `offer/index` only: no other page has the form, and the script
 * is an enhancement rather than a requirement.
 */
class OfferFilterAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'js/offer-filter.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
