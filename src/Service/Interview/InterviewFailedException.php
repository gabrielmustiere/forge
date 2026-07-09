<?php

declare(strict_types=1);

namespace App\Service\Interview;

/**
 * Échec d'un tour d'interview (story 009) : binaire `claude` absent, délai dépassé, session
 * introuvable, sortie illisible. Le message est lisible et exempt de secret — il alimente
 * {@see \App\Entity\Interview::markFailed()}.
 */
final class InterviewFailedException extends \RuntimeException
{
}
