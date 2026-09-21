<?php

declare(strict_types=1);

namespace backend\components;

use common\enums\OfferStatus;
use common\models\Casino;
use common\models\Offer;

/**
 * Counters and shortlists behind the admin dashboard.
 *
 * Everything here is an aggregate or a bounded query: the dashboard must not
 * get slower as the catalogue grows.
 */
final class Dashboard
{
    /**
     * Number of offers to preview on the dashboard.
     */
    public const RECENT_LIMIT = 5;

    /**
     * Window used for the "expiring soon" counter.
     */
    public const EXPIRING_DAYS = 30;

    public function casinoCount(): int
    {
        return (int) Casino::find()->count();
    }

    public function activeCasinoCount(): int
    {
        return (int) Casino::find()->where(['is_active' => true])->count();
    }

    public function offerCount(): int
    {
        return (int) Offer::find()->count();
    }

    /**
     * One grouped query instead of one count per status.
     *
     * @return array<string, int> status value => number of offers
     */
    public function offersByStatus(): array
    {
        // `column()` would hand back the first selected column (the status),
        // so the rows are read whole and mapped explicitly.
        $rows = Offer::find()
            ->select(['status', 'total' => 'COUNT(*)'])
            ->groupBy('status')
            ->asArray()
            ->all();

        // Statuses with no rows still belong on the dashboard, as zeroes.
        $result = array_fill_keys(OfferStatus::values(), 0);

        foreach ($rows as $row) {
            $result[(string) $row['status']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * Offers the public site would show right now.
     */
    public function visibleOfferCount(): int
    {
        return (int) Offer::find()->active()->notExpired()->count();
    }

    /**
     * Published offers whose expiry falls inside the next month — the ones an
     * administrator may want to extend or retire.
     */
    public function expiringSoonCount(): int
    {
        return (int) Offer::find()
            ->active()
            ->notExpired()
            ->andWhere(['<=', 'offer.expires_at', date('Y-m-d H:i:s', strtotime('+' . self::EXPIRING_DAYS . ' days'))])
            ->count();
    }

    /**
     * Published offers whose date has already passed while still marked active:
     * they silently disappeared from the public site and need a status change.
     */
    public function lapsedOfferCount(): int
    {
        return (int) Offer::find()
            ->active()
            ->andWhere(['not', ['offer.expires_at' => null]])
            ->andWhere(['<=', 'offer.expires_at', date('Y-m-d H:i:s')])
            ->count();
    }

    /**
     * Latest offers, with their casino eager-loaded for the preview table.
     *
     * @return list<Offer>
     */
    public function recentOffers(): array
    {
        return Offer::find()
            ->withCasino()
            ->orderBy(['offer.created_at' => SORT_DESC, 'offer.id' => SORT_DESC])
            ->limit(self::RECENT_LIMIT)
            ->all();
    }
}
