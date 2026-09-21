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
 * The public casino pages: /casinos and /casino/<slug>.
 *
 * Only active casinos are listed and only they have a page. An inactive casino
 * and a slug that never existed answer identically, for the same reason the
 * offer pages do: a distinguishable 404 lets anyone enumerate what is not
 * published.
 */
class CasinoController extends Controller
{
    /**
     * Casinos per page. The same 20 the offer lists use.
     */
    private const PAGE_SIZE = OfferFilter::PAGE_SIZE;

    /**
     * Casinos worth visiting: the active ones, newest listing first by name,
     * each with the number of offers it currently publishes.
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Casino::find()
                ->where(['is_active' => true])
                ->orderBy(['name' => SORT_ASC]),
            'pagination' => ['pageSize' => self::PAGE_SIZE, 'params' => Yii::$app->request->queryParams],
            'sort' => false,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'offerCounts' => $this->visibleOfferCounts(),
        ]);
    }

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

    /**
     * Live offer count per casino, in one grouped query.
     *
     * A count per rendered casino would be an N+1 in disguise, and the listing
     * shows the number on every card.
     *
     * @return array<int, int> casino id => offers currently published
     */
    private function visibleOfferCounts(): array
    {
        $rows = Offer::find()
            ->visibleNow()
            ->select(['offer.casino_id', 'total' => 'COUNT(*)'])
            ->groupBy('offer.casino_id')
            ->asArray()
            ->all();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row['casino_id']] = (int) $row['total'];
        }

        return $counts;
    }
}
