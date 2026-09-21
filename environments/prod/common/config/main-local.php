<?php

return [
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                'viewPath' => '@common/mail',
            ],
        ],
    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=yii2_offers',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
    ],
];
