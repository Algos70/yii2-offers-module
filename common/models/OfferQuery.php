<?php

declare(strict_types=1);

namespace common\models;

use common\enums\OfferStatus;
use yii\db\ActiveQuery;

/**
 * Reusable query scopes for {@see Offer}.
 *
 * The public listing, the offer detail page and the sitemap all need the same
 * "visible to the world" predicate, so it lives here instead of being retyped
 * in every controller.
 *
 * @extends ActiveQuery<Offer>
 */
class OfferQuery extends ActiveQuery
{
    /**
     * Published offers only; says nothing about expiry.
     */
    public function active(): self
    {
        return $this->andWhere(['offer.status' => OfferStatus::Active->value]);
    }

    /**
     * Offers that never expire, or whose expiry is still ahead of us.
     *
     * The boundary is a timestamp bound from PHP rather than MySQL's NOW():
     * validation compares against PHP's clock, and the two must not drift apart
     * when the database server runs in a different time zone (MySQL's
     * `@@session.time_zone` is `SYSTEM` by default).
     */
    public function notExpired(): self
    {
        return $this->andWhere([
            'or',
            ['offer.expires_at' => null],
            ['>', 'offer.expires_at', date('Y-m-d H:i:s')],
        ]);
    }

    /**
     * Eager-loads the casino so listings stay at a constant query count.
     */
    public function withCasino(): self
    {
        return $this->with(['casino']);
    }
}
