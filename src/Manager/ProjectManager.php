<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Project;
use App\Form\ProjectFormData;
use App\Service\Repository\ProjectVerifier;
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
        private ProjectVerifier $verifier,
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
        $this->verify($project);
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

        $this->verify($project);
        $this->em->flush();
    }

    public function delete(Project $project): void
    {
        $this->em->remove($project);
        $this->em->flush();
    }

    /**
     * Re-déclenche la vérification d'accès d'un projet existant (bouton « vérifier l'accès »)
     * et persiste le nouveau statut, sans re-créer le projet.
     */
    public function reverify(Project $project): void
    {
        $this->verify($project);
        $this->em->flush();
    }

    /**
     * Vérifie l'accès distant et met à jour le statut sur le projet (effet de bord contrôlé
     * de create/update). Synchrone : un provider lent est borné par le timeout du client,
     * l'enregistrement aboutit toujours, seul le statut reflète un éventuel échec. Le flush
     * est laissé à l'appelant (même transaction que create/update).
     */
    private function verify(Project $project): void
    {
        $result = $this->verifier->verify($project);
        $project->applyVerification($result->status, $result->verifiedAt);
    }

    private function resolveName(?string $name, RepositoryUrl $repositoryUrl): string
    {
        $name = trim($name ?? '');

        return '' !== $name ? $name : $repositoryUrl->name();
    }
}
