<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Casino;
use common\models\Offer;
use frontend\models\OfferFilter;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * The public casino page: /casino/<slug>.
 *
 * Only active casinos have one. An inactive casino and a slug that never
 * existed answer identically, for the same reason the offer pages do: a
 * distinguishable 404 lets anyone enumerate what is not published.
 */
class CasinoController extends Controller
{
    public function actionView(string $slug): string
    {
        $casino = Casino::find()
            ->where(['slug' => $slug, 'is_active' => true])
            ->one();

        if (!$casino instanceof Casino) {
            throw new NotFoundHttpException('This casino is not available.');
        }

        // Same visibility rule as every other public list, narrowed to this
        // casino, so the page can never link to an offer that 404s.
        $dataProvider = new ActiveDataProvider([
            'query' => Offer::find()
                ->publiclyVisible()
                ->withTerms()
                ->andWhere(['offer.casino_id' => $casino->id])
                ->orderBy(['offer.created_at' => SORT_DESC, 'offer.id' => SORT_DESC]),
            'pagination' => [
                'pageSize' => OfferFilter::PAGE_SIZE,
                'params' => Yii::$app->request->queryParams,
            ],
            // One meaningful order, and no user input reaches ORDER BY.
            'sort' => false,
        ]);

        return $this->render('view', [
            'casino' => $casino,
            'dataProvider' => $dataProvider,
        ]);
    }
}
