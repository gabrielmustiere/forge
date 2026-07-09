<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CloneRepository;
use App\Repository\ProjectRepository;
use App\Service\InvalidRepositoryUrlException;
use App\Service\Repository\CloneFailedException;
use App\Service\Repository\ClonePathResolver;
use App\Service\Repository\InvalidCloneDestinationException;
use App\Service\Repository\RepositoryClonerInterface;
use App\Service\RepositoryUrlNormalizer;
use App\Service\TokenCipher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Exécute le clone/pull hors requête HTTP et pose l'état final sur le projet.
 *
 * Un échec métier (token refusé, dépôt injoignable, `git` absent) est traduit en
 * {@see \App\Enum\Type\CloneStatus::Failed} avec une raison lisible et **n'est pas
 * re-propagé** : inutile de solliciter le retry Messenger pour une erreur non transitoire.
 * `synchronize()` étant idempotent, une double livraison est sans effet destructeur.
 */
#[AsMessageHandler]
final readonly class CloneRepositoryHandler
{
    public function __construct(
        private ProjectRepository $projects,
        private RepositoryClonerInterface $cloner,
        private ClonePathResolver $pathResolver,
        private RepositoryUrlNormalizer $normalizer,
        private TokenCipher $cipher,
        private EntityManagerInterface $em,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CloneRepository $message): void
    {
        $project = $this->projects->find($message->projectId);

        if (null === $project) {
            // Projet supprimé entre le dispatch et la consommation : rien à faire.
            return;
        }

        try {
            $url = $this->normalizer->normalize($project->getUrl());
            $destination = $this->pathResolver->resolve($url);

            $this->cloner->synchronize($url, $this->cipher->decrypt($project->getToken()), $destination);

            $project->markCloned($destination, $this->clock->now());
        } catch (CloneFailedException|InvalidRepositoryUrlException|InvalidCloneDestinationException $e) {
            $project->markCloneFailed($e->getMessage());
        }

        $this->em->flush();
    }
}
