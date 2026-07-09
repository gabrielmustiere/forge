<?php

declare(strict_types=1);

namespace App\Service\Repository;

/**
 * Le dossier de clone dérivé de l'`owner`/`repo` est invalide (segment douteux, traversée `..`).
 *
 * Levée par {@see ClonePathResolver::resolve()} ; {@see \App\MessageHandler\CloneRepositoryHandler}
 * la traduit en {@see \App\Enum\Type\CloneStatus::Failed}. Étend `\InvalidArgumentException` :
 * c'est bien une entrée invalide, mais le type dédié évite d'avaler par erreur un
 * `\InvalidArgumentException` générique surgi ailleurs dans le handler.
 */
final class InvalidCloneDestinationException extends \InvalidArgumentException
{
}
