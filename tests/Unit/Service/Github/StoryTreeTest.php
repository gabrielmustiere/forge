<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Github;

use App\Service\Github\StoryTree;
use PHPUnit\Framework\TestCase;

final class StoryTreeTest extends TestCase
{
    public function testGroupsFilesByConformingStoryFolder(): void
    {
        $tree = StoryTree::fromTreeEntries([
            ['path' => '001-f-connecteur', 'type' => 'tree'],
            ['path' => '001-f-connecteur/pitch.md', 'type' => 'blob'],
            ['path' => '001-f-connecteur/plan.md', 'type' => 'blob'],
            ['path' => '002-r-refonte', 'type' => 'tree'],
            ['path' => '002-r-refonte/report.md', 'type' => 'blob'],
        ]);

        self::assertTrue($tree->hasStories());
        self::assertCount(2, $tree->stories);

        [$first, $second] = $tree->stories;
        self::assertSame('001-f-connecteur', $first->id);
        self::assertSame(['pitch.md', 'plan.md'], $first->files());
        self::assertSame('002-r-refonte', $second->id);
        self::assertSame(['report.md'], $second->files());
    }

    public function testIgnoresPathsOutsideTheConvention(): void
    {
        $tree = StoryTree::fromTreeEntries([
            ['path' => 'README.md', 'type' => 'blob'],
            ['path' => 'draft/notes.md', 'type' => 'blob'],
            ['path' => 'a-001-article/plan.md', 'type' => 'blob'],
            ['path' => '1-f-tooshort/plan.md', 'type' => 'blob'],
            ['path' => '001-x-badtype/plan.md', 'type' => 'blob'],
            ['path' => '003-t-tech/plan.md', 'type' => 'blob'],
        ]);

        self::assertCount(1, $tree->stories);
        self::assertSame('003-t-tech', $tree->stories[0]->id);
    }

    public function testRegistersStoryFolderEvenWithoutFiles(): void
    {
        $tree = StoryTree::fromTreeEntries([
            ['path' => '004-f-empty', 'type' => 'tree'],
        ]);

        self::assertTrue($tree->hasStories());
        self::assertSame([], $tree->stories[0]->files());
    }

    public function testEmptyTreeHasNoStories(): void
    {
        $tree = StoryTree::fromTreeEntries([]);

        self::assertFalse($tree->hasStories());
        self::assertSame([], $tree->stories);
    }

    public function testFlattensNestedFilesRelativeToTheStory(): void
    {
        $tree = StoryTree::fromTreeEntries([
            ['path' => '005-f-nested/assets', 'type' => 'tree'],
            ['path' => '005-f-nested/assets/diagram.png', 'type' => 'blob'],
            ['path' => '005-f-nested/plan.md', 'type' => 'blob'],
        ]);

        self::assertSame(['assets/diagram.png', 'plan.md'], $tree->stories[0]->files());
    }
}
