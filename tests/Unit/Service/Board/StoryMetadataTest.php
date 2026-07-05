<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Board;

use App\Service\Board\StoryChangelogEntry;
use App\Service\Board\StoryDelivery;
use App\Service\Board\StoryMetadata;
use PHPUnit\Framework\TestCase;

final class StoryMetadataTest extends TestCase
{
    public function testExposesItsFields(): void
    {
        $metadata = new StoryMetadata(
            1,
            'Un vrai titre',
            new \DateTimeImmutable('2026-07-01'),
            new \DateTimeImmutable('2026-07-05'),
            ['board', 'kanban'],
            [new StoryChangelogEntry(new \DateTimeImmutable('2026-07-01'), 'Création', 'Pitch.')],
            new StoryDelivery('v1.0.0', 'abc1234'),
        );

        self::assertSame('Un vrai titre', $metadata->title);
        self::assertSame(['board', 'kanban'], $metadata->tags);
        self::assertTrue($metadata->hasTags());
        self::assertTrue($metadata->hasChangelog());
        self::assertTrue($metadata->isDelivered());
    }

    public function testReportsEmptyCollectionsAndNoDelivery(): void
    {
        $metadata = new StoryMetadata(
            1,
            'Titre',
            new \DateTimeImmutable('2026-07-01'),
            new \DateTimeImmutable('2026-07-01'),
            [],
            [],
            null,
        );

        self::assertFalse($metadata->hasTags());
        self::assertFalse($metadata->hasChangelog());
        self::assertFalse($metadata->isDelivered());
    }

    public function testDeliveryDetectsPartialLink(): void
    {
        // Commit sans release compte comme livré.
        self::assertTrue((new StoryDelivery(null, 'abc1234'))->isDelivered());
        // Release sans commit aussi.
        self::assertTrue((new StoryDelivery('v1.0.0', null))->isDelivered());
        // Vide : pas livré (le parser n'émet alors pas de delivery, garde-fou en plus).
        self::assertFalse((new StoryDelivery(null, null))->isDelivered());
    }
}
