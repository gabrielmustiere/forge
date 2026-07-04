<?php

declare(strict_types=1);

namespace App\Service\Repository;

use App\Enum\Type\VerificationStatus;

/**
 * Résultat d'une vérification produite par {@see ProjectVerifier} : le statut et son
 * horodatage, cohérents entre eux. Le caller l'applique sur le projet
 * (via {@see \App\Entity\Project::applyVerification()}) puis persiste.
 */
final readonly class VerificationResult
{
    public function __construct(
        public VerificationStatus $status,
        public \DateTimeImmutable $verifiedAt,
    ) {
    }
}
