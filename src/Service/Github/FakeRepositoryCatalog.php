<?php

declare(strict_types=1);

namespace App\Service\Github;

use App\Service\Repository\RepositoryAccessDeniedException;
use App\Service\Repository\RepositoryUnreachableException;
use App\Service\RepositoryUrl;

/**
 * Catalogue déterministe de données de dépôt factices, piloté par le nom du dépôt.
 *
 * Source unique consommée par {@see \App\Tests\Double\StubRepositoryReader} (env test) et
 * par {@see DevFakeRepositoryReader} (env dev, opt-in) : aucun appel réseau, comportement
 * identique dans les deux cas. Le nom du dépôt décide du scénario :
 *  - `*denied*`   → accès refusé ;
 *  - `*offline*`  → injoignable ;
 *  - `*board*`    → pipeline complet (quatre colonnes + bandeau « À vérifier ») ;
 *  - `*eligible*` → éligible (une story) ;
 *  - sinon        → arbre vide (défaut sûr).
 */
final class FakeRepositoryCatalog
{
    /**
     * @throws RepositoryAccessDeniedException
     * @throws RepositoryUnreachableException
     */
    public static function treeFor(RepositoryUrl $url): StoryTree
    {
        return match (true) {
            str_contains($url->repo, 'denied') => throw new RepositoryAccessDeniedException('fake: accès refusé'),
            str_contains($url->repo, 'offline') => throw new RepositoryUnreachableException('fake: injoignable'),
            str_contains($url->repo, 'board') => self::boardTree(),
            str_contains($url->repo, 'eligible') => new StoryTree([new StoryFolder('001-f-demo', ['pitch.md', 'plan.md'])]),
            default => new StoryTree([]),
        };
    }

    /**
     * @throws RepositoryAccessDeniedException
     * @throws RepositoryUnreachableException
     */
    public static function fileContent(RepositoryUrl $url, string $path): string
    {
        return match (true) {
            str_contains($url->repo, 'denied') => throw new RepositoryAccessDeniedException('fake: accès refusé'),
            str_contains($url->repo, 'offline') => throw new RepositoryUnreachableException('fake: injoignable'),
            str_contains($url->repo, 'missing') => throw new RepositoryUnreachableException('fake: fichier introuvable'),
            // Contenu déterministe embarquant du HTML brut (sanitization) et un lien externe
            // (ouverture sûre en nouvel onglet), pour prouver le rendu markdown.
            default => sprintf(
                "# Titre réel de la story\n\nContenu **markdown** du document `%s`.\n\n[Voir la doc](https://example.com/page)\n\n<script>alert('xss')</script>\n",
                $path,
            ),
        };
    }

    /**
     * Arbre riche : les quatre colonnes peuplées, deux cartes en Livré (tri par numéro
     * décroissant) et une story « À vérifier ».
     */
    private static function boardTree(): StoryTree
    {
        return new StoryTree([
            new StoryFolder('001-f-cadrage', ['pitch.md']),
            new StoryFolder('010-f-planifie', ['pitch.md', 'plan.md']),
            new StoryFolder('005-r-review', ['plan.md', 'review.md']),
            new StoryFolder('007-t-livre', ['report.md']),
            new StoryFolder('003-f-livre-complet', ['pitch.md', 'plan.md', 'review.md', 'report.md']),
            new StoryFolder('002-f-mystere', ['readme.md']),
        ]);
    }
}
