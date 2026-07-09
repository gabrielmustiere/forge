<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Interview;

use App\Service\Interview\StoryWorkspaceCleaner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class StoryWorkspaceCleanerTest extends TestCase
{
    private Filesystem $fs;
    private string $cloneDir;

    protected function setUp(): void
    {
        $this->fs = new Filesystem();
        $this->cloneDir = sys_get_temp_dir() . '/cleaner-' . bin2hex(random_bytes(6));
        $this->fs->mkdir($this->cloneDir);
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->cloneDir);
    }

    public function testRemovesTheStoryDirectory(): void
    {
        $storyDir = $this->cloneDir . '/docs/story/010-f-fake';
        $this->fs->dumpFile($storyDir . '/brief.md', "# brief\n");
        self::assertDirectoryExists($storyDir);

        (new StoryWorkspaceCleaner())->clean($this->cloneDir, '010-f-fake');

        self::assertDirectoryDoesNotExist($storyDir);
    }

    public function testIsBestEffortWhenTheDirectoryIsAbsent(): void
    {
        // Aucun dossier de story : la suppression ne doit lever aucune exception.
        (new StoryWorkspaceCleaner())->clean($this->cloneDir, '999-f-nope');

        $this->addToAssertionCount(1);
    }
}
