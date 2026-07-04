<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\Type\Provider;

/**
 * Forme canonique d'une URL de dépôt, produite par le {@see RepositoryUrlNormalizer}.
 */
final readonly class RepositoryUrl
{
    public function __construct(
        public Provider $provider,
        public string $owner,
        public string $repo,
        public string $normalizedUrl,
    ) {
    }

    public function name(): string
    {
        return $this->owner . '/' . $this->repo;
    }
}
