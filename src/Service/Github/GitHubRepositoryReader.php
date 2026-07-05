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
 *
 * La lecture groupée des `metadata.json` ({@see readStoryMetadata}) emprunte l'API **GraphQL**
 * (`POST /graphql`) : un alias `object(expression:)` par story dans une requête unique, donc
 * un nombre d'appels constant quel que soit le nombre de stories (règle 10). Le reader reste
 * bi-protocole mais le GraphQL est cloisonné à cette seule méthode, avec les mêmes exceptions
 * métier que le versant REST.
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

            $this->guardStatus($status, $response->getHeaders(false));

            return $response->getContent();
        } catch (TransportExceptionInterface $e) {
            throw new RepositoryUnreachableException('Dépôt GitHub injoignable (réseau ou timeout).', 0, $e);
        } catch (ExceptionInterface $e) {
            throw new RepositoryUnreachableException('Réponse GitHub illisible.', 0, $e);
        }
    }

    public function readStoryMetadata(RepositoryUrl $url, string $plainToken, array $storyIds): array
    {
        if ([] === $storyIds) {
            return [];
        }

        // Un alias GraphQL par story (`s0`, `s1`… — jamais l'identifiant brut, qui contient des
        // tirets interdits en nom de champ GraphQL). On garde la correspondance alias → storyId.
        $aliasToStory = [];
        $fields = [];
        foreach ($storyIds as $i => $storyId) {
            $alias = 's' . $i;
            $aliasToStory[$alias] = $storyId;
            $expression = json_encode(sprintf('HEAD:docs/story/%s/metadata.json', $storyId), \JSON_THROW_ON_ERROR);
            $fields[] = sprintf('%s: object(expression: %s) { ... on Blob { text } }', $alias, $expression);
        }

        $query = sprintf(
            'query { repository(owner: %s, name: %s) { %s } }',
            json_encode($url->owner, \JSON_THROW_ON_ERROR),
            json_encode($url->repo, \JSON_THROW_ON_ERROR),
            implode(' ', $fields),
        );

        $payload = $this->graphql($query, $plainToken);
        $data = \is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $repository = \is_array($data['repository'] ?? null) ? $data['repository'] : [];

        $result = [];
        foreach ($aliasToStory as $alias => $storyId) {
            $node = $repository[$alias] ?? null;
            $text = \is_array($node) ? ($node['text'] ?? null) : null;
            $result[$storyId] = \is_string($text) ? $text : null;
        }

        return $result;
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

            $this->guardStatus($status, $response->getHeaders(false));

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new RepositoryUnreachableException('Dépôt GitHub injoignable (réseau ou timeout).', 0, $e);
        } catch (ExceptionInterface $e) {
            throw new RepositoryUnreachableException('Réponse GitHub illisible.', 0, $e);
        }
    }

    /**
     * Requête GraphQL authentifiée (`POST /graphql`). Renvoie le corps décodé.
     *
     * @return array<mixed>
     *
     * @throws RepositoryAccessDeniedException 401/403 hors quota
     * @throws RepositoryUnreachableException  quota, 5xx, réseau, timeout, corps illisible
     */
    private function graphql(string $query, string $plainToken): array
    {
        try {
            $response = $this->client->request('POST', '/graphql', [
                'auth_bearer' => $plainToken,
                'json' => ['query' => $query],
            ]);

            $this->guardStatus($response->getStatusCode(), $response->getHeaders(false));

            $body = $response->toArray();
            $this->guardGraphqlRateLimit($body);

            return $body;
        } catch (TransportExceptionInterface $e) {
            throw new RepositoryUnreachableException('Dépôt GitHub injoignable (réseau ou timeout).', 0, $e);
        } catch (ExceptionInterface $e) {
            throw new RepositoryUnreachableException('Réponse GitHub illisible.', 0, $e);
        }
    }

    /**
     * Le versant GraphQL de GitHub signale un quota dépassé par un **HTTP 200** portant un
     * bloc `errors` de type `RATE_LIMITED` (là où le REST renvoie un 403). On l'aligne sur
     * le versant REST — même exception `RepositoryUnreachableException('Quota GitHub dépassé.')`.
     * Les autres erreurs GraphQL partielles (ex. `NOT_FOUND` sur un alias) sont laissées passer :
     * le champ concerné vaut `null`, la carte dégrade (fidélité, règle 9).
     *
     * @param array<mixed> $body
     *
     * @throws RepositoryUnreachableException quota GraphQL dépassé
     */
    private function guardGraphqlRateLimit(array $body): void
    {
        $errors = \is_array($body['errors'] ?? null) ? $body['errors'] : [];
        $types = array_column($errors, 'type');

        if (\in_array('RATE_LIMITED', $types, true)) {
            throw new RepositoryUnreachableException('Quota GitHub dépassé.');
        }
    }

    /**
     * Traduit un statut HTTP d'échec en exception métier, source unique partagée par les
     * versants REST ({@see get}, {@see readFile}) et GraphQL ({@see graphql}). Le 404 a un
     * sens propre à chaque appelant (arbre vide vs fichier introuvable) et reste traité en amont.
     *
     * @param array<string, list<string>> $headers en-têtes de réponse (pour la détection de quota)
     *
     * @throws RepositoryAccessDeniedException 401/403 hors quota
     * @throws RepositoryUnreachableException  quota (403 + remaining 0), ou tout statut hors 2xx
     */
    private function guardStatus(int $status, array $headers): void
    {
        if (401 === $status || 403 === $status) {
            if (403 === $status && $this->isRateLimited($headers)) {
                throw new RepositoryUnreachableException('Quota GitHub dépassé.');
            }

            throw new RepositoryAccessDeniedException('Accès GitHub refusé (token invalide ou insuffisant).');
        }

        if ($status < 200 || $status >= 300) {
            throw new RepositoryUnreachableException(sprintf('Réponse GitHub inattendue (HTTP %d).', $status));
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
