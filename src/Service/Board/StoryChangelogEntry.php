<?php

declare(strict_types=1);

namespace App\Service\Board;

/**
 * Une entrée de la timeline consolidée d'une story (source : `metadata.json`, champ
 * `changelog`). Value object immuable produit par {@see StoryMetadataParser}.
 *
 * `type` est un libellé court (« Création », « Planification », « Livraison »…), `description`
 * une phrase. La date est celle de la passe du skill qui a produit l'entrée.
 */
final readonly class StoryChangelogEntry
{
    public function __construct(
        public \DateTimeImmutable $date,
        public string $type,
        public string $description,
    ) {
    }
}
