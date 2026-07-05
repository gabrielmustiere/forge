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
     * `metadata.json` factices déterministes, servis en un seul lot ({@see readStoryMetadata}).
     *
     * Chaque story fixture porte un vrai titre, des tags et une date de mise à jour distincts
     * (filtre + tri testables) ; deux stories sont livrées (badge). `002-f-mystere` n'a **pas**
     * de metadata → prouve la dégradation gracieuse (règle 9). Les scénarios d'erreur du dépôt
     * restent cohérents avec {@see treeFor}.
     *
     * @param list<string> $storyIds
     *
     * @return array<string, ?string>
     *
     * @throws RepositoryAccessDeniedException
     * @throws RepositoryUnreachableException
     */
    public static function metadataFor(RepositoryUrl $url, array $storyIds): array
    {
        return match (true) {
            str_contains($url->repo, 'denied') => throw new RepositoryAccessDeniedException('fake: accès refusé'),
            str_contains($url->repo, 'offline') => throw new RepositoryUnreachableException('fake: injoignable'),
            default => array_combine(
                $storyIds,
                array_map(static fn (string $id): ?string => self::FAKE_METADATA[$id] ?? null, $storyIds),
            ),
        };
    }

    /**
     * `metadata.json` factices indexés par identifiant de story (JSON brut, comme le renverrait
     * le transport réel). Absent de la table → `null` (dégradation).
     *
     * @var array<string, string>
     */
    private const array FAKE_METADATA = [
        '001-f-cadrage' => '{"version":1,"title":"Cadrer la connexion GitHub","created":"2026-06-01","updated":"2026-06-10","tags":["connecteur","auth"],"changelog":[{"date":"2026-06-01","type":"Création","description":"Pitch initial."}]}',
        '010-f-planifie' => '{"version":1,"title":"Planifier le mapping des étapes","created":"2026-06-05","updated":"2026-06-20","tags":["mapping"],"changelog":[{"date":"2026-06-05","type":"Création","description":"Pitch."},{"date":"2026-06-20","type":"Planification","description":"Plan validé."}]}',
        '005-r-review' => '{"version":1,"title":"Revue du normaliseur d\'URL","created":"2026-06-02","updated":"2026-06-25","tags":["dette","url"],"changelog":[{"date":"2026-06-25","type":"Review","description":"Revue faite."}]}',
        '007-t-livre' => '{"version":1,"title":"Durcir le client HTTP","created":"2026-05-20","updated":"2026-06-28","tags":["http","dette"],"changelog":[{"date":"2026-06-28","type":"Livraison","description":"Livré."}],"delivery":{"release":"v4.2.0","commit":"abc1234"}}',
        '003-f-livre-complet' => '{"version":1,"title":"Afficher le kanban d\'un projet","created":"2026-05-25","updated":"2026-06-30","tags":["board","kanban"],"changelog":[{"date":"2026-06-30","type":"Livraison","description":"Livré."}],"delivery":{"release":"v4.3.0","commit":"b7964b4"}}',
        '012-f-idee' => '{"version":1,"title":"Explorer les notifications de sync","created":"2026-07-01","updated":"2026-07-02","tags":["exploration","notifications"],"changelog":[{"date":"2026-07-01","type":"Interview","description":"Besoin dégrossi par interview."}]}',
    ];

    /**
     * Arbre riche : les cinq colonnes peuplées (une story `brief.md` seule en Idée),
     * deux cartes en Livré (tri par numéro décroissant) et une story « À vérifier ».
     */
    private static function boardTree(): StoryTree
    {
        return new StoryTree([
            new StoryFolder('012-f-idee', ['brief.md']),
            new StoryFolder('001-f-cadrage', ['pitch.md']),
            new StoryFolder('010-f-planifie', ['pitch.md', 'plan.md']),
            new StoryFolder('005-r-review', ['plan.md', 'review.md']),
            new StoryFolder('007-t-livre', ['report.md']),
            new StoryFolder('003-f-livre-complet', ['pitch.md', 'plan.md', 'review.md', 'report.md']),
            new StoryFolder('002-f-mystere', ['readme.md']),
        ]);
    }
}
