<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum\Type;

use App\Enum\Type\Track;
use PHPUnit\Framework\TestCase;

final class TrackTest extends TestCase
{
    public function testFromLetterMapsEachTrack(): void
    {
        self::assertSame(Track::Feature, Track::fromLetter('f'));
        self::assertSame(Track::Refacto, Track::fromLetter('r'));
        self::assertSame(Track::Tech, Track::fromLetter('t'));
    }

    public function testFromLetterRejectsUnknownLetter(): void
    {
        $this->expectException(\ValueError::class);

        Track::fromLetter('x');
    }

    public function testLabels(): void
    {
        self::assertSame('Feature', Track::Feature->label());
        self::assertSame('Refacto', Track::Refacto->label());
        self::assertSame('Tech', Track::Tech->label());
    }
}
