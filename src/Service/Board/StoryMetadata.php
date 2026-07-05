<?php

declare(strict_types=1);

namespace App\Service\Board;

/**
 * Métadonnées lisibles d'une story (source : `metadata.json` du dossier de story, produit et
 * maintenu par les skills forge — jamais par l'app). Value object immuable produit par
 * {@see StoryMetadataParser} après validation tolérante.
 *
 * Porte le vrai titre, les dates de vie (création figée, dernière activité), les étiquettes,
 * la timeline consolidée et le lien de livraison. Absent ou malformé, le parser renvoie `null`
 * et la carte dégrade vers le slug humanisé (règle métier 9) — d'où l'invariant : une instance
 * de ce VO n'existe que si `title`, `created` et `updated` sont valides.
 */
final readonly class StoryMetadata
{
    /**
     * @param list<string>              $tags      étiquettes kebab-case, dédupliquées
     * @param list<StoryChangelogEntry> $changelog timeline consolidée, ordre chronologique
     */
    public function __construct(
        public int $version,
        public string $title,
        public \DateTimeImmutable $created,
        public \DateTimeImmutable $updated,
        public array $tags,
        public array $changelog,
        public ?StoryDelivery $delivery,
    ) {
    }

    public function hasTags(): bool
    {
        return [] !== $this->tags;
    }

    public function hasChangelog(): bool
    {
        return [] !== $this->changelog;
    }

    public function isDelivered(): bool
    {
        return null !== $this->delivery && $this->delivery->isDelivered();
    }
}
