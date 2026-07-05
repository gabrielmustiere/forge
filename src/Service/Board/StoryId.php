<?php

declare(strict_types=1);

namespace App\Service\Board;

use App\Enum\Type\Track;

/**
 * Identifiant d'une story `NNN-<f|r|t>-<slug>` décomposé en ses trois parties.
 *
 * Value object pur construit par {@see parse()} depuis l'identifiant fourni par le
 * scan (`docs/story/NNN-…`). Le format est déjà garanti conforme en amont
 * ({@see \App\Service\Github\StoryTree}), mais {@see parse()} le revérifie pour
 * rester autonome et lever tôt sur un identifiant inattendu.
 */
final readonly class StoryId
{
    /** `005-f-kanban-projet` → capture `number`, `track`, `slug`. */
    private const PATTERN = '#^(?<number>\d{3})-(?<track>[frt])-(?<slug>[a-z0-9]+(?:-[a-z0-9]+)*)$#';

    private function __construct(
        public string $value,
        public int $number,
        public Track $track,
        public string $slug,
    ) {
    }

    /**
     * @throws \InvalidArgumentException si l'identifiant ne respecte pas `NNN-<f|r|t>-<slug>`
     */
    public static function parse(string $id): self
    {
        if (1 !== preg_match(self::PATTERN, $id, $matches)) {
            throw new \InvalidArgumentException(sprintf('Identifiant de story invalide : « %s ».', $id));
        }

        return new self(
            $id,
            (int) $matches['number'],
            Track::fromLetter($matches['track']),
            $matches['slug'],
        );
    }

    /**
     * Titre lisible dérivé du slug seul, sans lecture de contenu : `kanban-projet` → « Kanban projet ».
     * La première lettre est capitalisée, les tirets deviennent des espaces (règle 4 : la carte
     * reste rapide, le vrai `# H1` n'apparaît que dans le drawer).
     */
    public function humanizedTitle(): string
    {
        return ucfirst(str_replace('-', ' ', $this->slug));
    }
}
