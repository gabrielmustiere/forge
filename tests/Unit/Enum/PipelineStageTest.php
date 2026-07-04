<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\Type\PipelineStage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PipelineStageTest extends TestCase
{
    /**
     * @param non-empty-string $label
     */
    #[DataProvider('provideLabels')]
    public function testLabel(PipelineStage $stage, string $label): void
    {
        self::assertSame($label, $stage->label());
    }

    /**
     * @return iterable<string, array{PipelineStage, string}>
     */
    public static function provideLabels(): iterable
    {
        yield 'cadrage' => [PipelineStage::Cadrage, 'Cadrage'];
        yield 'planifie' => [PipelineStage::Planifie, 'Planifié'];
        yield 'review' => [PipelineStage::Review, 'Review'];
        yield 'livre' => [PipelineStage::Livre, 'Livré'];
        yield 'a_verifier' => [PipelineStage::AVerifier, 'À vérifier'];
    }

    #[DataProvider('provideOnPipeline')]
    public function testIsOnPipeline(PipelineStage $stage, bool $expected): void
    {
        self::assertSame($expected, $stage->isOnPipeline());
    }

    /**
     * @return iterable<string, array{PipelineStage, bool}>
     */
    public static function provideOnPipeline(): iterable
    {
        yield 'cadrage' => [PipelineStage::Cadrage, true];
        yield 'planifie' => [PipelineStage::Planifie, true];
        yield 'review' => [PipelineStage::Review, true];
        yield 'livre' => [PipelineStage::Livre, true];
        yield 'a_verifier' => [PipelineStage::AVerifier, false];
    }
}
