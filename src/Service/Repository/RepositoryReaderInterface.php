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

    /**
     * Lit le contenu brut d'un fichier précis du dépôt et le retourne tel quel.
     *
     * @param string $plainToken token en clair, utilisé le temps de l'appel puis oublié
     * @param string $path       chemin du fichier relatif à la racine du dépôt (ex. `docs/story/005-f-x/pitch.md`)
     *
     * @throws RepositoryUnreachableException  fichier/dépôt introuvable, réseau, timeout, quota
     * @throws RepositoryAccessDeniedException accès refusé (401/403)
     */
    public function readFile(RepositoryUrl $url, string $plainToken, string $path): string;

    /**
     * Lit en **un seul appel groupé** le `metadata.json` de plusieurs stories (règle 10 :
     * chargement instantané, nombre d'appels indépendant du nombre de stories). Pur transport :
     * renvoie le JSON brut ou `null` par story ; le décodage/validation revient au parser.
     *
     * @param string       $plainToken token en clair, utilisé le temps de l'appel puis oublié
     * @param list<string> $storyIds   identifiants `NNN-<f|r|t>-<slug>` des stories à lire
     *
     * @return array<string, ?string> map storyId → contenu brut du `metadata.json`, `null` si absent
     *
     * @throws RepositoryUnreachableException  dépôt injoignable, réseau, timeout, quota
     * @throws RepositoryAccessDeniedException accès refusé (401/403)
     */
    public function readStoryMetadata(RepositoryUrl $url, string $plainToken, array $storyIds): array;
}
