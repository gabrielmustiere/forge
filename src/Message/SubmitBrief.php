<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Ordre asynchrone de publier le brief en proposition de revue (branche dédiée + PR draft,
 * story 009). Ne porte que l'identifiant : le handler recharge l'interview et déchiffre le
 * token au plus près de l'exécution.
 */
final readonly class SubmitBrief
{
    public function __construct(
        public int $interviewId,
    ) {
    }
}
