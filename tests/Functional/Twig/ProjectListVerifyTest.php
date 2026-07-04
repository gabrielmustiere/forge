<?php

declare(strict_types=1);

namespace App\Tests\Functional\Twig;

use App\Entity\Project;
use App\Enum\Type\Provider;
use App\Enum\Type\VerificationStatus;
use App\Manager\ProjectManager;
use App\Repository\ProjectRepository;
use App\Service\TokenCipher;
use App\Twig\Components\ProjectList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * La LiveAction `verify` s'appuie sur le reader neutralisé en env test
 * ({@see \App\Tests\Double\StubRepositoryReader}) : aucun appel réseau réel.
 * Le double décide du statut d'après le nom du dépôt.
 */
final class ProjectListVerifyTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ProjectRepository $projects;
    private ProjectManager $manager;
    private TokenCipher $cipher;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $projects = $container->get(ProjectRepository::class);
        $manager = $container->get(ProjectManager::class);
        $cipher = $container->get(TokenCipher::class);
        \assert($em instanceof EntityManagerInterface);
        \assert($projects instanceof ProjectRepository);
        \assert($manager instanceof ProjectManager);
        \assert($cipher instanceof TokenCipher);
        $this->em = $em;
        $this->projects = $projects;
        $this->manager = $manager;
        $this->cipher = $cipher;

        $this->em->createQuery('DELETE FROM ' . Project::class)->execute();
    }

    public function testVerifyMarksAnEligibleRepoAndPersistsStatus(): void
    {
        $id = $this->persist(Provider::GitHub, 'https://github.com/acme/eligible-app')->getId();
        self::assertNotNull($id);

        $this->component()->verify($id);

        $this->em->clear();
        $project = $this->projects->find($id);
        self::assertNotNull($project);
        self::assertSame(VerificationStatus::Eligible, $project->getVerificationStatus());
        self::assertNotNull($project->getVerifiedAt());
    }

    public function testVerifyMarksAnUnreadableRepoAsInvalidToken(): void
    {
        $id = $this->persist(Provider::GitHub, 'https://github.com/acme/denied-app')->getId();
        self::assertNotNull($id);

        $this->component()->verify($id);

        $this->em->clear();
        $project = $this->projects->find($id);
        self::assertNotNull($project);
        self::assertSame(VerificationStatus::InvalidToken, $project->getVerificationStatus());
    }

    public function testVerifyMarksGitLabAsUnsupportedWithoutNetwork(): void
    {
        $id = $this->persist(Provider::GitLab, 'https://gitlab.com/acme/whatever')->getId();
        self::assertNotNull($id);

        $this->component()->verify($id);

        $this->em->clear();
        $project = $this->projects->find($id);
        self::assertNotNull($project);
        self::assertSame(VerificationStatus::UnsupportedProvider, $project->getVerificationStatus());
    }

    private function component(): ProjectList
    {
        return new ProjectList($this->projects, $this->manager);
    }

    private function persist(Provider $provider, string $url): Project
    {
        // Persistance directe (sans passer par le manager) pour partir d'un statut Unverified.
        $project = new Project($provider, $url, 'acme/app', $this->cipher->encrypt('token'));
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }
}
