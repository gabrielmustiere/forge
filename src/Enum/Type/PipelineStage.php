<?php

declare(strict_types=1);

namespace App\Enum\Type;

/**
 * Étape d'une story sur le pipeline unifié du board, déduite des fichiers présents.
 *
 * Vocabulaire pur, découplé de toute logique de fichiers : la correspondance
 * fichier → étape vit dans {@see \App\Service\Mapping\StoryStageMapper}, comme
 * {@see VerificationStatus} ignore les readers. Les cinq premiers cas forment le
 * pipeline ordonné du cycle de vie (Idée → Besoin → Cadré → Implémenté → Livré) ;
 * {@see AVerifier} est une voie à part pour les stories dont les fichiers ne
 * permettent pas de trancher.
 */
enum PipelineStage: string
{
    /** Une `brief.md` est présente et rien de plus avancé : idée dégrossie par interview. */
    case Idee = 'idee';
    /** Une `pitch.md` est présente : besoin cadré fonctionnellement. */
    case Besoin = 'besoin';
    /** Une `plan.md` est présente : solution conçue, prête à implémenter. */
    case Cadre = 'cadre';
    /** Une `review.md` est présente : code écrit et auto-relu. */
    case Implemente = 'implemente';
    /** Une `report.md` est présente : story livrée. */
    case Livre = 'livre';
    /** Aucun fichier de pipeline reconnu : hors des colonnes, à vérifier. */
    case AVerifier = 'a_verifier';

    public function label(): string
    {
        return match ($this) {
            self::Idee => 'Idée',
            self::Besoin => 'Besoin',
            self::Cadre => 'Cadré',
            self::Implemente => 'Implémenté',
            self::Livre => 'Livré',
            self::AVerifier => 'À vérifier',
        };
    }

    /**
     * Vrai pour les cinq colonnes du pipeline, faux pour {@see AVerifier}
     * (voie séparée). Sert à distinguer « rangé sur le pipeline » de « signalé ».
     */
    public function isOnPipeline(): bool
    {
        return match ($this) {
            self::Idee, self::Besoin, self::Cadre, self::Implemente, self::Livre => true,
            self::AVerifier => false,
        };
    }
}
