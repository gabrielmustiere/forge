<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * @return list<Project>
     */
    public function findAllOrdered(): array
    {
        /** @var list<Project> $projects */
        $projects = $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $projects;
    }

    public function findOneByNormalizedUrl(string $normalizedUrl): ?Project
    {
        return $this->findOneBy(['url' => $normalizedUrl]);
    }

    public function existsByNormalizedUrl(string $normalizedUrl, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('1')
            ->where('p.url = :url')
            ->setParameter('url', $normalizedUrl)
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb->andWhere('p.id != :id')->setParameter('id', $excludeId);
        }

        return [] !== $qb->getQuery()->getResult();
    }
}
