<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Board;

use App\Enum\Type\PipelineStage;
use App\Service\Board\StoryCard;
use App\Service\Board\StoryId;
use App\Service\Board\StoryMetadata;
use PHPUnit\Framework\TestCase;

final class StoryCardTest extends TestCase
{
    public function testTitleUsesMetadataWhenPresent(): void
    {
        $card = new StoryCard(
            StoryId::parse('005-f-kanban-projet'),
            PipelineStage::Besoin,
            ['pitch.md'],
            new StoryMetadata(
                1,
                "Afficher le kanban d'un projet",
                new \DateTimeImmutable('2026-06-01'),
                new \DateTimeImmutable('2026-06-10'),
                [],
                [],
                null,
            ),
        );

        self::assertSame("Afficher le kanban d'un projet", $card->title());
    }

    public function testTitleFallsBackToHumanizedSlugWithoutMetadata(): void
    {
        $card = new StoryCard(StoryId::parse('005-f-kanban-projet'), PipelineStage::Besoin, ['pitch.md']);

        self::assertNull($card->metadata);
        self::assertSame('Kanban projet', $card->title());
    }
}
