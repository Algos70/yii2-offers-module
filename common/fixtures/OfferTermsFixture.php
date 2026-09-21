<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\OfferTerms;
use yii\test\ActiveFixture;

class OfferTermsFixture extends ActiveFixture
{
    public $modelClass = OfferTerms::class;

    /**
     * The foreign key forbids loading terms before their offers.
     */
    public $depends = [OfferFixture::class];
}
