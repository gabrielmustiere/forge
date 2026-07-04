<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Valide, au niveau du DTO projet, la sémantique de l'URL de dépôt :
 * format reconnu, cohérence avec le provider sélectionné, et unicité de la forme normalisée.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class UniqueRepositoryUrl extends Constraint
{
    public string $invalidMessage = 'Cette URL de dépôt est invalide ou non reconnue.';
    public string $providerMismatchMessage = 'Cette URL ne correspond pas au provider sélectionné.';
    public string $duplicateMessage = 'Ce dépôt est déjà suivi.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
