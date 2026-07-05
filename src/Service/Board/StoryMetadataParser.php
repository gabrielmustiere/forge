<?php

declare(strict_types=1);

namespace App\Service\Board;

/**
 * Décode le contenu brut d'un `metadata.json` de story en {@see StoryMetadata}, avec une
 * validation **strictement tolérante** : le JSON vient d'un dépôt tiers, jamais fiable.
 *
 * Principe de fidélité (règle métier 9) : au moindre doute, on renvoie `null` plutôt qu'une
 * donnée fausse — le board dégrade alors la carte vers le slug humanisé. Aucune exception
 * n'est jamais propagée au template. Les champs vitaux (`title`, `created`, `updated`) sont
 * obligatoires ; le reste (`tags`, `changelog`, `delivery`) est nettoyé entrée par entrée,
 * une valeur invalide étant écartée sans invalider tout le fichier.
 */
final class StoryMetadataParser
{
    /**
     * @param ?string $raw contenu brut du `metadata.json`, ou `null` si le fichier est absent
     *
     * @return ?StoryMetadata `null` si absent, illisible, malformé ou privé d'un champ vital
     */
    public function parse(?string $raw): ?StoryMetadata
    {
        if (null === $raw || '' === trim($raw)) {
            return null;
        }

        try {
            $data = json_decode($raw, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!\is_array($data)) {
            return null;
        }

        $title = $this->nonEmptyString($data['title'] ?? null);
        $created = $this->date($data['created'] ?? null);
        $updated = $this->date($data['updated'] ?? null);

        if (null === $title || null === $created || null === $updated) {
            return null;
        }

        return new StoryMetadata(
            \is_int($data['version'] ?? null) ? $data['version'] : 1,
            $title,
            $created,
            $updated,
            $this->tags($data['tags'] ?? null),
            $this->changelog($data['changelog'] ?? null),
            $this->delivery($data['delivery'] ?? null),
        );
    }

    private function nonEmptyString(mixed $value): ?string
    {
        return \is_string($value) && '' !== trim($value) ? trim($value) : null;
    }

    /**
     * Parse une date `YYYY-MM-DD` stricte (rejette les dates impossibles type `2026-13-40`).
     */
    private function date(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value) || 1 !== preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return false !== $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    /**
     * @return list<string> étiquettes non vides, dédupliquées, dans l'ordre de première apparition
     */
    private function tags(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $tags = [];
        foreach ($value as $tag) {
            $clean = $this->nonEmptyString($tag);
            if (null !== $clean && !\in_array($clean, $tags, true)) {
                $tags[] = $clean;
            }
        }

        return $tags;
    }

    /**
     * @return list<StoryChangelogEntry> entrées valides uniquement (une entrée malformée est écartée)
     */
    private function changelog(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $entries = [];
        foreach ($value as $entry) {
            if (!\is_array($entry)) {
                continue;
            }

            $date = $this->date($entry['date'] ?? null);
            $type = $this->nonEmptyString($entry['type'] ?? null);
            $description = $this->nonEmptyString($entry['description'] ?? null);

            if (null !== $date && null !== $type && null !== $description) {
                $entries[] = new StoryChangelogEntry($date, $type, $description);
            }
        }

        return $entries;
    }

    /**
     * `null` si le champ est absent ou vide de tout maillon ; sinon un {@see StoryDelivery}
     * dont release/commit sont indépendamment nullables (livraison partielle tolérée).
     */
    private function delivery(mixed $value): ?StoryDelivery
    {
        if (!\is_array($value)) {
            return null;
        }

        $release = $this->nonEmptyString($value['release'] ?? null);
        $commit = $this->nonEmptyString($value['commit'] ?? null);

        if (null === $release && null === $commit) {
            return null;
        }

        return new StoryDelivery($release, $commit);
    }
}
