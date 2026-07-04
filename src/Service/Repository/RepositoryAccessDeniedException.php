<?php

declare(strict_types=1);

namespace App\Service\Repository;

/**
 * L'accès au dépôt est refusé : réponse 401/403 (token invalide, révoqué ou insuffisant).
 * Traduit en {@see \App\Enum\Type\VerificationStatus::InvalidToken}.
 */
final class RepositoryAccessDeniedException extends \RuntimeException implements RepositoryReaderException
{
}
