<?php

declare(strict_types=1);

namespace App\Service\Repository;

/**
 * Le dépôt ou la branche est injoignable : 404, erreur réseau, timeout ou quota dépassé.
 * Traduit en {@see \App\Enum\Type\VerificationStatus::Unreachable}.
 */
final class RepositoryUnreachableException extends \RuntimeException implements RepositoryReaderException
{
}
