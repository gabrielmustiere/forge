<?php

declare(strict_types=1);

namespace App\Service\Github;

/**
 * Échec d'ouverture d'une proposition de revue (story 009) : token sans droit d'écriture,
 * quota, réseau, branche introuvable côté distant, réponse illisible. Message lisible et
 * exempt de secret — il alimente {@see \App\Entity\Interview::markFailed()}.
 */
final class PullRequestFailedException extends \RuntimeException
{
}
