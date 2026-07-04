<?php

declare(strict_types=1);

namespace App\Service\Github;

/**
 * Résultat de la lecture de l'arborescence `docs/story/` d'un dépôt distant :
 * la liste immuable des stories conformes à la convention forge.
 *
 * Construit depuis les entrées d'un arbre Git (chemins relatifs à `docs/story/`).
 * Seules les entrées respectant `NNN-<f|r|t>-<slug>/…` sont retenues ; tout le reste
 * est ignoré. `hasStories()` porte l'éligibilité forge ; la structure fichiers-par-story
 * est déjà disponible pour `mapping-etapes`.
 */
final readonly class StoryTree
{
    /** Un dossier de story conforme : `001-f-slug`, `012-r-autre-slug`… */
    private const STORY_ID = '#^(\d{3}-[frt]-[a-z0-9]+(?:-[a-z0-9]+)*)$#';

    /**
     * @param list<StoryFolder> $stories
     */
    public function __construct(
        public array $stories,
    ) {
    }

    /**
     * Construit l'arbre depuis des entrées Git relatives à `docs/story/`.
     *
     * @param iterable<array{path: string, type: string}> $entries entrées d'un `git/trees?recursive=1`
     */
    public static function fromTreeEntries(iterable $entries): self
    {
        /** @var array<string, list<string>> $filesByStory */
        $filesByStory = [];

        foreach ($entries as $entry) {
            $segments = explode('/', trim($entry['path'], '/'));
            $storyId = $segments[0];

            if (1 !== preg_match(self::STORY_ID, $storyId)) {
                continue;
            }

            // Enregistre la story dès qu'on croise son dossier ou un fichier dedans.
            $filesByStory[$storyId] ??= [];

            if ('blob' === $entry['type'] && \count($segments) > 1) {
                $filesByStory[$storyId][] = implode('/', \array_slice($segments, 1));
            }
        }

        ksort($filesByStory);

        $stories = [];
        foreach ($filesByStory as $storyId => $files) {
            sort($files);

            $stories[] = new StoryFolder($storyId, array_values(array_unique($files)));
        }

        return new self($stories);
    }

    public function hasStories(): bool
    {
        return [] !== $this->stories;
    }
}
