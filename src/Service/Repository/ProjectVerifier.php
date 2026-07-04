<?php

declare(strict_types=1);

namespace App\Service\Repository;

use App\Entity\Project;
use App\Enum\Type\VerificationStatus;
use App\Service\InvalidRepositoryUrlException;
use App\Service\RepositoryUrlNormalizer;
use App\Service\TokenCipher;
use Psr\Clock\ClockInterface;

/**
 * Traduit l'accès distant d'un projet en {@see VerificationStatus} horodaté.
 *
 * Un provider sans reader (GitLab) donne `UnsupportedProvider` sans aucun appel réseau.
 * Sinon le token est déchiffré au plus près de l'appel (variable locale, jamais stockée),
 * et les exceptions métier du reader sont traduites en statut : jamais d'erreur remontée
 * à l'utilisateur, un échec est un statut légitime.
 */
final readonly class ProjectVerifier
{
    public function __construct(
        private RepositoryReaderRegistry $registry,
        private RepositoryUrlNormalizer $normalizer,
        private TokenCipher $cipher,
        private ClockInterface $clock,
    ) {
    }

    public function verify(Project $project): VerificationResult
    {
        $reader = $this->registry->readerFor($project->getProvider());

        if (null === $reader) {
            return new VerificationResult(VerificationStatus::UnsupportedProvider, $this->clock->now());
        }

        try {
            $url = $this->normalizer->normalize($project->getUrl());
            $tree = $reader->readStoryTree($url, $this->cipher->decrypt($project->getToken()));

            $status = $tree->hasStories() ? VerificationStatus::Eligible : VerificationStatus::NotForge;
        } catch (RepositoryAccessDeniedException) {
            $status = VerificationStatus::InvalidToken;
        } catch (RepositoryUnreachableException|InvalidRepositoryUrlException) {
            $status = VerificationStatus::Unreachable;
        }

        return new VerificationResult($status, $this->clock->now());
    }
}
