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

    public function testReadFileReturnsRawContent(): void
    {
        $raw = "# Titre\n\nCorps du document.\n";
        $content = $this->readFile(new MockHttpClient([
            new MockResponse($raw),
        ], 'https://api.github.com'));

        self::assertSame($raw, $content);
    }

    public function testReadFileRequestsRawAcceptAndBearerNeverInUrl(): void
    {
        $response = new MockResponse('body');
        $this->readFile(new MockHttpClient([$response], 'https://api.github.com'), 'ghp_raw_secret');

        $rawHeaders = $response->getRequestOptions()['headers'] ?? [];
        self::assertIsArray($rawHeaders);
        $headers = implode("\n", array_filter($rawHeaders, 'is_string'));

        self::assertStringContainsString('Accept: application/vnd.github.raw', $headers);
        self::assertStringContainsString('Authorization: Bearer ghp_raw_secret', $headers);
        self::assertStringNotContainsString('ghp_raw_secret', $response->getRequestUrl());
    }

    public function testReadFileMissingIsUnreachable(): void
    {
        $this->expectException(RepositoryUnreachableException::class);

        $this->readFile(new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ], 'https://api.github.com'));
    }

    public function testReadFileUnauthorizedThrowsAccessDenied(): void
    {
        $this->expectException(RepositoryAccessDeniedException::class);

        $this->readFile(new MockHttpClient([
            new MockResponse('', ['http_code' => 401]),
        ], 'https://api.github.com'));
    }

    public function testReadFileForbiddenThrowsAccessDenied(): void
    {
        $this->expectException(RepositoryAccessDeniedException::class);

        $this->readFile(new MockHttpClient([
            new MockResponse('', ['http_code' => 403]),
        ], 'https://api.github.com'));
    }

    public function testReadFileRateLimitedIsUnreachable(): void
    {
        $this->expectException(RepositoryUnreachableException::class);

        $this->readFile(new MockHttpClient([
            new MockResponse('', [
                'http_code' => 403,
                'response_headers' => ['x-ratelimit-remaining' => '0'],
            ]),
        ], 'https://api.github.com'));
    }

    public function testReadFileTransportErrorIsUnreachable(): void
    {
        $this->expectException(RepositoryUnreachableException::class);

        $this->readFile(new MockHttpClient(static fn (): never => throw new TransportException('timeout')));
    }

    public function testReadStoryMetadataMapsEachStoryInASingleGraphqlCall(): void
    {
        $client = new MockHttpClient([
            new JsonMockResponse(['data' => ['repository' => [
                's0' => ['text' => '{"version":1,"title":"A"}'],
                's1' => null, // story sans metadata.json → null
                's2' => ['text' => '{"version":1,"title":"C"}'],
            ]]]),
        ], 'https://api.github.com');

        $reader = new GitHubRepositoryReader($client);
        $metadata = $reader->readStoryMetadata($this->url(), 'ghp_token', ['001-f-a', '002-f-b', '003-f-c']);

        // Un seul appel réseau, quel que soit le nombre de stories (règle 10).
        self::assertSame(1, $client->getRequestsCount());
        self::assertSame('{"version":1,"title":"A"}', $metadata['001-f-a']);
        self::assertNull($metadata['002-f-b']);
        self::assertSame('{"version":1,"title":"C"}', $metadata['003-f-c']);
    }

    public function testReadStoryMetadataWithoutStoriesMakesNoCall(): void
    {
        $client = new MockHttpClient([], 'https://api.github.com');
        $reader = new GitHubRepositoryReader($client);

        self::assertSame([], $reader->readStoryMetadata($this->url(), 'ghp_token', []));
        self::assertSame(0, $client->getRequestsCount());
    }

    public function testReadStoryMetadataPostsGraphqlWithBearerNeverInUrl(): void
    {
        $response = new JsonMockResponse(['data' => ['repository' => ['s0' => null]]]);
        $reader = new GitHubRepositoryReader(new MockHttpClient([$response], 'https://api.github.com'));
        $reader->readStoryMetadata($this->url(), 'ghp_gql_secret', ['001-f-a']);

        self::assertSame('POST', $response->getRequestMethod());
        self::assertStringEndsWith('/graphql', $response->getRequestUrl());

        $rawHeaders = $response->getRequestOptions()['headers'] ?? [];
        self::assertIsArray($rawHeaders);
        $headers = implode("\n", array_filter($rawHeaders, 'is_string'));
        self::assertStringContainsString('Authorization: Bearer ghp_gql_secret', $headers);
        self::assertStringNotContainsString('ghp_gql_secret', $response->getRequestUrl());
    }

    public function testReadStoryMetadataUnauthorizedThrowsAccessDenied(): void
    {
        $this->expectException(RepositoryAccessDeniedException::class);

        $reader = new GitHubRepositoryReader(new MockHttpClient([
            new MockResponse('', ['http_code' => 401]),
        ], 'https://api.github.com'));
        $reader->readStoryMetadata($this->url(), 'ghp_token', ['001-f-a']);
    }

    public function testReadStoryMetadataTransportErrorIsUnreachable(): void
    {
        $this->expectException(RepositoryUnreachableException::class);

        $reader = new GitHubRepositoryReader(new MockHttpClient(static fn (): never => throw new TransportException('timeout')));
        $reader->readStoryMetadata($this->url(), 'ghp_token', ['001-f-a']);
    }

    public function testReadStoryMetadataGraphqlRateLimitIsUnreachable(): void
    {
        // GraphQL signale le quota par un HTTP 200 + errors[].type = RATE_LIMITED (pas un 403).
        $this->expectException(RepositoryUnreachableException::class);
        $this->expectExceptionMessage('Quota GitHub dépassé.');

        $reader = new GitHubRepositoryReader(new MockHttpClient([
            new JsonMockResponse([
                'data' => null,
                'errors' => [['type' => 'RATE_LIMITED', 'message' => 'API rate limit exceeded']],
            ]),
        ], 'https://api.github.com'));
        $reader->readStoryMetadata($this->url(), 'ghp_token', ['001-f-a']);
    }

    public function testReadStoryMetadataToleratesPartialGraphqlErrors(): void
    {
        // Une erreur GraphQL non-quota (ex. NOT_FOUND sur un alias) ne casse pas la lecture :
        // le champ concerné vaut null, la carte dégrade (fidélité, règle 9).
        $reader = new GitHubRepositoryReader(new MockHttpClient([
            new JsonMockResponse([
                'data' => ['repository' => ['s0' => ['text' => '{"version":1,"title":"A"}'], 's1' => null]],
                'errors' => [['type' => 'NOT_FOUND', 'message' => 'Could not resolve']],
            ]),
        ], 'https://api.github.com'));

        $metadata = $reader->readStoryMetadata($this->url(), 'ghp_token', ['001-f-a', '002-f-b']);

        self::assertSame('{"version":1,"title":"A"}', $metadata['001-f-a']);
        self::assertNull($metadata['002-f-b']);
    }

    private function url(): RepositoryUrl
    {
        return new RepositoryUrl(Provider::GitHub, self::OWNER, self::REPO, 'https://github.com/' . self::OWNER . '/' . self::REPO);
    }

    private function read(MockHttpClient $client, string $token = 'ghp_token'): \App\Service\Github\StoryTree
    {
        $reader = new GitHubRepositoryReader($client);
        $url = new RepositoryUrl(Provider::GitHub, self::OWNER, self::REPO, 'https://github.com/' . self::OWNER . '/' . self::REPO);

        return $reader->readStoryTree($url, $token);
    }

    private function readFile(MockHttpClient $client, string $token = 'ghp_token'): string
    {
        $reader = new GitHubRepositoryReader($client);
        $url = new RepositoryUrl(Provider::GitHub, self::OWNER, self::REPO, 'https://github.com/' . self::OWNER . '/' . self::REPO);

        return $reader->readFile($url, $token, 'docs/story/005-f-kanban-projet/pitch.md');
    }
}
