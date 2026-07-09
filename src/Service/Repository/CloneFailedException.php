<?php

declare(strict_types=1);

namespace App\Service\Repository;

/**
 * Échec métier d'un {@see RepositoryClonerInterface} : dépôt injoignable, token refusé,
 * binaire `git` absent, délai dépassé….
 *
 * Son message est une raison **lisible** destinée à `Project::lastCloneError` : il ne doit
 * contenir ni token ni URL crédentialisée. {@see \App\MessageHandler\CloneRepositoryHandler}
 * le traduit en {@see \App\Enum\Type\CloneStatus::Failed} sans le re-propager (pas de retry).
 */
final class CloneFailedException extends \RuntimeException
{
}
