<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Casino model.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $rating decimal(2,1) between 0 and 5, returned as a string by PDO
 * @property bool $is_active
 * @property int $created_at
 * @property int $updated_at
 *
 * @property-read Offer[] $offers
 */
class Casino extends ActiveRecord
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public static function tableName(): string
    {
        return '{{%casino}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
            [
                // Runs on beforeValidate, so the rules below still see the generated slug.
                // `immutable` means a slug typed by the admin is kept; an empty one is
                // derived from the name and `ensureUnique` appends -2, -3, ... on collision.
                'class' => SluggableBehavior::class,
                'attribute' => 'name',
                'slugAttribute' => 'slug',
                'ensureUnique' => true,
                'immutable' => true,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['name'], 'trim'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 120],

            [['slug'], 'trim'],
            [['slug'], 'string', 'max' => 140],
            [['slug'], 'match', 'pattern' => self::SLUG_PATTERN,
                'message' => '{attribute} may only contain lowercase letters, digits and single '
                    . 'hyphens, like "neon-palace". Leave it blank to build one from the name.'],
            [['slug'], 'unique',
                'message' => 'Another casino already uses the slug "{value}", and the public URL '
                    . '/casino/{value} must point at one casino only. Leave it blank to have a '
                    . 'free one generated.'],

            [['rating'], 'required'],
            [['rating'], 'number', 'min' => 0, 'max' => 5,
                'message' => '{attribute} must be a number, like 4.5.',
                'tooSmall' => '{attribute} cannot be below 0. '
                    . 'Use 0 for a casino you have not rated yet.',
                'tooBig' => '{attribute} cannot be above 5 - that is the best score there is.'],

            [['is_active'], 'default', 'value' => true],
            [['is_active'], 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'slug' => 'Slug',
            'rating' => 'Rating (0-5)',
            'is_active' => 'Active',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ];
    }

    public function getOffers(): ActiveQuery
    {
        return $this->hasMany(Offer::class, ['casino_id' => 'id']);
    }
}
