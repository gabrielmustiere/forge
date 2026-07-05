<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Board;

use App\Service\Board\StoryMetadataParser;
use PHPUnit\Framework\TestCase;

final class StoryMetadataParserTest extends TestCase
{
    private StoryMetadataParser $parser;

    protected function setUp(): void
    {
        $this->parser = new StoryMetadataParser();
    }

    public function testParsesAWellFormedFile(): void
    {
        $metadata = $this->parser->parse(json_encode([
            'version' => 1,
            'title' => "Afficher le kanban d'un projet",
            'created' => '2026-07-01',
            'updated' => '2026-07-05',
            'tags' => ['board', 'kanban'],
            'changelog' => [
                ['date' => '2026-07-01', 'type' => 'Création', 'description' => 'Pitch initial.'],
                ['date' => '2026-07-03', 'type' => 'Planification', 'description' => 'Plan validé.'],
            ],
            'delivery' => ['release' => 'v4.3.0', 'commit' => 'b7964b4'],
        ], \JSON_THROW_ON_ERROR));

        self::assertNotNull($metadata);
        self::assertSame(1, $metadata->version);
        self::assertSame("Afficher le kanban d'un projet", $metadata->title);
        self::assertSame('2026-07-01', $metadata->created->format('Y-m-d'));
        self::assertSame('2026-07-05', $metadata->updated->format('Y-m-d'));
        self::assertSame(['board', 'kanban'], $metadata->tags);
        self::assertCount(2, $metadata->changelog);
        self::assertSame('Planification', $metadata->changelog[1]->type);
        self::assertNotNull($metadata->delivery);
        self::assertSame('v4.3.0', $metadata->delivery->release);
        self::assertSame('b7964b4', $metadata->delivery->commit);
        self::assertTrue($metadata->isDelivered());
    }

    public function testAbsentFileYieldsNull(): void
    {
        self::assertNull($this->parser->parse(null));
        self::assertNull($this->parser->parse(''));
        self::assertNull($this->parser->parse('   '));
    }

    public function testMalformedJsonYieldsNull(): void
    {
        self::assertNull($this->parser->parse('{ not json'));
    }

    public function testNonObjectJsonYieldsNull(): void
    {
        self::assertNull($this->parser->parse('"a string"'));
        self::assertNull($this->parser->parse('42'));
    }

    public function testMissingVitalFieldYieldsNull(): void
    {
        // title manquant.
        self::assertNull($this->parser->parse(json_encode([
            'created' => '2026-07-01', 'updated' => '2026-07-05',
        ], \JSON_THROW_ON_ERROR)));

        // updated manquant.
        self::assertNull($this->parser->parse(json_encode([
            'title' => 'X', 'created' => '2026-07-01',
        ], \JSON_THROW_ON_ERROR)));
    }

    public function testInvalidDateYieldsNull(): void
    {
        // Date impossible → champ vital invalide → tout le fichier dégrade.
        self::assertNull($this->parser->parse(json_encode([
            'title' => 'X', 'created' => '2026-13-40', 'updated' => '2026-07-05',
        ], \JSON_THROW_ON_ERROR)));

        // Format non ISO.
        self::assertNull($this->parser->parse(json_encode([
            'title' => 'X', 'created' => '01/07/2026', 'updated' => '2026-07-05',
        ], \JSON_THROW_ON_ERROR)));
    }

    public function testUnknownVersionIsTolerated(): void
    {
        // version absente → défaut 1 ; version non-int → défaut 1. Jamais un rejet.
        $missing = $this->parser->parse(json_encode([
            'title' => 'X', 'created' => '2026-07-01', 'updated' => '2026-07-05',
        ], \JSON_THROW_ON_ERROR));
        self::assertNotNull($missing);
        self::assertSame(1, $missing->version);

        $future = $this->parser->parse(json_encode([
            'version' => 99, 'title' => 'X', 'created' => '2026-07-01', 'updated' => '2026-07-05',
        ], \JSON_THROW_ON_ERROR));
        self::assertNotNull($future);
        self::assertSame(99, $future->version);
    }

    public function testTagsAreCleanedAndDeduplicated(): void
    {
        $metadata = $this->parser->parse(json_encode([
            'title' => 'X', 'created' => '2026-07-01', 'updated' => '2026-07-05',
            'tags' => ['board', '  ', 'board', 42, 'kanban'],
        ], \JSON_THROW_ON_ERROR));

        self::assertNotNull($metadata);
        self::assertSame(['board', 'kanban'], $metadata->tags);
    }

    public function testMalformedChangelogEntriesAreSkippedNotFatal(): void
    {
        $metadata = $this->parser->parse(json_encode([
            'title' => 'X', 'created' => '2026-07-01', 'updated' => '2026-07-05',
            'changelog' => [
                ['date' => '2026-07-01', 'type' => 'Création', 'description' => 'OK.'],
                ['date' => 'not-a-date', 'type' => 'Bug', 'description' => 'ignorée'],
                ['type' => 'Sans date'],
                'pas un objet',
            ],
        ], \JSON_THROW_ON_ERROR));

        self::assertNotNull($metadata);
        self::assertCount(1, $metadata->changelog);
        self::assertSame('Création', $metadata->changelog[0]->type);
    }

    public function testPartialDeliveryIsTolerated(): void
    {
        // Commit présent sans release (tag en différé, règle 8).
        $metadata = $this->parser->parse(json_encode([
            'title' => 'X', 'created' => '2026-07-01', 'updated' => '2026-07-05',
            'delivery' => ['release' => null, 'commit' => 'abc1234'],
        ], \JSON_THROW_ON_ERROR));

        self::assertNotNull($metadata);
        self::assertNotNull($metadata->delivery);
        self::assertNull($metadata->delivery->release);
        self::assertSame('abc1234', $metadata->delivery->commit);
        self::assertTrue($metadata->isDelivered());
    }

    public function testEmptyDeliveryYieldsNoDelivery(): void
    {
        $metadata = $this->parser->parse(json_encode([
            'title' => 'X', 'created' => '2026-07-01', 'updated' => '2026-07-05',
            'delivery' => ['release' => null, 'commit' => null],
        ], \JSON_THROW_ON_ERROR));

        self::assertNotNull($metadata);
        self::assertNull($metadata->delivery);
        self::assertFalse($metadata->isDelivered());
    }
}
