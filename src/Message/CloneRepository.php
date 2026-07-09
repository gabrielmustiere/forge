<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Ordre asynchrone de synchroniser la copie locale du dépôt d'un projet
 * (premier job async du Board). Ne porte que l'identifiant : le handler recharge le
 * projet et déchiffre le token au plus près de l'exécution.
 */
final readonly class CloneRepository
{
    public function __construct(
        public int $projectId,
    ) {
    }
}
