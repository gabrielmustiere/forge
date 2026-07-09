<?php

declare(strict_types=1);

namespace App\Enum\Type;

/**
 * Auteur d'un tour de conversation d'interview (story 009).
 *
 * Persisté sur {@see \App\Entity\InterviewMessage} : `User` pour les messages saisis par
 * l'utilisateur, `Assistant` pour les réponses du skill `feature-interview`.
 */
enum MessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
