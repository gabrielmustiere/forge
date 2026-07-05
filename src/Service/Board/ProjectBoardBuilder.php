<?php

declare(strict_types=1);

namespace App\Service\Board;

use App\Entity\Project;
use App\Service\Github\StoryFolder;
use App\Service\Github\StoryTree;
use App\Service\InvalidRepositoryUrlException;
use App\Service\Mapping\StoryStageMapper;
use App\Service\Repository\RepositoryAccessDeniedException;
use App\Service\Repository\RepositoryReaderInterface;
use App\Service\Repository\RepositoryReaderRegistry;
use App\Service\Repository\RepositoryUnreachableException;
use App\Service\RepositoryUrl;
use App\Service\RepositoryUrlNormalizer;
use App\Service\TokenCipher;

/**
 * Projette un {@see Project} en {@see Board} par un scan live du dépôt (règle 8).
 *
 * Même patron que {@see \App\Service\Repository\ProjectVerifier} : normalisation d'URL,
 * déchiffrement du token au plus près de l'appel, lecture distante, puis mapping via le
 * moteur `004` réutilisé tel quel. Les exceptions métier du reader sont catchées et
 * traduites en {@see BoardResult::failure()} — jamais remontées au template (garde-fou,
 * règle 10). L'écran ne recalcule aucune position : il lit ce que le builder projette.
 */
final readonly class ProjectBoardBuilder
{
    /** Documents mis en tête du drawer, du plus avancé au moins avancé (règle 6). */
    private const array DOC_PRECEDENCE = ['report.md', 'review.md', 'plan.md', 'pitch.md'];

    /**
     * Charset autorisé pour un document servable par le drawer. **Doit rester aligné** sur
     * le `requirements['filename']` de la route `app_project_story_doc` : un nom hors de ce
     * motif (majuscule, espace… ex. `README.md`) ferait échouer la génération d'URL
     * (`strict_requirements`) et casserait tout le rendu du board. On l'exclut donc en amont.
     */
    private const string DOCUMENT_NAME = '/^[a-z0-9._-]+\.md$/';

    public function __construct(
        private RepositoryReaderRegistry $registry,
        private RepositoryUrlNormalizer $normalizer,
        private TokenCipher $cipher,
        private StoryStageMapper $mapper,
        private StoryMetadataParser $metadataParser,
    ) {
    }

    public function build(Project $project): BoardResult
    {
        $reader = $this->registry->readerFor($project->getProvider());

        if (null === $reader) {
            return BoardResult::failure('Lecture du dépôt non supportée pour ce provider.');
        }

        try {
            $url = $this->normalizer->normalize($project->getUrl());
            $token = $this->cipher->decrypt($project->getToken());
            $tree = $reader->readStoryTree($url, $token);
        } catch (RepositoryAccessDeniedException) {
            return BoardResult::failure('Accès au dépôt refusé (token invalide ou insuffisant).');
        } catch (RepositoryUnreachableException|InvalidRepositoryUrlException) {
            return BoardResult::failure('Dépôt injoignable — impossible de charger le tableau.');
        }

        // Lecture groupée du metadata de toutes les stories (règle 10 : un seul appel).
        $metadata = $this->readMetadata($reader, $url, $token, $tree);

        /** @var array<string, list<StoryCard>> $columns */
        $columns = [];
        /** @var list<StoryCard> $banner */
        $banner = [];

        foreach ($tree->stories as $folder) {
            $stage = $this->mapper->stageFor($folder);
            $card = new StoryCard(
                StoryId::parse($folder->id),
                $stage,
                $this->documentsFor($folder->files()),
                $metadata[$folder->id] ?? null,
            );

            if ($stage->isOnPipeline()) {
                $columns[$stage->value][] = $card;
            } else {
                $banner[] = $card;
            }
        }

        foreach ($columns as $stageValue => $cards) {
            $columns[$stageValue] = $this->sortByNumberDesc($cards);
        }

        return BoardResult::success(new Board($columns, $this->sortByNumberDesc($banner)));
    }

    /**
     * Lit et parse en un seul appel groupé le metadata de toutes les stories de l'arbre.
     *
     * L'enrichissement ne doit jamais faire échouer un board déjà lisible : un échec de la
     * lecture groupée (réseau, quota…) dégrade toutes les cartes vers leur slug, sans casser
     * le tableau (règle 9). Chaque JSON est confié au parser tolérant (`null` si invalide).
     *
     * @return array<string, ?StoryMetadata> map storyId → métadonnées, `null` si absent/invalide
     */
    private function readMetadata(RepositoryReaderInterface $reader, RepositoryUrl $url, string $token, StoryTree $tree): array
    {
        $storyIds = array_map(static fn (StoryFolder $folder): string => $folder->id, $tree->stories);

        if ([] === $storyIds) {
            return [];
        }

        try {
            $raw = $reader->readStoryMetadata($url, $token, $storyIds);
        } catch (RepositoryAccessDeniedException|RepositoryUnreachableException) {
            return [];
        }

        $parsed = [];
        foreach ($raw as $storyId => $json) {
            $parsed[$storyId] = $this->metadataParser->parse($json);
        }

        return $parsed;
    }

    /**
     * Ordonne les documents d'une story pour le drawer : précédence forge d'abord,
     * puis les transversaux restants par ordre alphabétique. Ne garde que les fichiers
     * `.md` à la racine du dossier de story dont le nom est servable par la route
     * (cf. {@see DOCUMENT_NAME}) — le motif exclut aussi les sous-chemins (pas de `/`).
     *
     * @param list<string> $files
     *
     * @return list<string>
     */
    private function documentsFor(array $files): array
    {
        $docs = array_values(array_filter(
            $files,
            static fn (string $file): bool => 1 === preg_match(self::DOCUMENT_NAME, $file),
        ));

        $ordered = [];
        foreach (self::DOC_PRECEDENCE as $name) {
            if (\in_array($name, $docs, true)) {
                $ordered[] = $name;
            }
        }

        $rest = array_values(array_filter(
            $docs,
            static fn (string $file): bool => !\in_array($file, self::DOC_PRECEDENCE, true),
        ));
        sort($rest);

        return array_merge($ordered, $rest);
    }

    /**
     * @param list<StoryCard> $cards
     *
     * @return list<StoryCard>
     */
    private function sortByNumberDesc(array $cards): array
    {
        usort($cards, static fn (StoryCard $a, StoryCard $b): int => $b->id->number <=> $a->id->number);

        return $cards;
    }
}
