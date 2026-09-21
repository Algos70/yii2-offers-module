<?php

declare(strict_types=1);

namespace common\models;

use common\enums\OfferType;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Bonus terms of an offer: a strict 1:1 extension of {@see Offer}.
 *
 * `offer_id` is both the primary key and the foreign key, so an offer carries at
 * most one terms row and the row dies with its offer.
 *
 * @property int $offer_id
 * @property string|null $wagering_multiplier decimal(5,1), returned as a string by PDO
 * @property string|null $min_deposit decimal(10,2)
 * @property string|null $max_bonus decimal(10,2)
 * @property string|null $max_cashout decimal(10,2)
 * @property int|null $valid_days
 * @property string|null $terms_url
 * @property string|null $terms_note
 * @property int $created_at
 * @property int $updated_at
 *
 * @property-read Offer $offer
 */
class OfferTerms extends ActiveRecord
{
    /**
     * Type of the owning offer, used only as validation context.
     *
     * The type-dependent rules belong to the terms, but the discriminator lives
     * on the parent. The relation cannot supply it: while creating an offer the
     * parent has no id yet, so the caller assigns `$terms->offerType = $offer->type`
     * before validating.
     */
    public ?string $offerType = null;

    /**
     * Columns the admin actually fills in; `isEmpty()` and the controller use it
     * to decide whether a terms row is worth writing at all.
     */
    private const USER_ATTRIBUTES = [
        'wagering_multiplier',
        'min_deposit',
        'max_bonus',
        'max_cashout',
        'valid_days',
        'terms_url',
        'terms_note',
    ];

    public static function tableName(): string
    {
        return '{{%offer_terms}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            [['wagering_multiplier'], 'number', 'min' => 0, 'max' => 200,
                'message' => '{attribute} is a multiplier, so enter a plain number like 35 '
                    . '(meaning 35x) - not "35x".',
                'tooSmall' => '{attribute} cannot be negative. Leave it blank if the bonus '
                    . 'carries no wagering requirement.',
                'tooBig' => '{attribute} above 200x is not a real bonus - check for a typo.'],
            [['min_deposit', 'max_bonus', 'max_cashout'], 'number', 'min' => 0,
                'message' => '{attribute} must be an amount in euros, like 20 or 20.50.',
                'tooSmall' => '{attribute} cannot be negative.'],
            [['valid_days'], 'integer', 'min' => 1, 'max' => 365,
                'message' => '{attribute} must be a whole number of days.',
                'tooSmall' => '{attribute} must be at least 1 day. Leave it blank if the bonus '
                    . 'has no deadline.',
                'tooBig' => '{attribute} cannot exceed 365 - use a year at most.'],

            [['terms_url'], 'trim'],
            [['terms_url'], 'url', 'defaultScheme' => 'https',
                'message' => '{attribute} must be a full web address, like '
                    . 'https://example.com/terms/welcome.'],
            [['terms_url'], 'string', 'max' => 255],

            [['terms_note'], 'trim'],
            [['terms_note'], 'string', 'max' => 500],

            // A welcome bonus that states any terms at all must state its
            // minimum deposit. An offer with no terms yet (a draft being
            // sketched out) writes no row, so there is nothing to require.
            //
            // `enableClientValidation` is off because `when` has no effect in the
            // browser: Yii mirrors `required` into JS unconditionally unless a
            // `whenClient` counterpart is given, which would put form-field ids
            // into a shared model. Without this, saving *any* offer - including a
            // no-deposit one with blank terms - was blocked client-side by
            // "Min deposit cannot be blank." The server rule below is the real one.
            [['min_deposit'], 'required', 'enableClientValidation' => false,
                'message' => 'A welcome bonus that states any terms must also state its '
                    . 'minimum deposit. Enter one, or clear the other terms fields to save '
                    . 'this offer without terms.',
                'when' => static fn (self $model): bool
                    => $model->offerType === OfferType::Welcome->value && !$model->isEmpty()],
            // ... and a no-deposit offer must not carry one.
            [['min_deposit'], 'validateNotApplicable', 'when' => static fn (self $model): bool
                => $model->offerType === OfferType::NoDeposit->value],

            // A cap below the bonus itself is a typo, not a rule.
            [['max_cashout'], 'compare', 'compareAttribute' => 'max_bonus',
                'operator' => '>=', 'type' => 'number', 'skipOnEmpty' => true,
                // No {compareValue} placeholder: server-side it renders the other field's
                // value, but in the client-side mirror it renders its label instead.
                'message' => '{attribute} must be at least the max bonus - a cap below the '
                    . 'bonus would mean the player can never withdraw all of it.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'offer_id' => 'Offer',
            'wagering_multiplier' => 'Wagering (x)',
            'min_deposit' => 'Min deposit',
            'max_bonus' => 'Max bonus',
            'max_cashout' => 'Max cashout',
            'valid_days' => 'Valid for (days)',
            'terms_url' => 'Full T&C URL',
            'terms_note' => 'Extra note',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ];
    }

    /**
     * Rejects an attribute that does not apply to the owning offer's type.
     */
    public function validateNotApplicable(string $attribute): void
    {
        if ($this->$attribute === null || $this->$attribute === '') {
            return;
        }

        $this->addError(
            $attribute,
            sprintf(
                '%s does not apply to a "%s" offer - a no-deposit bonus is claimed without '
                . 'paying in. Clear this field, or change the offer type to "%s".',
                $this->getAttributeLabel($attribute),
                OfferType::NoDeposit->label(),
                OfferType::Welcome->label(),
            ),
        );
    }

    /**
     * True when the admin left every terms field blank, in which case no row is written.
     */
    public function isEmpty(): bool
    {
        foreach (self::USER_ATTRIBUTES as $attribute) {
            if ($this->$attribute !== null && $this->$attribute !== '') {
                return false;
            }
        }

        return true;
    }

    public function getOffer(): ActiveQuery
    {
        return $this->hasOne(Offer::class, ['id' => 'offer_id']);
    }
}
