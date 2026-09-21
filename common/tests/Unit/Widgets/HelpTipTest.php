<?php

declare(strict_types=1);

namespace common\tests\Unit\Widgets;

use Codeception\Test\Unit;
use common\widgets\HelpTip;

final class HelpTipTest extends Unit
{
    /**
     * A typo in a view would otherwise render a marker explaining nothing, and
     * a silent empty tooltip is worse than a loud failure.
     */
    public function testAnUnknownTermIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        HelpTip::text('wagering_requirement');
    }

    public function testTheGlossaryExplainsTheJargonTheViewsUse(): void
    {
        // The keys the offer form, the admin grid and the public pages ask for.
        foreach (
            [
                'offer_type',
                'amount',
                'wagering',
                'min_deposit',
                'max_bonus',
                'max_cashout',
                'valid_days',
                'terms_url',
                'terms_note',
                'max_wagering_filter',
            ] as $term
        ) {
            self::assertNotSame('', HelpTip::text($term), "\"$term\" has no definition");
        }
    }
}
