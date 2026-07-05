<?php

declare(strict_types=1);

namespace App\Service\Board;

/**
 * Un document de story n'a pas pu être lu (fichier disparu, dépôt injoignable, accès refusé).
 *
 * Exception « friendly » : {@see StoryDocumentFetcher} absorbe les erreurs bas niveau du
 * reader et n'expose que celle-ci, que le contrôleur traduit en message minimal dans le
 * turbo-frame du drawer — sans casser la page (cohérent règle 10).
 */
final class StoryDocumentUnavailableException extends \RuntimeException
{
}
