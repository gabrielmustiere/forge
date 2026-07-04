<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Github;

use App\Enum\Type\Provider;
use App\Service\Github\GitHubRepositoryReader;
use App\Service\Repository\RepositoryAccessDeniedException;
use App\Service\Repository\RepositoryUnreachableException;
use App\Service\RepositoryUrl;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GitHubRepositoryReaderTest extends TestCase
{
    private const OWNER = 'acme';
    private const REPO = 'widget';

    public function testSupportsOnlyGitHub(): void
    {
        $reader = new GitHubRepositoryReader(new MockHttpClient());

        self::assertTrue($reader->supports(Provider::GitHub));
        self::assertFalse($reader->supports(Provider::GitLab));
    }

    public function testReadsAConformingStoryTree(): void
    {
        $tree = $this->read(new MockHttpClient([
            new JsonMockResponse(['default_branch' => 'main']),
            new JsonMockResponse([
                ['name' => 'story', 'type' => 'dir', 'sha' => 'story-sha'],
                ['name' => 'vision.md', 'type' => 'file', 'sha' => 'x'],
            ]),
            new JsonMockResponse([
                'truncated' => false,
                'tree' => [
                    ['path' => '001-f-connecteur', 'type' => 'tree'],
                    ['path' => '001-f-connecteur/pitch.md', 'type' => 'blob'],
                    ['path' => '001-f-connecteur/plan.md', 'type' => 'blob'],
                ],
            ]),
        ], 'https://api.github.com'));

        self::assertTrue($tree->hasStories());
        self::assertSame('001-f-connecteur', $tree->stories[0]->id);
        self::assertSame(['pitch.md', 'plan.md'], $tree->stories[0]->files());
    }

    public function testPassesTheTokenAsBearerNeverInTheUrl(): void
    {
        $repoResponse = new JsonMockResponse(['default_branch' => 'main']);
        $this->read(new MockHttpClient([
            $repoResponse,
            new JsonMockResponse([]),
        ], 'https://api.github.com'), 'ghp_secret_value');

        $rawHeaders = $repoResponse->getRequestOptions()['headers'] ?? [];
        self::assertIsArray($rawHeaders);
        $headers = implode("\n", array_filter($rawHeaders, 'is_string'));

        self::assertStringContainsString('Authorization: Bearer ghp_secret_value', $headers);
        self::assertStringNotContainsString('ghp_secret_value', $repoResponse->getRequestUrl());
    }

    public function testRepoWithoutDocsStoryIsNotForge(): void
    {
        // contents/docs répond 404 → pas de docs → arbre vide.
        $tree = $this->read(new MockHttpClient([
            new JsonMockResponse(['default_branch' => 'main']),
            new MockResponse('', ['http_code' => 404]),
        ], 'https://api.github.com'));

        self::assertFalse($tree->hasStories());
    }

    public function testDocsWithoutStoryFolderIsNotForge(): void
    {
        $tree = $this->read(new MockHttpClient([
            new JsonMockResponse(['default_branch' => 'main']),
            new JsonMockResponse([
                ['name' => 'vision.md', 'type' => 'file', 'sha' => 'x'],
            ]),
        ], 'https://api.github.com'));

        self::assertFalse($tree->hasStories());
    }

    public function testUnauthorizedRepoThrowsAccessDenied(): void
    {
        $this->expectException(RepositoryAccessDeniedException::class);

        $this->read(new MockHttpClient([
            new MockResponse('', ['http_code' => 401]),
        ], 'https://api.github.com'));
    }

    public function testForbiddenRepoThrowsAccessDenied(): void
    {
        $this->expectException(RepositoryAccessDeniedException::class);

        $this->read(new MockHttpClient([
            new MockResponse('', ['http_code' => 403]),
        ], 'https://api.github.com'));
    }

    public function testRateLimitedRepoIsUnreachable(): void
    {
        $this->expectException(RepositoryUnreachableException::class);

        $this->read(new MockHttpClient([
            new MockResponse('', [
                'http_code' => 403,
                'response_headers' => ['x-ratelimit-remaining' => '0'],
            ]),
        ], 'https://api.github.com'));
    }

    public function testMissingRepoIsUnreachable(): void
    {
        $this->expectException(RepositoryUnreachableException::class);

        $this->read(new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ], 'https://api.github.com'));
    }

    public function testTransportErrorIsUnreachable(): void
    {
        $this->expectException(RepositoryUnreachableException::class);

        $this->read(new MockHttpClient(static fn (): never => throw new TransportException('timeout')));
    }

    public function testTruncatedTreeStillYieldsVisibleStories(): void
    {
        $tree = $this->read(new MockHttpClient([
            new JsonMockResponse(['default_branch' => 'main']),
            new JsonMockResponse([
                ['name' => 'story', 'type' => 'dir', 'sha' => 'story-sha'],
            ]),
            new JsonMockResponse([
                'truncated' => true,
                'tree' => [
                    ['path' => '001-f-visible/plan.md', 'type' => 'blob'],
                ],
            ]),
        ], 'https://api.github.com'));

        self::assertTrue($tree->hasStories());
    }

    private function read(MockHttpClient $client, string $token = 'ghp_token'): \App\Service\Github\StoryTree
    {
        $reader = new GitHubRepositoryReader($client);
        $url = new RepositoryUrl(Provider::GitHub, self::OWNER, self::REPO, 'https://github.com/' . self::OWNER . '/' . self::REPO);

        return $reader->readStoryTree($url, $token);
    }
}
