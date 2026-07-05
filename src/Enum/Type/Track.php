<?php

declare(strict_types=1);

namespace App\Enum\Type;

/**
 * Voie d'une story, déduite de la lettre `f`/`r`/`t` de son identifiant `NNN-<f|r|t>-<slug>`.
 *
 * Vocabulaire pur, dans l'esprit de {@see PipelineStage} : le badge porté par la carte
 * distingue les tracks hétérogènes sur les mêmes colonnes du pipeline unifié.
 */
enum Track: string
{
    /** Une feature — nouvelle capacité produit. */
    case Feature = 'feature';
    /** Une refacto — amélioration interne à comportement constant. */
    case Refacto = 'refacto';
    /** Une évolution technique — socle, outillage, dette. */
    case Tech = 'tech';

    /**
     * La track désignée par la lettre d'un identifiant de story (`f`/`r`/`t`).
     *
     * @throws \ValueError si la lettre n'est pas une voie connue
     */
    public static function fromLetter(string $letter): self
    {
        return match ($letter) {
            'f' => self::Feature,
            'r' => self::Refacto,
            't' => self::Tech,
            default => throw new \ValueError(sprintf('Lettre de track inconnue : « %s ».', $letter)),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Feature => 'Feature',
            self::Refacto => 'Refacto',
            self::Tech => 'Tech',
        };
    }
}
