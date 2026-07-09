<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Github;

use App\Enum\Type\Provider;
use App\Service\Github\GitHubPullRequestOpener;
use App\Service\Github\PullRequestFailedException;
use App\Service\RepositoryUrl;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Aucun appel réseau réel : {@see MockHttpClient} rejoue les réponses GitHub. Le premier appel
 * (`GET /repos/...`) résout la branche de base, le second (`POST .../pulls`) ouvre la PR draft.
 */
final class GitHubPullRequestOpenerTest extends TestCase
{
    private function url(): RepositoryUrl
    {
        return new RepositoryUrl(Provider::GitHub, 'acme', 'repo', 'https://github.com/acme/repo');
    }

    public function testOpensDraftPullRequestAndReturnsHtmlUrl(): void
    {
        $captured = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured): ResponseInterface {
            $captured[] = [$method, $url, $options];

            if (str_ends_with($url, '/repos/acme/repo')) {
                return new JsonMockResponse(['default_branch' => 'main']);
            }

            return new JsonMockResponse(['html_url' => 'https://github.com/acme/repo/pull/42'], ['http_code' => 201]);
        }, 'https://api.github.com');

        $opener = new GitHubPullRequestOpener($client);

        $urlResult = $opener->open($this->url(), 'ghp_write', 'forge/010-f-export', 'Cadrage : export', 'Corps du brief');

        self::assertSame('https://github.com/acme/repo/pull/42', $urlResult);

        // Deux appels : GET base branch puis POST pulls avec draft: true et head/base attendus.
        self::assertCount(2, $captured);
        [$method, $postUrl, $options] = $captured[1];
        self::assertSame('POST', $method);
        self::assertStringEndsWith('/repos/acme/repo/pulls', $postUrl);
        self::assertIsString($options['body']);
        $payload = json_decode($options['body'], true);
        self::assertIsArray($payload);
        self::assertTrue($payload['draft']);
        self::assertSame('forge/010-f-export', $payload['head']);
        self::assertSame('main', $payload['base']);
        self::assertSame('Cadrage : export', $payload['title']);
    }

    public function testReadOnlyTokenIsRejectedWithAWritableMessage(): void
    {
        $client = new MockHttpClient(function (string $method, string $url): ResponseInterface {
            if (str_ends_with($url, '/repos/acme/repo')) {
                return new JsonMockResponse(['default_branch' => 'main']);
            }

            return new MockResponse('{"message":"Resource not accessible by personal access token"}', ['http_code' => 403]);
        }, 'https://api.github.com');

        $opener = new GitHubPullRequestOpener($client);

        $this->expectException(PullRequestFailedException::class);
        $this->expectExceptionMessageMatches('/droit d\'écriture/');
        $opener->open($this->url(), 'ghp_readonly', 'forge/010-f-export', 'Titre', 'Corps');
    }

    public function testUnauthorizedTokenIsRejected(): void
    {
        $client = new MockHttpClient(fn (): ResponseInterface => new MockResponse('{"message":"Bad credentials"}', ['http_code' => 401]), 'https://api.github.com');

        $opener = new GitHubPullRequestOpener($client);

        $this->expectException(PullRequestFailedException::class);
        $this->expectExceptionMessageMatches('/refusé/');
        $opener->open($this->url(), 'ghp_invalid', 'forge/010-f-export', 'Titre', 'Corps');
    }

    public function testRateLimitIsReportedAsQuota(): void
    {
        $client = new MockHttpClient(fn (): ResponseInterface => new MockResponse('{}', [
            'http_code' => 403,
            'response_headers' => ['x-ratelimit-remaining' => '0'],
        ]), 'https://api.github.com');

        $opener = new GitHubPullRequestOpener($client);

        $this->expectException(PullRequestFailedException::class);
        $this->expectExceptionMessageMatches('/[Qq]uota/');
        $opener->open($this->url(), 'ghp_write', 'forge/010-f-export', 'Titre', 'Corps');
    }

    public function testUnprocessableEntitySurfacesTheGitHubReason(): void
    {
        $client = new MockHttpClient(function (string $method, string $url): ResponseInterface {
            if (str_ends_with($url, '/repos/acme/repo')) {
                return new JsonMockResponse(['default_branch' => 'main']);
            }

            return new JsonMockResponse([
                'message' => 'Validation Failed',
                'errors' => [['message' => 'No commits between main and forge/010-f-export']],
            ], ['http_code' => 422]);
        }, 'https://api.github.com');

        $opener = new GitHubPullRequestOpener($client);

        $this->expectException(PullRequestFailedException::class);
        $this->expectExceptionMessageMatches('/No commits between/');
        $opener->open($this->url(), 'ghp_write', 'forge/010-f-export', 'Titre', 'Corps');
    }
}
