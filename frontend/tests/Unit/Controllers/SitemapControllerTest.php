<?php

declare(strict_types=1);

namespace frontend\tests\Unit\Controllers;

use Codeception\Test\Unit;
use common\fixtures\CasinoFixture;
use common\fixtures\OfferFixture;
use common\fixtures\OfferTermsFixture;
use frontend\controllers\SitemapController;
use Yii;

/**
 * Crawlers read the response header, and neither browser module in this
 * project exposes headers, so the controller's response setup is asserted
 * directly.
 */
final class SitemapControllerTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'casino' => ['class' => CasinoFixture::class],
            'offer' => ['class' => OfferFixture::class],
            'offerTerms' => ['class' => OfferTermsFixture::class],
        ];
    }

    public function testResponseIsRawXml(): void
    {
        $controller = new SitemapController('sitemap', Yii::$app);

        $body = $controller->actionIndex();

        self::assertSame('application/xml; charset=UTF-8', Yii::$app->response->headers->get('Content-Type'));
        self::assertSame(\yii\web\Response::FORMAT_RAW, Yii::$app->response->format);
        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', trim($body));
        self::assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $body);
        self::assertNotFalse(simplexml_load_string($body), 'the sitemap must parse as XML');
    }

    public function testNoLayoutIsWrappedAroundTheDocument(): void
    {
        $controller = new SitemapController('sitemap', Yii::$app);

        self::assertFalse($controller->layout);
        self::assertStringNotContainsString('<html', $controller->actionIndex());
    }
}
