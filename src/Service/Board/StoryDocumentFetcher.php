<?php

declare(strict_types=1);

namespace App\Service\Board;

use App\Entity\Project;
use App\Service\InvalidRepositoryUrlException;
use App\Service\Repository\RepositoryReaderException;
use App\Service\Repository\RepositoryReaderRegistry;
use App\Service\RepositoryUrlNormalizer;
use App\Service\TokenCipher;

/**
 * Lit le contenu brut d'un document d'une story pour l'afficher dans le drawer.
 *
 * Même patron que {@see ProjectBoardBuilder} : normalisation d'URL, déchiffrement du
 * token au plus près de l'appel, lecture distante d'un seul fichier. Le chemin lu est
 * strictement borné à `docs/story/{storyId}/{filename}` — le contrôleur ayant déjà
 * validé les deux segments (anti-traversée). Toute erreur bas niveau est absorbée en
 * {@see StoryDocumentUnavailableException} (règle 10).
 */
final readonly class StoryDocumentFetcher
{
    public function __construct(
        private RepositoryReaderRegistry $registry,
        private RepositoryUrlNormalizer $normalizer,
        private TokenCipher $cipher,
    ) {
    }

    /**
     * @param string $storyId  identifiant `NNN-<f|r|t>-<slug>` déjà validé par l'appelant
     * @param string $filename nom de fichier `.md` déjà validé par l'appelant (jamais `/` ni `..`)
     *
     * @throws StoryDocumentUnavailableException si le document ne peut être lu
     */
    public function fetch(Project $project, string $storyId, string $filename): string
    {
        $reader = $this->registry->readerFor($project->getProvider());

        if (null === $reader) {
            throw new StoryDocumentUnavailableException('Lecture du dépôt non supportée pour ce provider.');
        }

        try {
            $url = $this->normalizer->normalize($project->getUrl());

            return $reader->readFile(
                $url,
                $this->cipher->decrypt($project->getToken()),
                sprintf('docs/story/%s/%s', $storyId, $filename),
            );
        } catch (RepositoryReaderException|InvalidRepositoryUrlException $e) {
            throw new StoryDocumentUnavailableException('Document introuvable ou illisible.', previous: $e);
        }
    }
}
