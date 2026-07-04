<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Enum\Type\Provider;
use App\Service\Github\StoryFolder;
use App\Service\Github\StoryTree;
use App\Service\Repository\RepositoryAccessDeniedException;
use App\Service\Repository\RepositoryReaderInterface;
use App\Service\Repository\RepositoryUnreachableException;
use App\Service\RepositoryUrl;

/**
 * Reader GitHub déterministe pour les tests fonctionnels et E2E : il remplace
 * {@see \App\Service\Github\GitHubRepositoryReader} en environnement `test` afin
 * qu'aucun appel réseau réel ne soit jamais émis.
 *
 * Le résultat est décidé par le nom du dépôt, ce qui permet de piloter le statut
 * attendu depuis l'URL déclarée :
 *  - `*denied*`   → accès refusé (token invalide) ;
 *  - `*offline*`  → injoignable ;
 *  - `*eligible*` → éligible (une story présente) ;
 *  - sinon        → non-forge (arbre vide), le défaut sûr.
 */
final class StubRepositoryReader implements RepositoryReaderInterface
{
    public function supports(Provider $provider): bool
    {
        return Provider::GitHub === $provider;
    }

    public function readStoryTree(RepositoryUrl $url, string $plainToken): StoryTree
    {
        return match (true) {
            str_contains($url->repo, 'denied') => throw new RepositoryAccessDeniedException('stub: accès refusé'),
            str_contains($url->repo, 'offline') => throw new RepositoryUnreachableException('stub: injoignable'),
            str_contains($url->repo, 'eligible') => new StoryTree([new StoryFolder('001-f-demo', ['pitch.md', 'plan.md'])]),
            default => new StoryTree([]),
        };
    }
}
