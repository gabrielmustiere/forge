<?php

declare(strict_types=1);

namespace App\Manager;

/**
 * Une action d'interview viole une règle métier (story 009) : projet non cloné (règle 1),
 * une interview déjà active sur le projet (règle 2), ou transition invalide. Message lisible,
 * destiné à un retour utilisateur (flash / erreur de composant).
 */
final class InterviewNotAllowedException extends \RuntimeException
{
}
