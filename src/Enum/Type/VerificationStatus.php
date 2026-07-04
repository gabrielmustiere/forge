<?php

declare(strict_types=1);

namespace App\Enum\Type;

/**
 * Résultat de la vérification d'accès d'un projet à son dépôt distant.
 *
 * Persisté sur {@see \App\Entity\Project} ; l'affichage (liste + fiche) le lit en base,
 * sans rappeler le provider à chaque rendu. La présentation ({@see label()}, {@see badgeTone()},
 * {@see icon()}) suit le même pattern que {@see Provider}.
 */
enum VerificationStatus: string
{
    /** État initial : aucune vérification n'a encore eu lieu. */
    case Unverified = 'unverified';
    /** Repo joignable, token accepté, au moins une story `docs/story/` conforme. */
    case Eligible = 'eligible';
    /** Repo joignable mais aucune story `docs/story/` conforme : pas un dépôt forge. */
    case NotForge = 'not_forge';
    /** Un appel a échoué en 401/403 : token invalide ou accès refusé. */
    case InvalidToken = 'invalid_token';
    /** Repo/branche introuvable, réseau, timeout ou quota dépassé. */
    case Unreachable = 'unreachable';
    /** Provider sans connecteur de lecture (GitLab, tant que la V2 n'existe pas). */
    case UnsupportedProvider = 'unsupported_provider';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Non vérifié',
            self::Eligible => 'Éligible',
            self::NotForge => 'Non-forge',
            self::InvalidToken => 'Token invalide',
            self::Unreachable => 'Injoignable',
            self::UnsupportedProvider => 'Provider non scannable',
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
            self::Eligible => 'ok',
            self::NotForge => 'warning',
            self::InvalidToken, self::Unreachable => 'danger',
            self::Unverified, self::UnsupportedProvider => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Unverified => 'tabler:circle-dashed',
            self::Eligible => 'tabler:circle-check',
            self::NotForge => 'tabler:folder-off',
            self::InvalidToken => 'tabler:key-off',
            self::Unreachable => 'tabler:plug-connected-x',
            self::UnsupportedProvider => 'tabler:ban',
        };
    }
}
