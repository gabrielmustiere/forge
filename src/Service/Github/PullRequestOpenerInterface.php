<?php

declare(strict_types=1);

namespace App\Service\Github;

use App\Enum\Type\Provider;
use App\Service\RepositoryUrl;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Ouvre une proposition de revue en **brouillon** (PR/MR draft) sur le dépôt distant.
 *
 * Une implémentation par provider (GitHub aujourd'hui, GitLab en suivant — l'ouverture de
 * MR/PR diffère par provider, règle métier 9), sélectionnée par {@see PullRequestOpenerRegistry}
 * via {@see supports()}. Toutes les implémentations sont taguées automatiquement
 * (`app.pull_request_opener`) pour alimenter le registry — miroir de {@see \App\Service\Repository\RepositoryReaderInterface}.
 */
#[AutoconfigureTag('app.pull_request_opener')]
interface PullRequestOpenerInterface
{
    public function supports(Provider $provider): bool;

    /**
     * Ouvre la proposition en brouillon depuis la branche `$head` vers la branche par défaut
     * du dépôt et retourne l'URL de la proposition. Jamais de merge : on ouvre, on ne pilote pas.
     *
     * @param string $plainToken token en clair (droit d'écriture requis), en `auth_bearer`, puis oublié
     * @param string $head       nom de la branche source déjà poussée sur le distant
     * @param string $title      titre de la proposition
     * @param string $body       corps (markdown) de la proposition
     *
     * @return string URL web de la proposition ouverte
     *
     * @throws PullRequestFailedException accès refusé, quota, réseau, branche/dépôt introuvable, réponse illisible
     */
    public function open(RepositoryUrl $url, #[\SensitiveParameter] string $plainToken, string $head, string $title, string $body): string;
}
