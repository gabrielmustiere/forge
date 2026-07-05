<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Board;

use App\Entity\Project;
use App\Enum\Type\PipelineStage;
use App\Enum\Type\Provider;
use App\Enum\Type\Track;
use App\Service\Board\ProjectBoardBuilder;
use App\Service\Github\StoryFolder;
use App\Service\Github\StoryTree;
use App\Service\Mapping\StoryStageMapper;
use App\Service\Repository\RepositoryAccessDeniedException;
use App\Service\Repository\RepositoryReaderInterface;
use App\Service\Repository\RepositoryReaderRegistry;
use App\Service\Repository\RepositoryUnreachableException;
use App\Service\RepositoryUrlNormalizer;
use App\Service\TokenCipher;
use PHPUnit\Framework\TestCase;

final class ProjectBoardBuilderTest extends TestCase
{
    private TokenCipher $cipher;

    protected function setUp(): void
    {
        $this->cipher = new TokenCipher('test-secret-for-board-builder');
    }

    public function testGroupsStoriesIntoColumnsAndBanner(): void
    {
        $result = $this->buildFrom(new StoryTree([
            new StoryFolder('001-f-cadrage', ['pitch.md']),
            new StoryFolder('010-f-planifie', ['pitch.md', 'plan.md']),
            new StoryFolder('005-r-review', ['plan.md', 'review.md']),
            new StoryFolder('007-t-livre', ['report.md']),
            new StoryFolder('003-f-livre-aussi', ['pitch.md', 'report.md']),
            new StoryFolder('002-f-mystere', ['readme.md']),
        ]));

        self::assertTrue($result->isSuccess());
        $board = $result->board;
        self::assertNotNull($board);

        self::assertSame(1, $board->countFor(PipelineStage::Cadrage));
        self::assertSame('001-f-cadrage', $board->cardsFor(PipelineStage::Cadrage)[0]->id->value);

        self::assertSame(1, $board->countFor(PipelineStage::Planifie));
        self::assertSame(1, $board->countFor(PipelineStage::Review));
        self::assertSame(Track::Refacto, $board->cardsFor(PipelineStage::Review)[0]->id->track);

        // report.md l'emporte pour les deux cartes Livré ; tri par numéro décroissant.
        self::assertSame(2, $board->countFor(PipelineStage::Livre));
        self::assertSame(
            ['007-t-livre', '003-f-livre-aussi'],
            array_map(static fn ($c) => $c->id->value, $board->cardsFor(PipelineStage::Livre)),
        );

        // La story sans fichier de pipeline atterrit dans le bandeau, pas dans une colonne.
        self::assertSame(1, $board->bannerCount());
        self::assertSame('002-f-mystere', $board->banner()[0]->id->value);
        self::assertFalse($board->isEmpty());
    }

    public function testRefactoStoryNeverLandsInCadrage(): void
    {
        // Convention forge : une refacto démarre à plan.md (pas de pitch) → jamais Cadrage.
        $result = $this->buildFrom(new StoryTree([
            new StoryFolder('004-r-refonte', ['plan.md']),
        ]));

        $board = $result->board;
        self::assertNotNull($board);
        self::assertSame(0, $board->countFor(PipelineStage::Cadrage));
        self::assertSame(1, $board->countFor(PipelineStage::Planifie));
    }

    public function testDocumentsAreOrderedForTheDrawer(): void
    {
        $result = $this->buildFrom(new StoryTree([
            new StoryFolder('001-f-x', ['notes.md', 'pitch.md', 'plan.md', 'report.md', 'review.md', 'diagram.png']),
        ]));

        $board = $result->board;
        self::assertNotNull($board);
        // Précédence forge d'abord, transversaux `.md` ensuite (alpha), non-`.md` exclus.
        self::assertSame(
            ['report.md', 'review.md', 'plan.md', 'pitch.md', 'notes.md'],
            $board->cardsFor(PipelineStage::Livre)[0]->documents,
        );
    }

    public function testDocumentsExcludeNamesNotServableByTheRoute(): void
    {
        // README.md / UPPER.md / sous-chemin / non-.md : hors du charset de la route
        // `app_project_story_doc` → exclus, sinon la génération d'URL casserait le board.
        $result = $this->buildFrom(new StoryTree([
            new StoryFolder('001-f-x', ['README.md', 'UPPER.md', 'sub/nested.md', 'diagram.png', 'pitch.md', 'annexe.md']),
        ]));

        $board = $result->board;
        self::assertNotNull($board);
        self::assertSame(
            ['pitch.md', 'annexe.md'],
            $board->cardsFor(PipelineStage::Cadrage)[0]->documents,
        );
    }

    public function testEmptyTreeYieldsAnEmptyBoard(): void
    {
        $result = $this->buildFrom(new StoryTree([]));

        self::assertTrue($result->isSuccess());
        self::assertNotNull($result->board);
        self::assertTrue($result->board->isEmpty());
    }

    public function testAccessDeniedBecomesAFailure(): void
    {
        $result = $this->buildFromException(new RepositoryAccessDeniedException('nope'));

        self::assertFalse($result->isSuccess());
        self::assertNull($result->board);
        self::assertNotNull($result->failureReason);
    }

    public function testUnreachableBecomesAFailure(): void
    {
        $result = $this->buildFromException(new RepositoryUnreachableException('offline'));

        self::assertFalse($result->isSuccess());
        self::assertNotNull($result->failureReason);
    }

    private function buildFrom(StoryTree $tree): \App\Service\Board\BoardResult
    {
        $reader = $this->createStub(RepositoryReaderInterface::class);
        $reader->method('supports')->willReturn(true);
        $reader->method('readStoryTree')->willReturn($tree);

        return $this->builderWith($reader)->build($this->project());
    }

    private function buildFromException(\Throwable $exception): \App\Service\Board\BoardResult
    {
        $reader = $this->createStub(RepositoryReaderInterface::class);
        $reader->method('supports')->willReturn(true);
        $reader->method('readStoryTree')->willThrowException($exception);

        return $this->builderWith($reader)->build($this->project());
    }

    private function builderWith(RepositoryReaderInterface $reader): ProjectBoardBuilder
    {
        return new ProjectBoardBuilder(
            new RepositoryReaderRegistry([$reader]),
            new RepositoryUrlNormalizer(),
            $this->cipher,
            new StoryStageMapper(),
        );
    }

    private function project(): Project
    {
        return new Project(
            Provider::GitHub,
            'https://github.com/acme/widget',
            'acme/widget',
            $this->cipher->encrypt('ghp_token'),
        );
    }
}
