<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Enum\Type\Provider;
use App\Service\Github\FakeRepositoryCatalog;
use App\Service\Github\StoryTree;
use App\Service\Repository\RepositoryReaderInterface;
use App\Service\RepositoryUrl;

/**
 * Reader GitHub déterministe pour les tests fonctionnels et E2E : il remplace
 * {@see \App\Service\Github\GitHubRepositoryReader} en environnement `test` afin
 * qu'aucun appel réseau réel ne soit jamais émis.
 *
 * Le scénario est décidé par le nom du dépôt via {@see FakeRepositoryCatalog}, source
 * partagée avec le décorateur dev {@see \App\Service\Github\DevFakeRepositoryReader}.
 */
final class StubRepositoryReader implements RepositoryReaderInterface
{
    public function supports(Provider $provider): bool
    {
        return Provider::GitHub === $provider;
    }

    public function readStoryTree(RepositoryUrl $url, string $plainToken): StoryTree
    {
        return FakeRepositoryCatalog::treeFor($url);
    }

    public function readFile(RepositoryUrl $url, string $plainToken, string $path): string
    {
        return FakeRepositoryCatalog::fileContent($url, $path);
    }

    public function readStoryMetadata(RepositoryUrl $url, string $plainToken, array $storyIds): array
    {
        return FakeRepositoryCatalog::metadataFor($url, $storyIds);
    }
}
