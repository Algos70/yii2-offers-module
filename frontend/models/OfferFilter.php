<?php

declare(strict_types=1);

namespace frontend\models;

use common\enums\OfferStatus;
use common\enums\OfferType;
use common\models\Casino;
use common\models\Offer;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * The public listing's filters, submitted as a plain GET form.
 *
 * Both values arrive from the query string, so they are validated before they
 * reach the query: an unknown type or casino slug is dropped rather than
 * narrowing the list to nothing or reaching SQL.
 */
class OfferFilter extends Model
{
    public const PAGE_SIZE = 20;

    /**
     * One of {@see OfferType}, or null for "all types".
     */
    public ?string $type = null;

    /**
     * A casino slug, or null for "all casinos".
     */
    public ?string $casino = null;

    public function rules(): array
    {
        return [
            [['type'], 'in', 'range' => OfferType::values()],
            [['casino'], 'string', 'max' => 140],
            [['casino'], 'exist',
                'targetClass' => Casino::class,
                'targetAttribute' => 'slug',
                'filter' => ['is_active' => true],
            ],
        ];
    }

    public function formName(): string
    {
        // Filters appear in the URL as ?type=…&casino=…, not ?OfferFilter[type]=…
        return '';
    }

    /**
     * Active, non-expired offers of active casinos, newest first.
     *
     * @param array<string, mixed> $params
     */
    public function search(array $params): ActiveDataProvider
    {
        // joinWith() both filters on casino.slug and eager-loads the relation,
        // so the card list stays at a constant number of queries.
        $query = Offer::find()
            ->publiclyVisible()
            ->withTerms()
            ->orderBy(['offer.created_at' => SORT_DESC, 'offer.id' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => self::PAGE_SIZE, 'params' => $params],
            // The public list has one meaningful order, so sorting is off and
            // no user input can reach ORDER BY at all.
            'sort' => false,
        ]);

        if (!($this->load($params) && $this->validate())) {
            // Invalid input is ignored: the visitor sees the full list rather
            // than an error page.
            $this->type = null;
            $this->casino = null;

            return $dataProvider;
        }

        $query->andFilterWhere(['offer.type' => $this->type])
            ->andFilterWhere(['casino.slug' => $this->casino]);

        return $dataProvider;
    }

    /**
     * Casinos worth offering in the dropdown: active, and with something to show.
     *
     * @return array<string, string> slug => name
     */
    public function casinoOptions(): array
    {
        return Casino::find()
            ->alias('casino')
            ->select(['casino.name', 'casino.slug'])
            ->distinct()
            ->innerJoin(['offer' => Offer::tableName()], 'offer.casino_id = casino.id')
            ->where(['casino.is_active' => true])
            ->andWhere(['offer.status' => OfferStatus::Active->value])
            ->andWhere(['or', ['offer.expires_at' => null], ['>', 'offer.expires_at', date('Y-m-d H:i:s')]])
            ->orderBy(['casino.name' => SORT_ASC])
            ->indexBy('slug')
            ->column();
    }

    /**
     * How many offers the public site shows in total, ignoring the filters.
     * The heading describes the catalogue, so it must not drop to zero just
     * because the visitor narrowed the list.
     */
    public function visibleTotal(): int
    {
        return (int) Offer::find()->publiclyVisible()->count();
    }

    /**
     * True when the visitor narrowed the list, which changes the empty-state copy.
     */
    public function isFiltered(): bool
    {
        return $this->type !== null || $this->casino !== null;
    }

    public function casinoName(): ?string
    {
        if ($this->casino === null) {
            return null;
        }

        return Casino::find()->where(['slug' => $this->casino])->select('name')->scalar() ?: null;
    }
}
