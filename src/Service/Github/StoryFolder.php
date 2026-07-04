<?php

declare(strict_types=1);

namespace App\Service\Github;

/**
 * Un dossier de story lu à distance : son identifiant `NNN-<f|r|t>-<slug>` et les
 * noms de fichiers qu'il contient (chemins relatifs au dossier de la story).
 *
 * Value object immuable. La liste de fichiers n'est pas exploitée pour l'éligibilité
 * (qui ne regarde que la présence de la story) ; elle est déjà disponible pour
 * `mapping-etapes` sans y être utilisée ici.
 */
final readonly class StoryFolder
{
    /**
     * @param list<string> $files chemins de fichiers relatifs au dossier de la story, triés
     */
    public function __construct(
        public string $id,
        public array $files,
    ) {
    }

    /**
     * @return list<string>
     */
    public function files(): array
    {
        return $this->files;
    }
}
