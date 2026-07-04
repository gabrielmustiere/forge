<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Project;
use App\Enum\Type\Provider;
use App\Validator\UniqueRepositoryUrl;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO de saisie du formulaire projet. Découple la représentation de saisie de l'entité :
 * le token n'est jamais hydraté depuis {@see Project}, donc jamais réémis vers le front.
 */
#[UniqueRepositoryUrl]
final class ProjectFormData
{
    public ?int $id = null;

    #[Assert\NotNull(message: 'Choisissez un provider.')]
    public ?Provider $provider = null;

    #[Assert\NotBlank(message: 'Renseignez l\'URL du dépôt.')]
    public ?string $url = null;

    public ?string $name = null;

    #[Assert\NotBlank(message: 'Fournissez un token de lecture.', groups: ['create'])]
    public ?string $plainToken = null;

    public static function fromProject(Project $project): self
    {
        $data = new self();
        $data->id = $project->getId();
        $data->provider = $project->getProvider();
        $data->url = $project->getUrl();
        $data->name = $project->getName();

        // plainToken volontairement laissé nul : le token n'est jamais renvoyé au formulaire.

        return $data;
    }
}
