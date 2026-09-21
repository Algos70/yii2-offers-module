<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var list<array{loc: string, lastmod: string, changefreq: string}> $urls
 */

use yii\helpers\Html;

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= Html::encode($url['loc']) ?></loc>
        <lastmod><?= Html::encode($url['lastmod']) ?></lastmod>
        <changefreq><?= Html::encode($url['changefreq']) ?></changefreq>
    </url>
<?php endforeach ?>
</urlset>
