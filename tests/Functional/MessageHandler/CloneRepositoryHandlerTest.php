<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Project;
use App\Enum\Type\CloneStatus;
use App\Enum\Type\Provider;
use App\Message\CloneRepository;
use App\MessageHandler\CloneRepositoryHandler;
use App\Repository\ProjectRepository;
use App\Service\TokenCipher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Le cloner réel est remplacé par {@see \App\Tests\Double\FakeRepositoryCloner} en test :
 * le scénario (succès / échec) est piloté par le nom du dépôt, aucun `git` réel n'est lancé.
 */
final class CloneRepositoryHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private TokenCipher $cipher;
    private ProjectRepository $projects;
    private CloneRepositoryHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $cipher = $container->get(TokenCipher::class);
        $projects = $container->get(ProjectRepository::class);
        $handler = $container->get(CloneRepositoryHandler::class);
        \assert($em instanceof EntityManagerInterface);
        \assert($cipher instanceof TokenCipher);
        \assert($projects instanceof ProjectRepository);
        \assert($handler instanceof CloneRepositoryHandler);
        $this->em = $em;
        $this->cipher = $cipher;
        $this->projects = $projects;
        $this->handler = $handler;

        $this->em->createQuery('DELETE FROM ' . Project::class)->execute();
    }

    public function testSuccessMarksClonedWithLocalPathAndTimestamp(): void
    {
        $id = $this->persistProject('https://github.com/acme/cloneable', 'acme/cloneable');

        ($this->handler)(new CloneRepository($id));

        $this->em->clear();
        $project = $this->projects->find($id);
        self::assertNotNull($project);
        self::assertSame(CloneStatus::Cloned, $project->getCloneStatus());
        self::assertNotNull($project->getClonedAt());
        self::assertNotNull($project->getLocalPath());
        self::assertStringEndsWith('/private/acme-cloneable', (string) $project->getLocalPath());
        self::assertNull($project->getLastCloneError());
    }

    public function testBusinessFailureMarksFailedWithoutPropagating(): void
    {
        // Le nom `clone-fail` force le double à lever une CloneFailedException.
        $id = $this->persistProject('https://github.com/acme/clone-fail', 'acme/clone-fail');

        // Aucune exception ne doit remonter (pas de retry Messenger inutile).
        ($this->handler)(new CloneRepository($id));

        $this->em->clear();
        $project = $this->projects->find($id);
        self::assertNotNull($project);
        self::assertSame(CloneStatus::Failed, $project->getCloneStatus());
        self::assertNotNull($project->getLastCloneError());
        self::assertNotSame('', $project->getLastCloneError());
        self::assertNull($project->getClonedAt());
    }

    public function testUnknownProjectIsANoOp(): void
    {
        // Projet supprimé entre dispatch et consommation : le handler ne doit pas planter.
        ($this->handler)(new CloneRepository(999_999));

        $this->expectNotToPerformAssertions();
    }

    private function persistProject(string $url, string $name): int
    {
        $project = new Project(Provider::GitHub, $url, $name, $this->cipher->encrypt('token'));
        $this->em->persist($project);
        $this->em->flush();

        return $project->getId() ?? throw new \LogicException('Projet non persisté.');
    }
}
