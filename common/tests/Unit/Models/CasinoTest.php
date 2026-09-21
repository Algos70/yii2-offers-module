<?php

declare(strict_types=1);

namespace common\tests\Unit\Models;

use Codeception\Test\Unit;
use common\models\Casino;

final class CasinoTest extends Unit
{
    public function testSlugIsDerivedFromTheNameWhenLeftEmpty(): void
    {
        $casino = $this->makeCasino(['name' => 'Royal Spin Palace', 'slug' => '']);

        self::assertTrue($casino->save(), print_r($casino->getErrors(), true));
        self::assertSame('royal-spin-palace', $casino->slug);
    }

    public function testDerivedSlugIsMadeUniqueOnCollision(): void
    {
        $first = $this->makeCasino(['name' => 'Lucky Bear']);
        self::assertTrue($first->save(), print_r($first->getErrors(), true));

        $second = $this->makeCasino(['name' => 'Lucky Bear']);
        self::assertTrue($second->save(), print_r($second->getErrors(), true));

        self::assertSame('lucky-bear', $first->slug);
        self::assertSame('lucky-bear-2', $second->slug);
    }

    public function testExplicitSlugIsKeptAndNotOverwritten(): void
    {
        $casino = $this->makeCasino(['name' => 'Golden Reels', 'slug' => 'gr-main']);

        self::assertTrue($casino->save(), print_r($casino->getErrors(), true));
        self::assertSame('gr-main', $casino->slug);
    }

    public function testExplicitSlugMustMatchThePattern(): void
    {
        $casino = $this->makeCasino(['name' => 'Golden Reels', 'slug' => 'Not A Slug']);

        self::assertFalse($casino->validate());
        self::assertArrayHasKey('slug', $casino->getErrors());
    }

    public function testDuplicateExplicitSlugIsRejected(): void
    {
        $first = $this->makeCasino(['name' => 'First', 'slug' => 'shared-slug']);
        self::assertTrue($first->save(), print_r($first->getErrors(), true));

        $second = $this->makeCasino(['name' => 'Second', 'slug' => 'shared-slug']);

        self::assertFalse($second->validate());
        self::assertArrayHasKey('slug', $second->getErrors());
    }

    public function testNameIsRequiredAndTrimmed(): void
    {
        $blank = $this->makeCasino(['name' => '   ']);
        self::assertFalse($blank->validate());
        self::assertArrayHasKey('name', $blank->getErrors());

        $padded = $this->makeCasino(['name' => '  Spin City  ']);
        self::assertTrue($padded->validate(), print_r($padded->getErrors(), true));
        self::assertSame('Spin City', $padded->name);
    }

    public function testRatingMustStayInsideItsScale(): void
    {
        $tooHigh = $this->makeCasino(['rating' => '6']);
        self::assertFalse($tooHigh->validate());
        self::assertArrayHasKey('rating', $tooHigh->getErrors());

        $negative = $this->makeCasino(['rating' => '-1']);
        self::assertFalse($negative->validate());

        $edge = $this->makeCasino(['rating' => '5']);
        self::assertTrue($edge->validate(), print_r($edge->getErrors(), true));
    }

    public function testRatingIsRequired(): void
    {
        $casino = new Casino(['name' => 'No Rating']);

        self::assertFalse($casino->validate());
        self::assertArrayHasKey('rating', $casino->getErrors());
    }

    public function testDefaultsAndTimestampsAreApplied(): void
    {
        $casino = new Casino(['name' => 'Defaults Casino', 'rating' => '4.0']);

        self::assertTrue($casino->save(), print_r($casino->getErrors(), true));
        self::assertTrue((bool) $casino->is_active);
        self::assertNotEmpty($casino->created_at);
        self::assertSame($casino->created_at, $casino->updated_at);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeCasino(array $attributes = []): Casino
    {
        return new Casino($attributes + ['name' => 'Test Casino', 'rating' => '4.5']);
    }
}
