<?php

declare(strict_types=1);

namespace App\Service\Repository;

use App\Enum\Type\Provider;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Résout le {@see RepositoryReaderInterface} supportant un provider donné.
 *
 * Un provider sans reader (GitLab, tant que la V2 n'existe pas) renvoie `null` :
 * {@see ProjectVerifier} en déduit `UnsupportedProvider` sans tenter d'appel réseau.
 */
final readonly class RepositoryReaderRegistry
{
    /**
     * @param iterable<RepositoryReaderInterface> $readers
     */
    public function __construct(
        #[AutowireIterator('app.repository_reader')]
        private iterable $readers,
    ) {
    }

    public function readerFor(Provider $provider): ?RepositoryReaderInterface
    {
        foreach ($this->readers as $reader) {
            if ($reader->supports($provider)) {
                return $reader;
            }
        }

        return null;
    }
}
