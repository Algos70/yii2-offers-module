<?php

declare(strict_types=1);

namespace backend\models;

use common\models\Casino;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Filter/sort model behind the casino grid.
 *
 * Extends the AR class only to reuse its attributes and labels; `scenarios()`
 * drops the parent's validation so filtering never triggers save-time rules.
 */
class CasinoSearch extends Casino
{
    public const PAGE_SIZE = 20;

    /**
     * None: this model filters, it never persists. `Casino`'s
     * `SluggableBehavior` would otherwise slugify the name box into `$this->slug`
     * during validation and filter on it as well.
     */
    public function behaviors(): array
    {
        return [];
    }

    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['name', 'slug'], 'safe'],
            [['rating'], 'number'],
            [['is_active'], 'boolean'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function search(array $params): ActiveDataProvider
    {
        $query = Casino::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => self::PAGE_SIZE],
            'sort' => [
                // Explicit whitelist: anything outside it is ignored rather than
                // reaching the ORDER BY clause.
                'attributes' => ['id', 'name', 'slug', 'rating', 'is_active', 'created_at'],
                'defaultOrder' => ['name' => SORT_ASC],
            ],
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere(['id' => $this->id])
            ->andFilterWhere(['rating' => $this->rating])
            ->andFilterWhere(['is_active' => $this->is_active])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'slug', $this->slug]);

        return $dataProvider;
    }
}
