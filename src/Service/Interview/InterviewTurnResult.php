<?php

declare(strict_types=1);

namespace App\Service\Interview;

/**
 * Résultat d'un tour d'interview (story 009) : la réponse du skill et le coût du tour.
 *
 * `costUsd` provient du champ `total_cost_usd` du JSON de sortie de `claude` (ADR-0002,
 * Driver 4) : traçable à la source, non persisté en V1.
 */
final readonly class InterviewTurnResult
{
    public function __construct(
        public string $assistantText,
        public float $costUsd,
    ) {
    }
}
