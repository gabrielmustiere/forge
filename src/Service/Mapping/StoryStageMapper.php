<?php

declare(strict_types=1);

namespace App\Service\Mapping;

use App\Enum\Type\PipelineStage;
use App\Service\Github\StoryFolder;

/**
 * Déduit l'étape d'une story sur le pipeline à partir des seuls noms de fichiers
 * présents dans son dossier — jamais du contenu, jamais d'une saisie.
 *
 * Fonction pure sans effet de bord, dans l'esprit de {@see \App\Service\Repository\ProjectVerifier} :
 * le résultat ne dépend que de l'ensemble des noms de fichiers. La table de précédence
 * {@see PRECEDENCE} est le point unique à modifier quand la convention forge évolue
 * (renommage de livrable, nouveau document).
 */
final readonly class StoryStageMapper
{
    /**
     * Fichier déclencheur → étape, ordonnés du plus avancé au moins avancé.
     * Le premier fichier présent (nom top-level exact) l'emporte : `report.md`
     * gagne sur `review.md`, qui gagne sur `plan.md`, qui gagne sur `pitch.md`.
     *
     * @var array<string, PipelineStage>
     */
    private const array PRECEDENCE = [
        'report.md' => PipelineStage::Livre,
        'review.md' => PipelineStage::Review,
        'plan.md' => PipelineStage::Planifie,
        'pitch.md' => PipelineStage::Cadrage,
    ];

    /**
     * L'étape déduite du dossier, ou {@see PipelineStage::AVerifier} si aucun
     * fichier de pipeline n'est présent.
     */
    public function stageFor(StoryFolder $folder): PipelineStage
    {
        $files = $folder->files();

        foreach (self::PRECEDENCE as $filename => $stage) {
            if (\in_array($filename, $files, true)) {
                return $stage;
            }
        }

        return PipelineStage::AVerifier;
    }
}
