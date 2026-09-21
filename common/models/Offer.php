<?php

declare(strict_types=1);

namespace common\models;

use common\enums\OfferStatus;
use common\enums\OfferType;
use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Offer model.
 *
 * @property int $id
 * @property int $casino_id
 * @property string $title
 * @property string $slug
 * @property string $type one of {@see OfferType}
 * @property string $amount decimal(12,2), returned as a string by PDO
 * @property string|null $expires_at 'Y-m-d H:i:s'; null means the offer never expires
 * @property string $status one of {@see OfferStatus}
 * @property int $created_at
 * @property int $updated_at
 *
 * @property-read Casino $casino
 * @property-read OfferTerms|null $terms
 */
class Offer extends ActiveRecord
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    public const DATETIME_FORMAT = 'php:Y-m-d H:i:s';

    public static function tableName(): string
    {
        return '{{%offer}}';
    }

    public static function find(): OfferQuery
    {
        return new OfferQuery(static::class);
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
            [
                // Same contract as Casino: derive from the title only while the slug
                // is empty, keep an admin-typed slug, append -2, -3, ... on collision.
                'class' => SluggableBehavior::class,
                'attribute' => 'title',
                'slugAttribute' => 'slug',
                'ensureUnique' => true,
                'immutable' => true,
            ],
        ];
    }

    public function rules(): array
    {
        // Spelled out in the message so a rejected value names the valid ones.
        $types = implode(', ', OfferType::labels());
        $statuses = implode(', ', OfferStatus::labels());

        return [
            [['casino_id'], 'required'],
            [['casino_id'], 'integer'],
            [['casino_id'], 'exist', 'targetClass' => Casino::class, 'targetAttribute' => 'id',
                'message' => 'That casino no longer exists - pick one from the list.'],

            [['title'], 'trim'],
            [['title'], 'required'],
            [['title'], 'string', 'max' => 160],

            [['slug'], 'trim'],
            [['slug'], 'string', 'max' => 180],
            [['slug'], 'match', 'pattern' => self::SLUG_PATTERN,
                'message' => '{attribute} may only contain lowercase letters, digits and single '
                    . 'hyphens, like "neon-palace-welcome". Leave it blank to build one from the title.'],
            [['slug'], 'unique',
                'message' => 'Another offer already uses the slug "{value}", and the public URL '
                    . '/offer/{value} must point at one offer only. Leave it blank to have a free '
                    . 'one generated.'],

            [['type'], 'required'],
            [['type'], 'in', 'range' => OfferType::values(),
                'message' => '{attribute} must be one of: ' . $types . '.'],

            [['status'], 'default', 'value' => OfferStatus::Draft->value],
            [['status'], 'required'],
            [['status'], 'in', 'range' => OfferStatus::values(),
                'message' => '{attribute} must be one of: ' . $statuses . '.'],

            [['amount'], 'required'],
            [['amount'], 'number', 'min' => 0,
                'message' => '{attribute} must be a number - the cash value, or the count of '
                    . 'spins for a free-spins offer.',
                'tooSmall' => '{attribute} cannot be negative; an offer gives value away.'],

            [['expires_at'], 'datetime', 'format' => self::DATETIME_FORMAT,
                'message' => '{attribute} must be written as YYYY-MM-DD HH:MM:SS, '
                    . 'like 2026-12-31 23:59:59. Leave it blank if the offer never expires.'],
            [['expires_at'], 'validateExpiresAtInFuture'],

            [['status'], 'validateStatusAgainstExpiry'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'casino_id' => 'Casino',
            'title' => 'Title',
            'slug' => 'Slug',
            'type' => 'Type',
            'amount' => 'Amount',
            'expires_at' => 'Expires at',
            'status' => 'Status',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ];
    }

    /**
     * A newly entered expiry must lie ahead. An untouched one may be in the past,
     * otherwise an offer that has already run out could never be edited again.
     */
    public function validateExpiresAtInFuture(string $attribute): void
    {
        if ($this->expires_at === null || $this->expires_at === '') {
            return;
        }

        if (!$this->isAttributeChanged($attribute)) {
            return;
        }

        if (strtotime($this->expires_at) <= time()) {
            $this->addError($attribute, sprintf(
                '%s must be in the future - %s has already passed (it is now %s). '
                . 'Leave it blank for an offer that never expires.',
                $this->getAttributeLabel($attribute),
                $this->expires_at,
                date('Y-m-d H:i'),
            ));
        }
    }

    /**
     * An offer whose expiry has passed cannot be published.
     */
    public function validateStatusAgainstExpiry(string $attribute): void
    {
        if ($this->status !== OfferStatus::Active->value) {
            return;
        }

        if ($this->expires_at === null || $this->expires_at === '') {
            return;
        }

        if (strtotime($this->expires_at) <= time()) {
            $this->addError($attribute, sprintf(
                'This offer expired on %s, so it cannot be "%s" - the public site would never '
                . 'show it. Push the expiry date forward, or set the status to "%s".',
                $this->expires_at,
                OfferStatus::Active->label(),
                OfferStatus::Expired->label(),
            ));
        }
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && strtotime($this->expires_at) <= time();
    }

    public function getCasino(): ActiveQuery
    {
        return $this->hasOne(Casino::class, ['id' => 'casino_id']);
    }

    public function getTerms(): ActiveQuery
    {
        return $this->hasOne(OfferTerms::class, ['offer_id' => 'id']);
    }

    /**
     * Writes the offer and its terms as one unit.
     *
     * Both models are expected to be validated already (the controller does it
     * with `Model::validateMultiple()`), so this only owns the transaction and
     * the foreign key wiring. Terms that the admin left completely blank are
     * not written at all, and an existing blank-out deletes the row, so the
     * table never holds all-null noise.
     */
    public function saveWithTerms(OfferTerms $terms): bool
    {
        $transaction = static::getDb()->beginTransaction();

        try {
            if (!$this->save(false)) {
                $transaction->rollBack();

                return false;
            }

            if ($terms->isEmpty()) {
                if (!$terms->getIsNewRecord()) {
                    $terms->delete();
                }
            } else {
                $terms->offer_id = $this->id;
                if (!$terms->save(false)) {
                    $transaction->rollBack();

                    return false;
                }
            }

            $transaction->commit();
            $this->populateRelation('terms', $terms->isEmpty() ? null : $terms);

            return true;
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }
    }
}
