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
        // Correspondance fichier déclencheur → colonne du cycle de vie.
        yield 'brief seul → Idée' => [['brief.md'], PipelineStage::Idee];
        yield 'pitch → Besoin' => [['brief.md', 'pitch.md'], PipelineStage::Besoin];
        yield 'plan → Cadré' => [['brief.md', 'pitch.md', 'plan.md'], PipelineStage::Cadre];
        yield 'review → Implémenté' => [['pitch.md', 'plan.md', 'review.md'], PipelineStage::Implemente];
        yield 'report → Livré' => [['pitch.md', 'plan.md', 'review.md', 'report.md'], PipelineStage::Livre];

        // Track r/t qui entre en Cadré sans passer par Idée/Besoin :
        // le mapper est track-agnostique, `plan.md` sans amont classe en Cadré.
        yield 'refacto/tech avec plan seul → Cadré' => [['plan.md'], PipelineStage::Cadre];

        // Le plus avancé l'emporte, séquence incomplète tolérée.
        yield 'report sans plan → Livré' => [['pitch.md', 'report.md'], PipelineStage::Livre];
        yield 'review sans plan → Implémenté' => [['pitch.md', 'review.md'], PipelineStage::Implemente];

        // `estimate.md` n'est pas déclencheur : plan gouverne, reste en Cadré.
        yield 'plan + estimate → Cadré' => [['plan.md', 'estimate.md'], PipelineStage::Cadre];
        // brief présent mais plan plus avancé l'emporte.
        yield 'brief + plan + adr → Cadré' => [['brief.md', 'plan.md', '0001-adr-choix.md'], PipelineStage::Cadre];

        // Aucun fichier de pipeline reconnu → À vérifier.
        yield 'fichier inconnu → À vérifier' => [['notes.txt'], PipelineStage::AVerifier];
        yield 'estimate seul → À vérifier' => [['estimate.md'], PipelineStage::AVerifier];
        yield 'dossier vide → À vérifier' => [[], PipelineStage::AVerifier];

        // Match top-level exact : un fichier en sous-dossier ne compte pas.
        yield 'pitch en sous-dossier → À vérifier' => [['feature-map/pitch.md'], PipelineStage::AVerifier];
        yield 'plan en sous-dossier ignoré, pitch top-level compte' => [['x/plan.md', 'pitch.md'], PipelineStage::Besoin];
        yield 'brief top-level compte si rien de plus avancé' => [['x/pitch.md', 'brief.md'], PipelineStage::Idee];
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
        self::assertSame(PipelineStage::Cadre, $first);
    }
}
