<?php

declare(strict_types=1);

namespace App\Service\Repository;

use App\Service\RepositoryUrl;

/**
 * Rapatrie (ou met à jour) la copie locale d'un dépôt distant.
 *
 * Une seule implémentation ({@see GitRepositoryCloner}) : `git clone`/`git pull` est
 * identique entre GitHub et GitLab, pas de registry par provider (contrairement au
 * {@see RepositoryReaderInterface}). Le port isole le shell-out git → substituable par un
 * double en test, sans appel réseau réel.
 */
interface RepositoryClonerInterface
{
    /**
     * Assure que `$destination` contient une copie à jour du dépôt : `git clone` si le
     * dossier n'est pas encore un dépôt git, `git pull` sinon. Idempotent : une double
     * livraison Messenger est sans effet destructeur.
     *
     * @param string $plainToken  token en clair, injecté au process via l'environnement
     *                            (jamais en argv ni dans `.git/config`), puis oublié
     * @param string $destination chemin absolu du dossier de clone local
     *
     * @throws CloneFailedException dépôt injoignable, token refusé, `git` absent, délai dépassé
     */
    public function synchronize(RepositoryUrl $url, #[\SensitiveParameter] string $plainToken, string $destination): void;
}
