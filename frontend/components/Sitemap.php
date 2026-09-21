<?php

declare(strict_types=1);

namespace frontend\components;

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
        /** @var array<int, array{name: string, slug: string, lastmod: int}> $casinos */
        $casinos = [];
        $newest = 0;

        // One pass builds both lists: every visible offer is an entry of its
        // own and also dates its casino's page.
        foreach (Offer::find()->publiclyVisible()->withTerms()->each(self::BATCH_SIZE) as $offer) {
            /** @var Offer $offer */
            $lastmod = $this->offerLastmod($offer);
            $newest = max($newest, $lastmod);

            $offers[] = [
                'loc' => Url::to(['/offer/view', 'slug' => $offer->slug], true),
                'lastmod' => $this->format($lastmod),
                'changefreq' => 'weekly',
            ];

            $casino = $offer->casino;
            $casinoLastmod = max((int) $casino->updated_at, $lastmod);

            $casinos[$casino->id] = [
                'loc' => Url::to((new CasinoPresenter($casino))->url(), true),
                'lastmod' => max($casinos[$casino->id]['lastmod'] ?? 0, $casinoLastmod),
            ];
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

        foreach ($casinos as $casino) {
            $urls[] = [
                'loc' => $casino['loc'],
                'lastmod' => $this->format($casino['lastmod']),
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
