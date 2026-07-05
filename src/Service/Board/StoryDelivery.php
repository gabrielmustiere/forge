<?php

declare(strict_types=1);

namespace App\Service\Board;

/**
 * Le lien de livraison d'une story (source : `metadata.json`, champ `delivery`) : la release
 * et le commit qui l'ont livrée, écrits par les skills `commit`/`release`. Value object
 * immuable produit par {@see StoryMetadataParser}.
 *
 * Les deux champs sont indépendamment nullables : le commit arrive à la clôture, le tag de
 * release parfois plus tard (règle métier 8). Un commit présent sans release est un état
 * valide — la carte affiche « livré » sans numéro de version.
 */
final readonly class StoryDelivery
{
    public function __construct(
        public ?string $release,
        public ?string $commit,
    ) {
    }

    /**
     * Vrai dès qu'au moins un maillon de livraison est renseigné (commit ou release).
     */
    public function isDelivered(): bool
    {
        return null !== $this->commit || null !== $this->release;
    }
}
