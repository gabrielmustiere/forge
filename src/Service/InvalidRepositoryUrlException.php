<?php

declare(strict_types=1);

namespace App\Service;

final class InvalidRepositoryUrlException extends \InvalidArgumentException
{
    public function __construct(string $url)
    {
        parent::__construct(sprintf('URL de dépôt invalide ou non reconnue : "%s".', $url));
    }
}
