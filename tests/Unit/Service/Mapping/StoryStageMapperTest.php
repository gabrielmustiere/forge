<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Mapping;

use App\Enum\Type\PipelineStage;
use App\Service\Github\StoryFolder;
use App\Service\Mapping\StoryStageMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StoryStageMapperTest extends TestCase
{
    private StoryStageMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new StoryStageMapper();
    }

    /**
     * @param list<string> $files
     */
    #[DataProvider('provideMappings')]
    public function testStageFor(array $files, PipelineStage $expected): void
    {
        $folder = new StoryFolder('001-f-exemple', $files);

        self::assertSame($expected, $this->mapper->stageFor($folder));
    }

    /**
     * @return iterable<string, array{list<string>, PipelineStage}>
     */
    public static function provideMappings(): iterable
    {
        // Correspondance fichier déclencheur → colonne (règle #3).
        yield 'pitch seul → Cadrage' => [['pitch.md'], PipelineStage::Cadrage];
        yield 'plan → Planifié' => [['pitch.md', 'plan.md'], PipelineStage::Planifie];
        yield 'review → Review' => [['pitch.md', 'plan.md', 'review.md'], PipelineStage::Review];
        yield 'report → Livré' => [['pitch.md', 'plan.md', 'review.md', 'report.md'], PipelineStage::Livre];

        // Track r/t qui entre en Planifié sans jamais passer par Cadrage (règle #5) :
        // le mapper est track-agnostique, `plan.md` sans `pitch.md` classe en Planifié.
        yield 'refacto/tech avec plan seul → Planifié' => [['plan.md'], PipelineStage::Planifie];

        // Le plus avancé l'emporte, séquence incomplète tolérée (règle #4).
        yield 'report sans plan → Livré' => [['pitch.md', 'report.md'], PipelineStage::Livre];
        yield 'review sans plan → Review' => [['pitch.md', 'review.md'], PipelineStage::Review];

        // Transversaux ignorés (règle #7).
        yield 'plan + estimate → Planifié' => [['plan.md', 'estimate.md'], PipelineStage::Planifie];
        yield 'plan + brief + adr → Planifié' => [['brief.md', 'plan.md', '0001-adr-choix.md'], PipelineStage::Planifie];

        // Aucun fichier de pipeline → À vérifier (règle #6).
        yield 'brief seul → À vérifier' => [['brief.md'], PipelineStage::AVerifier];
        yield 'fichier inconnu → À vérifier' => [['notes.txt'], PipelineStage::AVerifier];
        yield 'dossier vide → À vérifier' => [[], PipelineStage::AVerifier];

        // Match top-level exact : un fichier en sous-dossier ne compte pas.
        yield 'pitch en sous-dossier → À vérifier' => [['feature-map/pitch.md'], PipelineStage::AVerifier];
        yield 'plan en sous-dossier ignoré, pitch top-level compte' => [['x/plan.md', 'pitch.md'], PipelineStage::Cadrage];
    }

    /**
     * Rejeu déterministe sur la forme des stories réelles du repo (001/002/003) :
     * chacune porte pitch+plan+review+report → toutes en Livré (critère d'acceptation,
     * validation de l'hypothèse critique #1).
     *
     * @param non-empty-string $id
     */
    #[DataProvider('provideRealStories')]
    public function testRealStoriesAreDelivered(string $id): void
    {
        $folder = new StoryFolder($id, ['pitch.md', 'plan.md', 'review.md', 'report.md']);

        self::assertSame(PipelineStage::Livre, $this->mapper->stageFor($folder));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideRealStories(): iterable
    {
        yield '001' => ['001-f-declaration-projet'];
        yield '002' => ['002-f-login'];
        yield '003' => ['003-f-connecteur-github-lecture'];
    }

    public function testStageForIsDeterministic(): void
    {
        $folder = new StoryFolder('001-f-exemple', ['pitch.md', 'plan.md']);

        $first = $this->mapper->stageFor($folder);
        $second = $this->mapper->stageFor($folder);

        self::assertSame($first, $second);
        self::assertSame(PipelineStage::Planifie, $first);
    }
}
