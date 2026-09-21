<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\Offer;
use yii\test\ActiveFixture;

class OfferFixture extends ActiveFixture
{
    public $modelClass = Offer::class;

    /**
     * The foreign key forbids loading offers before their casinos.
     */
    public $depends = [CasinoFixture::class];
}
