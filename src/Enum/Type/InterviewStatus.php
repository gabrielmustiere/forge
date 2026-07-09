<?php

declare(strict_types=1);

namespace App\Enum\Type;

/**
 * État d'une interview de cadrage rattachée à un projet (story 009).
 *
 * Persisté sur {@see \App\Entity\Interview} et piloté par les jobs asynchrones
 * {@see \App\Message\RunInterviewTurn} (un tour de dialogue) et
 * {@see \App\Message\SubmitBrief} (dépôt en PR draft). La présentation
 * ({@see label()}, {@see badgeTone()}, {@see icon()}) suit le même pattern que
 * {@see CloneStatus}.
 *
 * Cycle de vie nominal :
 *   Awaiting → Thinking → Awaiting → … → BriefReady → Submitting → Submitted.
 * `Failed` est récupérable (l'utilisateur peut re-tenter) ; `Submitted` et `Abandoned`
 * sont terminaux (l'interview ne compte plus dans la règle « 1 active par projet »).
 */
enum InterviewStatus: string
{
    /** Le skill a rendu la main : on attend le prochain message de l'utilisateur. */
    case Awaiting = 'awaiting';
    /** Un tour (ou le dépôt du premier message) part en tâche de fond. */
    case Thinking = 'thinking';
    /** Le brief a été détecté sur le filesystem : en attente de validation. */
    case BriefReady = 'brief_ready';
    /** Le dépôt du brief est lancé (push + ouverture de PR draft). */
    case Submitting = 'submitting';
    /** PR draft ouverte : parcours terminé avec succès. */
    case Submitted = 'submitted';
    /** Le dernier tour ou le dépôt a échoué (raison lisible dans `lastError`) ; récupérable. */
    case Failed = 'failed';
    /** L'utilisateur a abandonné : terminal, libère le créneau « 1 active par projet ». */
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Awaiting => 'En attente',
            self::Thinking => 'Réflexion…',
            self::BriefReady => 'À valider',
            self::Submitting => 'Dépôt…',
            self::Submitted => 'Proposée',
            self::Failed => 'Échec',
            self::Abandoned => 'Abandonnée',
        };
    }

    /**
     * Tonalité sémantique du badge, mappée vers les classes de la DA côté template.
     *
     * @return 'ok'|'neutral'|'warning'|'danger'
     */
    public function badgeTone(): string
    {
        return match ($this) {
            self::Submitted => 'ok',
            self::BriefReady => 'warning',
            self::Failed => 'danger',
            self::Awaiting, self::Thinking, self::Submitting, self::Abandoned => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Awaiting => 'tabler:message-dots',
            self::Thinking => 'tabler:loader-2',
            self::BriefReady => 'tabler:file-check',
            self::Submitting => 'tabler:git-branch',
            self::Submitted => 'tabler:git-pull-request',
            self::Failed => 'tabler:alert-triangle',
            self::Abandoned => 'tabler:circle-x',
        };
    }

    /**
     * Une interview « active » occupe le créneau unique du projet : tout état hors terminal
     * ({@see Submitted}, {@see Abandoned}). Un `Failed` reste actif (re-tentable).
     */
    public function isActive(): bool
    {
        return self::Submitted !== $this && self::Abandoned !== $this;
    }

    /** Le worker travaille hors requête : l'UI poll jusqu'à la bascule ({@see Thinking}, {@see Submitting}). */
    public function isInFlight(): bool
    {
        return self::Thinking === $this || self::Submitting === $this;
    }
}
