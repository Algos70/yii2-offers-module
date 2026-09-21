<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Offer;
use common\models\OfferTerms;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Offer administration.
 *
 * Create and update edit two models at once — the offer and its optional bonus
 * terms — which `Model::loadMultiple()` / `validateMultiple()` handle natively,
 * while `Offer::saveWithTerms()` owns the transaction.
 */
class OfferController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Unfiltered listing; Story 2.4 replaces the provider with `OfferSearch`.
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Offer::find()->withCasino()->withTerms(),
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'attributes' => ['id', 'title', 'type', 'status', 'amount', 'expires_at', 'created_at'],
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', [
            'offer' => $this->findModel($id),
        ]);
    }

    public function actionCreate(): Response|string
    {
        $offer = new Offer();
        $terms = new OfferTerms();

        if ($this->saveFromRequest($offer, $terms)) {
            Yii::$app->session->setFlash('success', 'Offer created.');

            return $this->redirect(['view', 'id' => $offer->id]);
        }

        return $this->render('create', [
            'offer' => $offer,
            'terms' => $terms,
        ]);
    }

    public function actionUpdate(int $id): Response|string
    {
        $offer = $this->findModel($id);
        $terms = $offer->terms ?? new OfferTerms();

        if ($this->saveFromRequest($offer, $terms)) {
            Yii::$app->session->setFlash('success', 'Offer updated.');

            return $this->redirect(['view', 'id' => $offer->id]);
        }

        return $this->render('update', [
            'offer' => $offer,
            'terms' => $terms,
        ]);
    }

    public function actionDelete(int $id): Response
    {
        // The terms row goes with it through the foreign key.
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Offer deleted.');

        return $this->redirect(['index']);
    }

    /**
     * Loads both models from the request and writes them as one unit.
     *
     * Returns false when there is nothing to save yet (plain GET) or when
     * validation failed, in which case the caller re-renders the form.
     */
    private function saveFromRequest(Offer $offer, OfferTerms $terms): bool
    {
        $post = Yii::$app->request->post();

        // Model::loadMultiple() is for tabular input (Offer[0], Offer[1]); two
        // different models each get their own load(), keyed by their form name.
        if (!$offer->load($post)) {
            return false;
        }

        $terms->load($post);

        // The type-dependent terms rules need the parent's discriminator, which
        // the relation cannot provide while the offer is still unsaved.
        $terms->offerType = $offer->type;

        if (!Model::validateMultiple([$offer, $terms])) {
            return false;
        }

        return $offer->saveWithTerms($terms);
    }

    private function findModel(int $id): Offer
    {
        $offer = Offer::find()->withCasino()->withTerms()->andWhere(['offer.id' => $id])->one();

        if ($offer === null) {
            throw new NotFoundHttpException('The requested offer does not exist.');
        }

        return $offer;
    }
}
