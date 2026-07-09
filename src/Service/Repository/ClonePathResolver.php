<?php

declare(strict_types=1);

namespace App\Service\Repository;

use App\Service\RepositoryUrl;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Résout le dossier de clone local d'un dépôt : `private/<owner>-<repo>`, un par dépôt.
 *
 * L'`owner` et le `repo` viennent du {@see \App\Service\RepositoryUrlNormalizer} et sont
 * déjà validés segment par segment (`[A-Za-z0-9._-]+`). On les aplatit en un identifiant
 * de dossier unique (les `/` d'un sous-groupe GitLab deviennent des `-`) et on refuse tout
 * nom douteux (traversée `..`) : le chemin retourné reste garanti sous `private/`.
 */
final readonly class ClonePathResolver
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/private')]
        private string $baseDir,
    ) {
    }

    /**
     * @throws InvalidCloneDestinationException si l'`owner`/`repo` produit un nom de dossier douteux
     */
    public function resolve(RepositoryUrl $url): string
    {
        $slug = str_replace('/', '-', $url->owner . '-' . $url->repo);

        if (str_contains($slug, '..') || 1 !== preg_match('/^[A-Za-z0-9._-]+$/', $slug)) {
            throw new InvalidCloneDestinationException(sprintf('Nom de dossier de clone invalide pour « %s ».', $url->name()));
        }

        return $this->baseDir . '/' . $slug;
    }
}
