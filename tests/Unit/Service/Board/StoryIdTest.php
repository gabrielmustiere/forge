<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Board;

use App\Enum\Type\Track;
use App\Service\Board\StoryId;
use PHPUnit\Framework\TestCase;

final class StoryIdTest extends TestCase
{
    public function testParsesTheThreeParts(): void
    {
        $id = StoryId::parse('005-f-kanban-projet');

        self::assertSame('005-f-kanban-projet', $id->value);
        self::assertSame(5, $id->number);
        self::assertSame(Track::Feature, $id->track);
        self::assertSame('kanban-projet', $id->slug);
    }

    public function testParsesEachTrackLetter(): void
    {
        self::assertSame(Track::Feature, StoryId::parse('001-f-a')->track);
        self::assertSame(Track::Refacto, StoryId::parse('012-r-refonte-cache')->track);
        self::assertSame(Track::Tech, StoryId::parse('120-t-ci-pipeline')->track);
    }

    public function testNumberKeepsMagnitudeWithoutLeadingZeros(): void
    {
        self::assertSame(120, StoryId::parse('120-t-x')->number);
        self::assertSame(1, StoryId::parse('001-f-x')->number);
    }

    public function testHumanizesTheSlug(): void
    {
        self::assertSame('Kanban projet', StoryId::parse('005-f-kanban-projet')->humanizedTitle());
        self::assertSame('Mapping etapes', StoryId::parse('004-f-mapping-etapes')->humanizedTitle());
        self::assertSame('Login', StoryId::parse('001-f-login')->humanizedTitle());
    }

    public function testRejectsMalformedId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StoryId::parse('5-f-kanban');
    }

    public function testRejectsUnknownTrackLetter(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StoryId::parse('005-x-kanban');
    }
}
