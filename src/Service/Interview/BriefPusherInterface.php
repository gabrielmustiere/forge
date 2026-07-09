<?php

declare(strict_types=1);

namespace App\Service\Interview;

use App\Service\RepositoryUrl;

/**
 * Publie le brief produit sur une **branche dédiée** du dépôt distant, sans jamais toucher la
 * branche principale ni le clone maintenu par la story 008 (règle métier 7).
 *
 * Une seule implémentation ({@see GitBriefPusher}) : le push git est identique GitHub/GitLab
 * (l'ouverture de PR/MR, elle, diffère → {@see \App\Service\Github\PullRequestOpenerInterface}).
 * Le port isole le shell-out git → substituable par un double en test, sans `git`/réseau réel.
 */
interface BriefPusherInterface
{
    /**
     * Dépose le dossier de story `docs/story/<storySlug>/` (non suivi dans le clone maintenu)
     * sur une branche dédiée poussée sur le distant, et retourne le nom de cette branche.
     *
     * Opère sur une **copie de travail isolée** du clone (le clone maintenu reste intact) :
     * l'échec préserve donc le brief local, re-tentable.
     *
     * @param string $cloneDir   chemin absolu du clone maintenu (source du dossier de story)
     * @param string $storySlug  slug `NNN-f-<slug>` de la story à publier
     * @param string $plainToken token en clair (droit d'écriture), injecté hors argv, puis oublié
     *
     * @return string nom de la branche poussée (source de la proposition de revue)
     *
     * @throws BriefPushFailedException token refusé, réseau, conflit, `git` absent, brief absent
     */
    public function push(string $cloneDir, string $storySlug, #[\SensitiveParameter] string $plainToken, RepositoryUrl $url): string;
}
