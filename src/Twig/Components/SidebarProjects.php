<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SidebarProjects
{
    public function __construct(
        private readonly ProjectRepository $projects,
    ) {
    }

    /**
     * @return list<Project>
     */
    public function getProjects(): array
    {
        return $this->projects->findAllOrdered();
    }
}
