<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Interview;
use App\Entity\Project;
use App\Enum\Type\InterviewStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interview>
 */
class InterviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interview::class);
    }

    /**
     * L'unique interview active d'un projet, ou `null` s'il n'y en a pas.
     *
     * « Active » = statut hors terminal ({@see InterviewStatus::Submitted},
     * {@see InterviewStatus::Abandoned}). Sert la règle métier « 1 active par projet » : la
     * garde de {@see \App\Manager\InterviewManager} refuse d'en démarrer une seconde tant que
     * celle-ci existe, et la fiche projet réhydrate le parcours en cours depuis cette méthode.
     */
    public function findActiveForProject(Project $project): ?Interview
    {
        /** @var Interview|null $interview */
        $interview = $this->createQueryBuilder('i')
            ->andWhere('i.project = :project')
            ->andWhere('i.status NOT IN (:terminal)')
            ->setParameter('project', $project)
            ->setParameter('terminal', [InterviewStatus::Submitted, InterviewStatus::Abandoned])
            ->orderBy('i.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $interview;
    }

    /**
     * La dernière interview d'un projet (active ou terminée), ou `null` s'il n'y en a jamais eu.
     *
     * Alimente l'affichage du parcours : le composant réhydrate ce fil à chaque cycle (le fil
     * courant, ou le dernier résultat — PR ouverte, abandon — avant d'en amorcer un nouveau).
     */
    public function findLatestForProject(Project $project): ?Interview
    {
        return $this->findOneBy(['project' => $project], ['id' => 'DESC']);
    }
}
