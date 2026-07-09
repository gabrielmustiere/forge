<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Service\Repository\CloneFailedException;
use App\Service\Repository\RepositoryClonerInterface;
use App\Service\RepositoryUrl;

/**
 * Cloner déterministe pour les tests : remplace {@see \App\Service\Repository\GitRepositoryCloner}
 * en environnement `test` (config/services_test.yaml) afin qu'aucun `git`/réseau réel ne soit
 * jamais lancé.
 *
 * Le scénario est piloté par le nom du dépôt (comme {@see StubRepositoryReader} pour la lecture) :
 * un `repo` contenant `clone-fail` simule un échec, sinon le clone « réussit » sans écrire de
 * fichiers (le handler ne persiste que le chemin, pas le contenu).
 */
final class FakeRepositoryCloner implements RepositoryClonerInterface
{
    public function synchronize(RepositoryUrl $url, #[\SensitiveParameter] string $plainToken, string $destination): void
    {
        if (str_contains($url->repo, 'clone-fail')) {
            throw new CloneFailedException('git a échoué : dépôt injoignable (simulé).');
        }
    }
}
