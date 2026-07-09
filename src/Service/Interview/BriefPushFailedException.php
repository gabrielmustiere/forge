<?php

declare(strict_types=1);

namespace App\Service\Interview;

/**
 * Échec de dépôt du brief sur le distant (story 009) : token en lecture seule (push refusé),
 * réseau, conflit, `git` absent. Message lisible et exempt de secret — il alimente
 * {@see \App\Entity\Interview::markFailed()}, le brief restant récupérable en local.
 */
final class BriefPushFailedException extends \RuntimeException
{
}
