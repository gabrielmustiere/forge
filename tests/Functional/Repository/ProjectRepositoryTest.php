<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Project;
use App\Enum\Type\Provider;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProjectRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ProjectRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $repository = $container->get(ProjectRepository::class);
        \assert($em instanceof EntityManagerInterface);
        \assert($repository instanceof ProjectRepository);
        $this->em = $em;
        $this->repository = $repository;

        $this->em->createQuery('DELETE FROM ' . Project::class)->execute();
    }

    public function testFindAllOrderedReturnsNewestFirst(): void
    {
        $first = $this->persist(Provider::GitHub, 'https://github.com/acme/first', 'acme/first');
        $second = $this->persist(Provider::GitHub, 'https://github.com/acme/second', 'acme/second');

        $ordered = $this->repository->findAllOrdered();

        self::assertCount(2, $ordered);
        self::assertSame($second->getId(), $ordered[0]->getId());
        self::assertSame($first->getId(), $ordered[1]->getId());
    }

    public function testFindOneByNormalizedUrl(): void
    {
        $project = $this->persist(Provider::GitLab, 'https://gitlab.com/acme/widget', 'acme/widget');

        self::assertSame($project->getId(), $this->repository->findOneByNormalizedUrl('https://gitlab.com/acme/widget')?->getId());
        self::assertNull($this->repository->findOneByNormalizedUrl('https://gitlab.com/acme/unknown'));
    }

    public function testExistsByNormalizedUrlHonoursExclusion(): void
    {
        $project = $this->persist(Provider::GitHub, 'https://github.com/acme/widget', 'acme/widget');

        self::assertTrue($this->repository->existsByNormalizedUrl('https://github.com/acme/widget'));
        self::assertFalse($this->repository->existsByNormalizedUrl('https://github.com/acme/widget', $project->getId()));
        self::assertFalse($this->repository->existsByNormalizedUrl('https://github.com/acme/other'));
    }

    private function persist(Provider $provider, string $url, string $name): Project
    {
        $project = new Project($provider, $url, $name, 'cipher');
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }
}
