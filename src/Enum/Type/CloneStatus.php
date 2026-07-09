<?php

declare(strict_types=1);

namespace App\Enum\Type;

/**
 * État du clone local d'un projet (dossier `private/<owner>-<repo>`).
 *
 * Persisté sur {@see \App\Entity\Project} et piloté par le job asynchrone
 * {@see \App\Message\CloneRepository} : `NotCloned` tant qu'aucun clone n'a été demandé,
 * `Cloning` pendant l'exécution en tâche de fond, puis `Cloned` ou `Failed`.
 * La présentation ({@see label()}, {@see badgeTone()}, {@see icon()}) suit le même
 * pattern que {@see VerificationStatus}.
 */
enum CloneStatus: string
{
    /** État initial : aucun clone n'a encore été demandé. */
    case NotCloned = 'not_cloned';
    /** Clone/pull en cours dans le worker Messenger. */
    case Cloning = 'cloning';
    /** Copie locale à jour : dernier clone ou pull réussi. */
    case Cloned = 'cloned';
    /** Le dernier clone/pull a échoué (raison lisible dans `lastCloneError`). */
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::NotCloned => 'Non cloné',
            self::Cloning => 'Clonage…',
            self::Cloned => 'Cloné',
            self::Failed => 'Échec',
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
            self::Cloned => 'ok',
            self::Failed => 'danger',
            self::NotCloned, self::Cloning => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::NotCloned => 'tabler:cloud-off',
            self::Cloning => 'tabler:cloud-download',
            self::Cloned => 'tabler:cloud-check',
            self::Failed => 'tabler:cloud-x',
        };
    }
}
