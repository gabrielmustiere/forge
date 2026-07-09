<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Enum\Type\Provider;
use App\Service\Github\PullRequestFailedException;
use App\Service\Github\PullRequestOpenerInterface;
use App\Service\RepositoryUrl;

/**
 * Ouvreur de PR déterministe pour les tests : remplace {@see \App\Service\Github\GitHubPullRequestOpener}
 * en environnement `test` (config/services_test.yaml) — aucun appel réseau GitHub réel. Reste
 * taggé `app.pull_request_opener`, donc résolu normalement par le registry.
 *
 * Une branche contenant `pr-fail` simule un refus d'ouverture ; sinon la PR « s'ouvre » et
 * renvoie une URL déterministe.
 */
final class FakePullRequestOpener implements PullRequestOpenerInterface
{
    public function supports(Provider $provider): bool
    {
        return Provider::GitHub === $provider;
    }

    public function open(RepositoryUrl $url, #[\SensitiveParameter] string $plainToken, string $head, string $title, string $body): string
    {
        if (str_contains($head, 'pr-fail')) {
            throw new PullRequestFailedException('GitHub refuse la proposition (simulé).');
        }

        return sprintf('https://github.com/%s/pull/1', $url->name());
    }
}
