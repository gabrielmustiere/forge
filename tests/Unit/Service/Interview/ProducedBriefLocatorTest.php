<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Interview;

use App\Service\Interview\InterviewFailedException;
use App\Service\Interview\ProducedBriefLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Utilise un vrai dépôt git temporaire (pas de double) : la détection repose sur la sémantique
 * exacte de `git status --porcelain`, qu'un mock ne reproduirait pas fidèlement.
 */
final class ProducedBriefLocatorTest extends TestCase
{
    private Filesystem $fs;
    private string $repo;
    private ProducedBriefLocator $locator;

    protected function setUp(): void
    {
        $this->fs = new Filesystem();
        $this->repo = sys_get_temp_dir() . '/brief-locator-' . bin2hex(random_bytes(6));
        $this->fs->mkdir($this->repo);
        $this->git('init', '-q');
        $this->git('config', 'user.email', 'test@example.com');
        $this->git('config', 'user.name', 'Test');

        // Un commit initial pour que le clone ne soit pas « vierge » : le brief détecté doit
        // ressortir comme non suivi par rapport à un état propre.
        $this->fs->dumpFile($this->repo . '/README.md', "# repo\n");
        $this->git('add', '.');
        $this->git('commit', '-q', '-m', 'init');

        $this->locator = new ProducedBriefLocator();
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->repo);
    }

    public function testReturnsNullWhenNoBriefProduced(): void
    {
        self::assertNull($this->locator->locate($this->repo));
    }

    public function testDetectsUntrackedBriefAndReturnsSlug(): void
    {
        $this->fs->dumpFile($this->repo . '/docs/story/010-f-export-factures/brief.md', "# Brief\n");

        self::assertSame('010-f-export-factures', $this->locator->locate($this->repo));
    }

    public function testDetectsBriefEvenWhenSiblingFilesExistInTheStoryFolder(): void
    {
        $this->fs->dumpFile($this->repo . '/docs/story/011-f-relances/metadata.json', "{}\n");
        $this->fs->dumpFile($this->repo . '/docs/story/011-f-relances/brief.md', "# Brief\n");

        self::assertSame('011-f-relances', $this->locator->locate($this->repo));
    }

    public function testIgnoresStoryFolderWithoutBrief(): void
    {
        // Un dossier de story non suivi mais sans brief.md ne doit pas être détecté.
        $this->fs->dumpFile($this->repo . '/docs/story/012-f-orphan/metadata.json', "{}\n");

        self::assertNull($this->locator->locate($this->repo));
    }

    public function testThrowsWhenDirectoryIsNotAGitRepository(): void
    {
        $notARepo = sys_get_temp_dir() . '/not-a-repo-' . bin2hex(random_bytes(6));
        $this->fs->mkdir($notARepo);

        try {
            $this->expectException(InterviewFailedException::class);
            $this->locator->locate($notARepo);
        } finally {
            $this->fs->remove($notARepo);
        }
    }

    private function git(string ...$args): void
    {
        $process = new Process(['git', '-C', $this->repo, ...$args]);
        $process->mustRun();
    }
}
