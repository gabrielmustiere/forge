<?php

declare(strict_types=1);

namespace App\Service\Repository;

use App\Enum\Type\Provider;
use App\Service\Github\StoryTree;
use App\Service\RepositoryUrl;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Accès en lecture seule à l'arborescence `docs/story/` d'un dépôt distant.
 *
 * Une implémentation par provider (GitHub aujourd'hui, GitLab en V2), sélectionnée
 * par {@see RepositoryReaderRegistry} via {@see supports()}. Toutes les implémentations
 * sont taguées automatiquement (`app.repository_reader`) pour alimenter le registry.
 */
#[AutoconfigureTag('app.repository_reader')]
interface RepositoryReaderInterface
{
    public function supports(Provider $provider): bool;

    /**
     * Lit l'arborescence `docs/story/` du dépôt et la retourne parsée.
     *
     * @param string $plainToken token en clair, utilisé le temps de l'appel puis oublié
     *
     * @throws RepositoryUnreachableException  dépôt/branche introuvable, réseau, timeout, quota
     * @throws RepositoryAccessDeniedException accès refusé (401/403)
     */
    public function readStoryTree(RepositoryUrl $url, string $plainToken): StoryTree;
}
