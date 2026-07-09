<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Ordre asynchrone d'exécuter un tour d'interview (story 009). Ne porte que l'identifiant :
 * le handler recharge l'interview, lit son dernier message utilisateur et déchiffre le token
 * au plus près de l'exécution.
 */
final readonly class RunInterviewTurn
{
    public function __construct(
        public int $interviewId,
    ) {
    }
}
