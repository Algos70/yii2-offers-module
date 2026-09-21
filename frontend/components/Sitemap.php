<?php

declare(strict_types=1);

namespace frontend\components;

use common\models\Casino;
use common\models\Offer;
use yii\helpers\Url;

/**
 * The public sitemap, built from the database.
 *
 * It lists exactly what the public site serves: the home page, the offer
 * listing, one entry per casino that has something to show, and one per visible
 * offer. Draft, expired and lapsed offers are excluded — and so are offers of
 * an inactive casino — because the sitemap starts from the same
 * `publiclyVisible()` scope the pages themselves use. A URL that 404s must
 * never appear here.
 */
final class Sitemap
{
    /**
     * Rows fetched per batch. The sitemap is a full-table read, so it is
     * iterated in batches rather than hydrated in one go.
     */
    private const BATCH_SIZE = 200;

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string}>
     */
    public function urls(): array
    {
        $offers = [];
        /** @var array<int, int> $offerDates casino id => newest offer lastmod */
        $offerDates = [];
        $newest = 0;

        // Every visible offer is an entry of its own, and also dates the casino
        // page that lists it.
        foreach (Offer::find()->publiclyVisible()->withTerms()->each(self::BATCH_SIZE) as $offer) {
            /** @var Offer $offer */
            $lastmod = $this->offerLastmod($offer);
            $newest = max($newest, $lastmod);

            $offers[] = [
                'loc' => Url::to(['/offer/view', 'slug' => $offer->slug], true),
                'lastmod' => $this->format($lastmod),
                'changefreq' => 'weekly',
            ];

            $offerDates[$offer->casino_id] = max($offerDates[$offer->casino_id] ?? 0, $lastmod);
        }

        // With nothing published, the index pages still exist; their date is
        // then simply the time of the request.
        $newest = $newest > 0 ? $newest : time();

        $urls = [
            [
                'loc' => Url::to(['/site/index'], true),
                'lastmod' => $this->format($newest),
                'changefreq' => 'daily',
            ],
            [
                'loc' => Url::to(['/offer/index'], true),
                'lastmod' => $this->format($newest),
                'changefreq' => 'daily',
            ],
            [
                'loc' => Url::to(['/casino/index'], true),
                'lastmod' => $this->format($newest),
                'changefreq' => 'weekly',
            ],
        ];

        // Driven by the casino table, not by the offers: an active casino with
        // nothing published still has a page — it says so — and leaving it out
        // would hide a live URL from crawlers. Only inactive casinos are
        // omitted, because only they 404.
        $activeCasinos = Casino::find()->where(['is_active' => true])->orderBy(['name' => SORT_ASC]);

        foreach ($activeCasinos->each(self::BATCH_SIZE) as $casino) {
            /** @var Casino $casino */
            $urls[] = [
                'loc' => Url::to((new CasinoPresenter($casino))->url(), true),
                'lastmod' => $this->format(max((int) $casino->updated_at, $offerDates[$casino->id] ?? 0)),
                'changefreq' => 'weekly',
            ];
        }

        return array_merge($urls, $offers);
    }

    /**
     * An offer page also renders its bonus terms, so editing those changes the
     * page and has to move its `lastmod`.
     */
    private function offerLastmod(Offer $offer): int
    {
        return max((int) $offer->updated_at, (int) ($offer->terms->updated_at ?? 0));
    }

    /**
     * W3C datetime, which is what `lastmod` expects.
     */
    private function format(int $timestamp): string
    {
        return date('c', $timestamp);
    }
}
