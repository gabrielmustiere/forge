<?php

declare(strict_types=1);

namespace App\Service\Repository;

/**
 * Marqueur des erreurs métier remontées par un {@see RepositoryReaderInterface}.
 *
 * Ces exceptions ne sont pas des erreurs applicatives : {@see ProjectVerifier} les
 * traduit en {@see \App\Enum\Type\VerificationStatus} affiché calmement.
 */
interface RepositoryReaderException extends \Throwable
{
}
