<?php

declare(strict_types=1);

namespace App\Service\Board;

use App\Enum\Type\PipelineStage;

/**
 * Une carte du board : une story projetée sur le pipeline.
 *
 * Value object immuable produit par {@see ProjectBoardBuilder}. L'identité vient de
 * {@see StoryId} (numéro, track, slug, titre humanisé) ; la colonne vient du moteur
 * de mapping (`004`) ; {@see documents} liste les documents présents ordonnés pour le
 * drawer (`report` > `review` > `plan` > `pitch`, puis les transversaux). {@see metadata}
 * porte les métadonnées lues dans le `metadata.json` de la story — `null` quand le fichier
 * est absent ou invalide, auquel cas la carte dégrade vers le slug humanisé (règle 9).
 */
final readonly class StoryCard
{
    /**
     * @param list<string> $documents noms de fichiers `.md` présents, ordonnés pour le drawer
     */
    public function __construct(
        public StoryId $id,
        public PipelineStage $stage,
        public array $documents,
        public ?StoryMetadata $metadata = null,
    ) {
    }

    /**
     * Le titre à afficher : le vrai `title` du metadata s'il existe, sinon le slug humanisé
     * (dégradation gracieuse, règle 9).
     */
    public function title(): string
    {
        return null !== $this->metadata ? $this->metadata->title : $this->id->humanizedTitle();
    }
}
