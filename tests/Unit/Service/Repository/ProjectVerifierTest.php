<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Repository;

use App\Entity\Project;
use App\Enum\Type\Provider;
use App\Enum\Type\VerificationStatus;
use App\Service\Github\StoryFolder;
use App\Service\Github\StoryTree;
use App\Service\Repository\ProjectVerifier;
use App\Service\Repository\RepositoryAccessDeniedException;
use App\Service\Repository\RepositoryReaderInterface;
use App\Service\Repository\RepositoryReaderRegistry;
use App\Service\Repository\RepositoryUnreachableException;
use App\Service\RepositoryUrlNormalizer;
use App\Service\TokenCipher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class ProjectVerifierTest extends TestCase
{
    private TokenCipher $cipher;
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->cipher = new TokenCipher('unit-test-secret');
        $this->clock = new MockClock('2026-07-04 10:00:00');
    }

    public function testEligibleWhenTheRepoExposesStories(): void
    {
        $reader = $this->readerReturning(new StoryTree([new StoryFolder('001-f-x', ['plan.md'])]));

        $result = $this->verifier($reader)->verify($this->gitHubProject());

        self::assertSame(VerificationStatus::Eligible, $result->status);
        self::assertEquals($this->clock->now(), $result->verifiedAt);
    }

    public function testNotForgeWhenTheRepoHasNoStory(): void
    {
        $reader = $this->readerReturning(new StoryTree([]));

        $result = $this->verifier($reader)->verify($this->gitHubProject());

        self::assertSame(VerificationStatus::NotForge, $result->status);
    }

    public function testInvalidTokenWhenAccessIsDenied(): void
    {
        $reader = $this->readerThrowing(new RepositoryAccessDeniedException('denied'));

        $result = $this->verifier($reader)->verify($this->gitHubProject());

        self::assertSame(VerificationStatus::InvalidToken, $result->status);
    }

    public function testUnreachableWhenTheRepoCannotBeReached(): void
    {
        $reader = $this->readerThrowing(new RepositoryUnreachableException('offline'));

        $result = $this->verifier($reader)->verify($this->gitHubProject());

        self::assertSame(VerificationStatus::Unreachable, $result->status);
    }

    public function testGitLabIsUnsupportedWithoutTouchingAnyReader(): void
    {
        // Le reader GitHub ne doit jamais être invoqué pour un projet GitLab.
        $reader = $this->createMock(RepositoryReaderInterface::class);
        $reader->method('supports')->willReturnCallback(
            static fn (Provider $provider): bool => Provider::GitHub === $provider,
        );
        $reader->expects(self::never())->method('readStoryTree');

        $project = new Project(Provider::GitLab, 'https://gitlab.com/acme/widget', 'acme/widget', $this->cipher->encrypt('t'));

        $result = $this->verifier($reader)->verify($project);

        self::assertSame(VerificationStatus::UnsupportedProvider, $result->status);
    }

    private function verifier(RepositoryReaderInterface $reader): ProjectVerifier
    {
        return new ProjectVerifier(
            new RepositoryReaderRegistry([$reader]),
            new RepositoryUrlNormalizer(),
            $this->cipher,
            $this->clock,
        );
    }

    private function gitHubProject(): Project
    {
        return new Project(Provider::GitHub, 'https://github.com/acme/widget', 'acme/widget', $this->cipher->encrypt('ghp_token'));
    }

    private function readerReturning(StoryTree $tree): RepositoryReaderInterface
    {
        $reader = $this->createStub(RepositoryReaderInterface::class);
        $reader->method('supports')->willReturnCallback(
            static fn (Provider $provider): bool => Provider::GitHub === $provider,
        );
        $reader->method('readStoryTree')->willReturn($tree);

        return $reader;
    }

    private function readerThrowing(\Throwable $exception): RepositoryReaderInterface
    {
        $reader = $this->createStub(RepositoryReaderInterface::class);
        $reader->method('supports')->willReturnCallback(
            static fn (Provider $provider): bool => Provider::GitHub === $provider,
        );
        $reader->method('readStoryTree')->willThrowException($exception);

        return $reader;
    }
}
