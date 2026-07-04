<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Project;
use App\Form\ProjectFormData;
use App\Service\RepositoryUrl;
use App\Service\RepositoryUrlNormalizer;
use App\Service\TokenCipher;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private RepositoryUrlNormalizer $normalizer,
        private TokenCipher $cipher,
    ) {
    }

    public function create(ProjectFormData $data): Project
    {
        $provider = $data->provider ?? throw new \LogicException('Provider requis.');
        $url = $data->url ?? throw new \LogicException('URL requise.');
        $plainToken = $data->plainToken ?? throw new \LogicException('Token requis.');

        $repositoryUrl = $this->normalizer->normalize($url);

        $project = new Project(
            $provider,
            $repositoryUrl->normalizedUrl,
            $this->resolveName($data->name, $repositoryUrl),
            $this->cipher->encrypt($plainToken),
        );

        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    public function update(Project $project, ProjectFormData $data): void
    {
        $provider = $data->provider ?? throw new \LogicException('Provider requis.');
        $url = $data->url ?? throw new \LogicException('URL requise.');

        $repositoryUrl = $this->normalizer->normalize($url);

        $project
            ->setProvider($provider)
            ->setUrl($repositoryUrl->normalizedUrl)
            ->setName($this->resolveName($data->name, $repositoryUrl));

        // Champ token laissé vide à l'édition → le token existant est conservé.
        if (null !== $data->plainToken && '' !== $data->plainToken) {
            $project->setToken($this->cipher->encrypt($data->plainToken));
        }

        $this->em->flush();
    }

    public function delete(Project $project): void
    {
        $this->em->remove($project);
        $this->em->flush();
    }

    private function resolveName(?string $name, RepositoryUrl $repositoryUrl): string
    {
        $name = trim($name ?? '');

        return '' !== $name ? $name : $repositoryUrl->name();
    }
}
