<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\OfferTerms;
use yii\test\ActiveFixture;

class OfferTermsFixture extends ActiveFixture
{
    public $modelClass = OfferTerms::class;

    public $dataFile = '@common/tests/Support/data/offer_terms.php';

    /**
     * The foreign key forbids loading terms before their offers.
     */
    public $depends = [OfferFixture::class];
}
