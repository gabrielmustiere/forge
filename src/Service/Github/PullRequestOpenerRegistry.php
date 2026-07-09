<?php

declare(strict_types=1);

namespace App\Service\Github;

use App\Enum\Type\Provider;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Résout le {@see PullRequestOpenerInterface} supportant un provider donné.
 *
 * Un provider sans opener (GitLab, tant que le suivant n'existe pas) renvoie `null` :
 * l'appelant en déduit un échec lisible sans tenter d'appel réseau. Miroir de
 * {@see \App\Service\Repository\RepositoryReaderRegistry}.
 */
final readonly class PullRequestOpenerRegistry
{
    /**
     * @param iterable<PullRequestOpenerInterface> $openers
     */
    public function __construct(
        #[AutowireIterator('app.pull_request_opener')]
        private iterable $openers,
    ) {
    }

    public function openerFor(Provider $provider): ?PullRequestOpenerInterface
    {
        foreach ($this->openers as $opener) {
            if ($opener->supports($provider)) {
                return $opener;
            }
        }

        return null;
    }
}
