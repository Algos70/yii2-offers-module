<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Offer;
use frontend\components\OfferPresenter;
use frontend\models\OfferFilter;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * The public offers pages: /offers and /offer/<slug>.
 *
 * Both actions see the same slice of the catalogue — active, non-expired offers
 * belonging to active casinos — so an offer never appears on the listing but
 * 404s on its own page, or the other way round.
 */
class OfferController extends Controller
{
    public function actionIndex(): string
    {
        $filter = new OfferFilter();
        $dataProvider = $filter->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'filter' => $filter,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(string $slug): string
    {
        $offer = Offer::find()
            ->publiclyVisible()
            ->withTerms()
            ->andWhere(['offer.slug' => $slug])
            ->one();

        if (!$offer instanceof Offer) {
            // A draft offer, an expired one and a slug that never existed must
            // all answer identically: anything else turns 404s into a way to
            // enumerate unpublished offers.
            throw new NotFoundHttpException('This offer is not available.');
        }

        return $this->render('view', [
            'presenter' => new OfferPresenter($offer),
        ]);
    }
}
