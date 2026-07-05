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
 * drawer (`report` > `review` > `plan` > `pitch`, puis les transversaux), sans lecture
 * de contenu — le titre réel `# H1` n'est lu qu'à l'ouverture d'un document.
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
    ) {
    }
}
