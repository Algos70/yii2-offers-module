<?php

declare(strict_types=1);

namespace common\fixtures;

use common\models\Casino;
use yii\test\ActiveFixture;

class CasinoFixture extends ActiveFixture
{
    public $modelClass = Casino::class;

    /**
     * Aliased so every suite (common, backend, frontend) loads the same rows;
     * `codecept_data_dir()` would resolve to the calling suite's own folder.
     */
    public $dataFile = '@common/tests/Support/data/casino.php';
}
