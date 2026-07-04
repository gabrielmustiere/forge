<?php

declare(strict_types=1);

namespace App\Enum\Type;

/**
 * Étape d'une story sur le pipeline unifié du board, déduite des fichiers présents.
 *
 * Vocabulaire pur, découplé de toute logique de fichiers : la correspondance
 * fichier → étape vit dans {@see \App\Service\Mapping\StoryStageMapper}, comme
 * {@see VerificationStatus} ignore les readers. Les quatre premiers cas forment le
 * pipeline ordonné (Cadrage → Planifié → Review → Livré) ; {@see AVerifier} est une
 * voie à part pour les stories dont les fichiers ne permettent pas de trancher.
 */
enum PipelineStage: string
{
    /** Une `pitch.md` est présente et rien de plus avancé : story cadrée. */
    case Cadrage = 'cadrage';
    /** Une `plan.md` est présente : story planifiée. */
    case Planifie = 'planifie';
    /** Une `review.md` est présente : story en review. */
    case Review = 'review';
    /** Une `report.md` est présente : story livrée. */
    case Livre = 'livre';
    /** Aucun fichier de pipeline reconnu : hors des colonnes, à vérifier. */
    case AVerifier = 'a_verifier';

    public function label(): string
    {
        return match ($this) {
            self::Cadrage => 'Cadrage',
            self::Planifie => 'Planifié',
            self::Review => 'Review',
            self::Livre => 'Livré',
            self::AVerifier => 'À vérifier',
        };
    }

    /**
     * Vrai pour les quatre colonnes du pipeline, faux pour {@see AVerifier}
     * (voie séparée). Sert à distinguer « rangé sur le pipeline » de « signalé ».
     */
    public function isOnPipeline(): bool
    {
        return match ($this) {
            self::Cadrage, self::Planifie, self::Review, self::Livre => true,
            self::AVerifier => false,
        };
    }
}
