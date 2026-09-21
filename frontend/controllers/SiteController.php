<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Casino;
use common\models\Offer;
use frontend\components\OfferPresenter;
use yii\web\Controller;
use yii\web\ErrorAction;

/**
 * The public home page and the error handler.
 *
 * There is deliberately no authentication here. Accounts exist for staff only
 * and live in the backend application: the `user` table is shared between the
 * two applications and the backend authorises on `roles => ['@']`, so any
 * active user is an administrator and a public signup form would hand the
 * admin panel to anyone who filled it in.
 *
 * The offers themselves live in {@see OfferController}.
 */
class SiteController extends Controller
{
    /**
     * Offers previewed on the home page.
     */
    private const FEATURED_COUNT = 4;

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }

    /**
     * Landing page: the freshest offers plus what the catalogue holds.
     */
    public function actionIndex(): string
    {
        // Same visibility rule as /offers, so the home page can never advertise
        // an offer whose own page would 404.
        $featured = Offer::find()
            ->publiclyVisible()
            ->withTerms()
            ->orderBy(['offer.created_at' => SORT_DESC, 'offer.id' => SORT_DESC])
            ->limit(self::FEATURED_COUNT)
            ->all();

        return $this->render('index', [
            'presenters' => array_map(
                static fn (Offer $offer): OfferPresenter => new OfferPresenter($offer),
                $featured,
            ),
            'offerCount' => (int) Offer::find()->publiclyVisible()->count(),
            'casinoCount' => (int) Casino::find()->where(['is_active' => true])->count(),
        ]);
    }
}
