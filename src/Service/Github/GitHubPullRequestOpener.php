<?php

declare(strict_types=1);

namespace App\Service\Github;

use App\Enum\Type\Provider;
use App\Service\RepositoryUrl;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Ouvre une PR **draft** GitHub via l'API REST, en deux appels bornés :
 *
 *  1. `GET /repos/{owner}/{repo}` → branche par défaut (la base de la PR) ;
 *  2. `POST /repos/{owner}/{repo}/pulls` avec `draft: true` → la proposition en brouillon.
 *
 * Le token en clair (droit d'écriture) n'est utilisé qu'en `auth_bearer`, jamais dans l'URL
 * ni loggé. Tout statut d'échec est traduit en {@see PullRequestFailedException} lisible.
 * Même client scopé (`github.client`) et même discipline d'erreurs que {@see GitHubRepositoryReader}.
 */
final readonly class GitHubPullRequestOpener implements PullRequestOpenerInterface
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

    public function open(RepositoryUrl $url, #[\SensitiveParameter] string $plainToken, string $head, string $title, string $body): string
    {
        $repo = $url->owner . '/' . $url->repo;
        $base = $this->defaultBranch($repo, $plainToken);

        try {
            $response = $this->client->request('POST', '/repos/' . $repo . '/pulls', [
                'auth_bearer' => $plainToken,
                'json' => [
                    'title' => $title,
                    'head' => $head,
                    'base' => $base,
                    'body' => $body,
                    'draft' => true,
                ],
            ]);

            $status = $response->getStatusCode();
            $this->guardStatus($status, $response->getHeaders(false), $response);

            $data = $response->toArray();
            $htmlUrl = $data['html_url'] ?? null;

            if (!\is_string($htmlUrl) || '' === $htmlUrl) {
                throw new PullRequestFailedException('Réponse GitHub sans URL de proposition.');
            }

            return $htmlUrl;
        } catch (TransportExceptionInterface $e) {
            throw new PullRequestFailedException('GitHub injoignable (réseau ou timeout).', 0, $e);
        } catch (ExceptionInterface $e) {
            throw new PullRequestFailedException('Réponse GitHub illisible.', 0, $e);
        }
    }

    private function defaultBranch(string $repo, #[\SensitiveParameter] string $plainToken): string
    {
        try {
            $response = $this->client->request('GET', '/repos/' . $repo, ['auth_bearer' => $plainToken]);
            $this->guardStatus($response->getStatusCode(), $response->getHeaders(false), $response);

            $branch = $response->toArray()['default_branch'] ?? null;
            if (!\is_string($branch) || '' === $branch) {
                throw new PullRequestFailedException(sprintf('Branche par défaut introuvable pour « %s ».', $repo));
            }

            return $branch;
        } catch (TransportExceptionInterface $e) {
            throw new PullRequestFailedException('GitHub injoignable (réseau ou timeout).', 0, $e);
        } catch (ExceptionInterface $e) {
            throw new PullRequestFailedException('Réponse GitHub illisible.', 0, $e);
        }
    }

    /**
     * Traduit un statut HTTP d'échec en {@see PullRequestFailedException} lisible.
     *
     * @param array<string, list<string>> $headers en-têtes de réponse (détection de quota)
     *
     * @throws PullRequestFailedException 401/403 (droit d'écriture manquant), quota, 422 (branche
     *                                    introuvable / diff vide / doublon), tout statut hors 2xx
     */
    private function guardStatus(int $status, array $headers, ResponseInterface $response): void
    {
        if (401 === $status || 403 === $status) {
            if (403 === $status && '0' === ($headers['x-ratelimit-remaining'][0] ?? null)) {
                throw new PullRequestFailedException('Quota GitHub dépassé.');
            }

            throw new PullRequestFailedException('Accès GitHub refusé : le token doit avoir le droit d\'écriture (push + pull request).');
        }

        if (422 === $status) {
            throw new PullRequestFailedException(sprintf('GitHub refuse la proposition : %s', $this->unprocessableReason($response)));
        }

        if ($status < 200 || $status >= 300) {
            throw new PullRequestFailedException(sprintf('Réponse GitHub inattendue (HTTP %d).', $status));
        }
    }

    /**
     * Extrait le message d'un 422 (branche `head` absente, aucune différence, PR déjà ouverte)
     * sans faire échouer la traduction si le corps est illisible.
     */
    private function unprocessableReason(ResponseInterface $response): string
    {
        try {
            $body = $response->toArray(false);
        } catch (ExceptionInterface) {
            return 'requête non traitable (422).';
        }

        $message = $body['message'] ?? null;
        $errors = \is_array($body['errors'] ?? null) ? $body['errors'] : [];
        $detail = null;
        foreach ($errors as $error) {
            if (\is_array($error) && \is_string($error['message'] ?? null)) {
                $detail = $error['message'];
                break;
            }
        }

        return $detail ?? (\is_string($message) ? $message : 'requête non traitable (422).');
    }
}
