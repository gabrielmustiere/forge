<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Repository;

use App\Enum\Type\Provider;
use App\Service\Repository\ClonePathResolver;
use App\Service\Repository\InvalidCloneDestinationException;
use App\Service\RepositoryUrl;
use PHPUnit\Framework\TestCase;

final class ClonePathResolverTest extends TestCase
{
    private const BASE_DIR = '/app/private';

    public function testResolvesOwnerAndRepoToADedicatedFolder(): void
    {
        $resolver = new ClonePathResolver(self::BASE_DIR);

        $path = $resolver->resolve($this->url('acme', 'widget'));

        self::assertSame('/app/private/acme-widget', $path);
    }

    public function testFlattensGitLabSubgroupsIntoASingleFolder(): void
    {
        $resolver = new ClonePathResolver(self::BASE_DIR);

        // GitLab autorise les sous-groupes : `repo` contient alors des `/`.
        $path = $resolver->resolve($this->url('acme', 'group/sub/widget', Provider::GitLab));

        self::assertSame('/app/private/acme-group-sub-widget', $path);
    }

    public function testRejectsATraversalSegment(): void
    {
        $resolver = new ClonePathResolver(self::BASE_DIR);

        $this->expectException(InvalidCloneDestinationException::class);
        $resolver->resolve($this->url('..', 'widget'));
    }

    private function url(string $owner, string $repo, Provider $provider = Provider::GitHub): RepositoryUrl
    {
        return new RepositoryUrl(
            $provider,
            $owner,
            $repo,
            sprintf('https://%s/%s/%s', $provider->host(), $owner, $repo),
        );
    }
}
