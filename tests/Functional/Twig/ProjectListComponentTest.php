<?php

declare(strict_types=1);

namespace App\Tests\Functional\Twig;

use App\Entity\Project;
use App\Enum\Type\Provider;
use App\Manager\ProjectManager;
use App\Repository\ProjectRepository;
use App\Service\TokenCipher;
use App\Twig\Components\ProjectList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProjectListComponentTest extends KernelTestCase
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

    public function testDeleteRemovesTheProjectAfterConfirmation(): void
    {
        $id = $this->persistProject()->getId();
        self::assertNotNull($id);

        $component = new ProjectList($this->projects, $this->manager);
        $component->confirmDelete($id);

        self::assertSame($id, $component->confirmingId);
        self::assertNotNull($component->getConfirming());

        $component->delete();

        self::assertNull($component->confirmingId);
        $this->em->clear();
        self::assertNull($this->projects->find($id));
    }

    public function testCancelKeepsTheProject(): void
    {
        $id = $this->persistProject()->getId();
        self::assertNotNull($id);

        $component = new ProjectList($this->projects, $this->manager);
        $component->confirmDelete($id);
        $component->cancelDelete();

        self::assertNull($component->confirmingId);
        $this->em->clear();
        self::assertNotNull($this->projects->find($id));
    }

    private function persistProject(): Project
    {
        $project = new Project(Provider::GitHub, 'https://github.com/acme/widget', 'acme/widget', $this->cipher->encrypt('token'));
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }
}
