<?php

declare(strict_types=1);

namespace App\Service\Board;

/**
 * Résultat de la construction d'un board : un succès porteur du {@see Board}, ou un
 * échec porteur d'un message court.
 *
 * Garde-fou de la règle 10 : un scan raté (repo injoignable, token invalide) n'est
 * jamais une exception remontée au template mais un échec légitime — exactement comme
 * {@see \App\Service\Repository\ProjectVerifier} traduit un échec en statut. Le
 * diagnostic riche relève de `sync-manuelle`, hors périmètre ici.
 */
final readonly class BoardResult
{
    private function __construct(
        public ?Board $board,
        public ?string $failureReason,
    ) {
    }

    public static function success(Board $board): self
    {
        return new self($board, null);
    }

    public static function failure(string $reason): self
    {
        return new self(null, $reason);
    }

    public function isSuccess(): bool
    {
        return null !== $this->board;
    }
}
