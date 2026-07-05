<?php

declare(strict_types=1);

namespace App\Service\Github;

use App\Enum\Type\Provider;
use App\Service\Repository\RepositoryAccessDeniedException;
use App\Service\Repository\RepositoryReaderInterface;
use App\Service\Repository\RepositoryUnreachableException;
use App\Service\RepositoryUrl;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Lit l'arborescence `docs/story/` d'un dépôt GitHub via l'API REST, en trois appels
 * bornés au sous-arbre `docs/story` (jamais l'arbre entier du dépôt) :
 *
 *  1. `GET /repos/{owner}/{repo}` → branche par défaut (valide aussi existence + accès) ;
 *  2. `GET /repos/{owner}/{repo}/contents/docs?ref={branch}` → tree SHA du dossier `story` ;
 *  3. `GET /repos/{owner}/{repo}/git/trees/{sha}?recursive=1` → sous-arbre `docs/story`.
 *
 * Le token en clair n'est utilisé qu'en `auth_bearer`, jamais dans l'URL ni loggé.
 */
final readonly class GitHubRepositoryReader implements RepositoryReaderInterface
{
    public function __construct(
        #[Autowire(service: 'github.client')]
        private HttpClientInterface $client,
    ) {
    }

    public function supports(Provider $provider): bool
    {
        return Provider::GitHub === $provider;
    }

    public function readStoryTree(RepositoryUrl $url, string $plainToken): StoryTree
    {
        $repo = $url->owner . '/' . $url->repo;

        // 1. La branche par défaut ; un 404 ici = dépôt introuvable → injoignable.
        $repoData = $this->get('/repos/' . $repo, $plainToken);
        if (null === $repoData || !\is_string($repoData['default_branch'] ?? null)) {
            throw new RepositoryUnreachableException(sprintf('Dépôt GitHub « %s » introuvable ou illisible.', $repo));
        }
        $branch = $repoData['default_branch'];

        // 2. Le tree SHA de docs/story ; docs ou story absent = non-forge (arbre vide).
        $storySha = $this->locateStoryTreeSha($repo, $branch, $plainToken);
        if (null === $storySha) {
            return new StoryTree([]);
        }

        // 3. Le sous-arbre docs/story, récursif mais borné (troncature quasi impossible).
        $treeData = $this->get('/repos/' . $repo . '/git/trees/' . $storySha, $plainToken, ['recursive' => '1']);
        if (null === $treeData) {
            return new StoryTree([]);
        }

        return StoryTree::fromTreeEntries($this->normalizeTreeEntries($treeData['tree'] ?? null));
    }

    public function readFile(RepositoryUrl $url, string $plainToken, string $path): string
    {
        $repo = $url->owner . '/' . $url->repo;

        try {
            // `Accept: raw` renvoie le contenu du fichier tel quel, jamais le wrapper JSON base64.
            $response = $this->client->request('GET', '/repos/' . $repo . '/contents/' . $path, [
                'auth_bearer' => $plainToken,
                'headers' => ['Accept' => 'application/vnd.github.raw'],
            ]);

            $status = $response->getStatusCode();

            if (404 === $status) {
                throw new RepositoryUnreachableException(sprintf('Fichier GitHub « %s » introuvable dans « %s ».', $path, $repo));
            }

            if (401 === $status || 403 === $status) {
                if (403 === $status && $this->isRateLimited($response->getHeaders(false))) {
                    throw new RepositoryUnreachableException('Quota GitHub dépassé.');
                }

                throw new RepositoryAccessDeniedException('Accès GitHub refusé (token invalide ou insuffisant).');
            }

            if ($status < 200 || $status >= 300) {
                throw new RepositoryUnreachableException(sprintf('Réponse GitHub inattendue (HTTP %d).', $status));
            }

            return $response->getContent();
        } catch (TransportExceptionInterface $e) {
            throw new RepositoryUnreachableException('Dépôt GitHub injoignable (réseau ou timeout).', 0, $e);
        } catch (ExceptionInterface $e) {
            throw new RepositoryUnreachableException('Réponse GitHub illisible.', 0, $e);
        }
    }

    /**
     * @return ?non-empty-string SHA du sous-arbre `docs/story`, ou null si `docs` ou `story` est absent
     */
    private function locateStoryTreeSha(string $repo, string $branch, string $plainToken): ?string
    {
        $docs = $this->get('/repos/' . $repo . '/contents/docs', $plainToken, ['ref' => $branch]);
        if (null === $docs) {
            return null;
        }

        foreach ($docs as $entry) {
            if (\is_array($entry)
                && 'story' === ($entry['name'] ?? null)
                && 'dir' === ($entry['type'] ?? null)
                && \is_string($entry['sha'] ?? null)
                && '' !== $entry['sha']
            ) {
                return $entry['sha'];
            }
        }

        return null;
    }

    /**
     * Requête GET authentifiée. Renvoie le corps décodé, ou `null` sur un 404.
     *
     * @param array<string, string> $query
     *
     * @return array<mixed>|null
     *
     * @throws RepositoryAccessDeniedException 401/403 hors quota
     * @throws RepositoryUnreachableException  404 non pertinent traité côté appelant, 5xx, réseau, timeout, quota
     */
    private function get(string $path, string $plainToken, array $query = []): ?array
    {
        try {
            $response = $this->client->request('GET', $path, [
                'auth_bearer' => $plainToken,
                'query' => $query,
            ]);

            $status = $response->getStatusCode();

            if (404 === $status) {
                return null;
            }

            if (401 === $status || 403 === $status) {
                if (403 === $status && $this->isRateLimited($response->getHeaders(false))) {
                    throw new RepositoryUnreachableException('Quota GitHub dépassé.');
                }

                throw new RepositoryAccessDeniedException('Accès GitHub refusé (token invalide ou insuffisant).');
            }

            if ($status < 200 || $status >= 300) {
                throw new RepositoryUnreachableException(sprintf('Réponse GitHub inattendue (HTTP %d).', $status));
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new RepositoryUnreachableException('Dépôt GitHub injoignable (réseau ou timeout).', 0, $e);
        } catch (ExceptionInterface $e) {
            throw new RepositoryUnreachableException('Réponse GitHub illisible.', 0, $e);
        }
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function isRateLimited(array $headers): bool
    {
        return '0' === ($headers['x-ratelimit-remaining'][0] ?? null);
    }

    /**
     * Ne garde que les entrées exploitables (`path` string + `type` string) de l'arbre Git.
     *
     * @return list<array{path: string, type: string}>
     */
    private function normalizeTreeEntries(mixed $tree): array
    {
        if (!\is_array($tree)) {
            return [];
        }

        $entries = [];
        foreach ($tree as $entry) {
            if (\is_array($entry) && \is_string($entry['path'] ?? null) && \is_string($entry['type'] ?? null)) {
                $entries[] = ['path' => $entry['path'], 'type' => $entry['type']];
            }
        }

        return $entries;
    }
}
