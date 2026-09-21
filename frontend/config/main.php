<?php

declare(strict_types=1);

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php',
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'user' => [
            'identityClass' => \common\models\User::class,
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // The two public routes of the offers module. The slug pattern
                // matches what SluggableBehavior produces, so a request with
                // anything else in it never reaches the controller.
                // Canonical home, so generated links (and the sitemap) say "/"
                // rather than "/site/index".
                '' => 'site/index',
                'offers' => 'offer/index',
                'casinos' => 'casino/index',
                'offer/<slug:[a-z0-9]+(?:-[a-z0-9]+)*>' => 'offer/view',
                'casino/<slug:[a-z0-9]+(?:-[a-z0-9]+)*>' => 'casino/view',
                'sitemap.xml' => 'sitemap/index',
            ],
        ],
    ],
    'params' => $params,
];
