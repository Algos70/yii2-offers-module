<?php

declare(strict_types=1);

namespace frontend\controllers;

use frontend\components\Sitemap;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Serves /sitemap.xml.
 */
class SitemapController extends Controller
{
    /**
     * No layout: the response is an XML document, not a page.
     */
    public $layout = false;

    public function actionIndex(): string
    {
        $response = Yii::$app->response;
        // FORMAT_RAW so Yii does not re-encode the rendered document, plus the
        // content type crawlers expect.
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $this->renderPartial('index', [
            'urls' => (new Sitemap())->urls(),
        ]);
    }
}
