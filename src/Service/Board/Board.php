<?php

declare(strict_types=1);

namespace App\Service\Board;

use App\Enum\Type\PipelineStage;

/**
 * Le tableau d'un projet : les cartes réparties sur les quatre colonnes ordonnées du
 * pipeline unifié, plus le bandeau « À vérifier » regroupant les stories indécidables.
 *
 * Value object immuable produit par {@see ProjectBoardBuilder}. Les cartes d'une colonne
 * sont déjà triées par numéro décroissant (règle 5). L'écran ne recalcule aucune
 * position : il lit ce que le builder a projeté.
 */
final readonly class Board
{
    /** Les quatre colonnes du pipeline, de gauche à droite (règle 1). */
    private const array COLUMNS = [
        PipelineStage::Cadrage,
        PipelineStage::Planifie,
        PipelineStage::Review,
        PipelineStage::Livre,
    ];

    /**
     * @param array<string, list<StoryCard>> $columns cartes par colonne, indexées sur `PipelineStage->value`
     * @param list<StoryCard>                $banner  cartes « À vérifier »
     */
    public function __construct(
        private array $columns,
        private array $banner,
    ) {
    }

    /**
     * @return list<PipelineStage> les quatre colonnes dans l'ordre d'affichage
     */
    public function columns(): array
    {
        return self::COLUMNS;
    }

    /**
     * @return list<StoryCard>
     */
    public function cardsFor(PipelineStage $stage): array
    {
        return $this->columns[$stage->value] ?? [];
    }

    public function countFor(PipelineStage $stage): int
    {
        return \count($this->cardsFor($stage));
    }

    /**
     * @return list<StoryCard>
     */
    public function banner(): array
    {
        return $this->banner;
    }

    public function bannerCount(): int
    {
        return \count($this->banner);
    }

    /**
     * Vrai quand le projet est éligible mais ne contient aucune story (règle 9 : état vide).
     */
    public function isEmpty(): bool
    {
        if ([] !== $this->banner) {
            return false;
        }

        foreach (self::COLUMNS as $stage) {
            if ([] !== $this->cardsFor($stage)) {
                return false;
            }
        }

        return true;
    }
}
