<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Project;
use App\Manager\ProjectManager;
use App\Repository\ProjectRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ProjectList
{
    use DefaultActionTrait;

    /** Identifiant du projet dont la suppression est en attente de confirmation. */
    #[LiveProp(writable: true)]
    public ?int $confirmingId = null;

    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly ProjectManager $manager,
    ) {
    }

    /**
     * @return list<Project>
     */
    public function getProjects(): array
    {
        return $this->projects->findAllOrdered();
    }

    public function getConfirming(): ?Project
    {
        return null !== $this->confirmingId ? $this->projects->find($this->confirmingId) : null;
    }

    #[LiveAction]
    public function confirmDelete(#[LiveArg] int $id): void
    {
        $this->confirmingId = $id;
    }

    #[LiveAction]
    public function cancelDelete(): void
    {
        $this->confirmingId = null;
    }

    #[LiveAction]
    public function delete(): void
    {
        $project = $this->getConfirming();

        if (null !== $project) {
            $this->manager->delete($project);
        }

        $this->confirmingId = null;
    }
}
