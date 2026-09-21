<?php

declare(strict_types=1);

namespace backend\models;

use common\enums\OfferStatus;
use common\enums\OfferType;
use common\models\Offer;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Filter/sort model behind the offer grid.
 *
 * Extends the AR class only to reuse its attributes and labels; `scenarios()`
 * drops the parent's validation so filtering never triggers save-time rules.
 */
class OfferSearch extends Offer
{
    public const PAGE_SIZE = 20;

    /**
     * Search-only attribute: upper bound on the related wagering requirement.
     */
    public ?string $maxWagering = null;

    public function rules(): array
    {
        return [
            [['id', 'casino_id'], 'integer'],
            [['title', 'slug'], 'safe'],
            // Out-of-range filter input is dropped instead of reaching the query.
            [['type'], 'in', 'range' => OfferType::values()],
            [['status'], 'in', 'range' => OfferStatus::values()],
            [['maxWagering'], 'number', 'min' => 0],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function attributeLabels(): array
    {
        return parent::attributeLabels() + ['maxWagering' => 'Max wagering'];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function search(array $params): ActiveDataProvider
    {
        // joinWith() does both jobs: LEFT JOINs so the related columns are
        // available to ORDER BY and WHERE, and eager loading so the grid never
        // issues a query per row. It must sit outside the filter branch below,
        // because sorting by casino.name is offered even with no filter set.
        $query = Offer::find()->joinWith(['casino', 'terms']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            // Sort and Pagination read Yii::$app->request->queryParams unless
            // told otherwise; passing $params keeps search() self-contained and
            // testable without faking a request.
            'pagination' => ['pageSize' => self::PAGE_SIZE, 'params' => $params],
            'sort' => [
                // Explicit whitelist; related columns get an expression each.
                'attributes' => [
                    'id',
                    'title',
                    'type',
                    'status',
                    'amount',
                    'expires_at',
                    'created_at',
                    'casino_id' => [
                        'asc' => ['casino.name' => SORT_ASC],
                        'desc' => ['casino.name' => SORT_DESC],
                        'label' => 'Casino',
                    ],
                    // Keyed after the filter attribute so a single grid column
                    // carries both the sort link and the filter input.
                    'maxWagering' => [
                        'asc' => ['offer_terms.wagering_multiplier' => SORT_ASC],
                        'desc' => ['offer_terms.wagering_multiplier' => SORT_DESC],
                        'label' => 'Wagering',
                    ],
                ],
                'defaultOrder' => ['created_at' => SORT_DESC],
                'params' => $params,
            ],
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        // Columns are table-qualified because both relations are in the query.
        $query->andFilterWhere(['offer.id' => $this->id])
            ->andFilterWhere(['offer.casino_id' => $this->casino_id])
            ->andFilterWhere(['offer.type' => $this->type])
            ->andFilterWhere(['offer.status' => $this->status])
            ->andFilterWhere(['like', 'offer.title', $this->title])
            ->andFilterWhere(['like', 'offer.slug', $this->slug])
            ->andFilterWhere(['<=', 'offer_terms.wagering_multiplier', $this->maxWagering]);

        return $dataProvider;
    }
}
