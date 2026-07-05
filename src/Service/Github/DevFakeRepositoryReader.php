<?php

declare(strict_types=1);

namespace App\Service\Github;

use App\Enum\Type\Provider;
use App\Service\Repository\RepositoryReaderInterface;
use App\Service\RepositoryUrl;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * Décorateur dev-only du reader GitHub : sert des données factices déterministes quand
 * `APP_FAKE_REPOSITORY_READER=1`, sinon délègue au vrai reader.
 *
 * Existe uniquement en environnement `dev` ({@see When}) pour rendre l'E2E du board
 * reproductible sans dépendre d'un dépôt réel. Désactivé par défaut (flag à 0) : le
 * dogfooding local lit alors les vrais dépôts GitHub, comportement inchangé. En `test`,
 * ce décorateur n'existe pas — c'est {@see \App\Tests\Double\StubRepositoryReader} qui
 * neutralise le réseau. Les deux s'appuient sur {@see FakeRepositoryCatalog}.
 */
#[When('dev')]
#[AsDecorator(GitHubRepositoryReader::class)]
final readonly class DevFakeRepositoryReader implements RepositoryReaderInterface
{
    public function __construct(
        #[AutowireDecorated]
        private RepositoryReaderInterface $inner,
        #[Autowire('%env(bool:APP_FAKE_REPOSITORY_READER)%')]
        private bool $fake,
    ) {
    }

    public function supports(Provider $provider): bool
    {
        return $this->inner->supports($provider);
    }

    public function readStoryTree(RepositoryUrl $url, string $plainToken): StoryTree
    {
        return $this->fake
            ? FakeRepositoryCatalog::treeFor($url)
            : $this->inner->readStoryTree($url, $plainToken);
    }

    public function readFile(RepositoryUrl $url, string $plainToken, string $path): string
    {
        return $this->fake
            ? FakeRepositoryCatalog::fileContent($url, $path)
            : $this->inner->readFile($url, $plainToken, $path);
    }

    public function readStoryMetadata(RepositoryUrl $url, string $plainToken, array $storyIds): array
    {
        return $this->fake
            ? FakeRepositoryCatalog::metadataFor($url, $storyIds)
            : $this->inner->readStoryMetadata($url, $plainToken, $storyIds);
    }
}
