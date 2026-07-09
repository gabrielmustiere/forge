<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\Type\InterviewStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InterviewStatusTest extends TestCase
{
    /**
     * @param non-empty-string                  $label
     * @param 'ok'|'neutral'|'warning'|'danger' $tone
     */
    #[DataProvider('provideCases')]
    public function testLabelAndBadgeTone(InterviewStatus $status, string $label, string $tone): void
    {
        self::assertSame($label, $status->label());
        self::assertSame($tone, $status->badgeTone());
    }

    /**
     * @return iterable<string, array{InterviewStatus, string, string}>
     */
    public static function provideCases(): iterable
    {
        yield 'awaiting' => [InterviewStatus::Awaiting, 'En attente', 'neutral'];
        yield 'thinking' => [InterviewStatus::Thinking, 'Réflexion…', 'neutral'];
        yield 'brief_ready' => [InterviewStatus::BriefReady, 'À valider', 'warning'];
        yield 'submitting' => [InterviewStatus::Submitting, 'Dépôt…', 'neutral'];
        yield 'submitted' => [InterviewStatus::Submitted, 'Proposée', 'ok'];
        yield 'failed' => [InterviewStatus::Failed, 'Échec', 'danger'];
        yield 'abandoned' => [InterviewStatus::Abandoned, 'Abandonnée', 'neutral'];
    }

    public function testEveryCaseHasAnIcon(): void
    {
        foreach (InterviewStatus::cases() as $status) {
            self::assertNotSame('', $status->icon());
        }
    }

    #[DataProvider('provideActiveClassification')]
    public function testIsActive(InterviewStatus $status, bool $active): void
    {
        self::assertSame($active, $status->isActive());
    }

    /**
     * @return iterable<string, array{InterviewStatus, bool}>
     */
    public static function provideActiveClassification(): iterable
    {
        yield 'awaiting is active' => [InterviewStatus::Awaiting, true];
        yield 'thinking is active' => [InterviewStatus::Thinking, true];
        yield 'brief_ready is active' => [InterviewStatus::BriefReady, true];
        yield 'submitting is active' => [InterviewStatus::Submitting, true];
        yield 'failed is active (recoverable)' => [InterviewStatus::Failed, true];
        yield 'submitted is terminal' => [InterviewStatus::Submitted, false];
        yield 'abandoned is terminal' => [InterviewStatus::Abandoned, false];
    }

    #[DataProvider('provideInFlightClassification')]
    public function testIsInFlight(InterviewStatus $status, bool $inFlight): void
    {
        self::assertSame($inFlight, $status->isInFlight());
    }

    /**
     * @return iterable<string, array{InterviewStatus, bool}>
     */
    public static function provideInFlightClassification(): iterable
    {
        yield 'thinking polls' => [InterviewStatus::Thinking, true];
        yield 'submitting polls' => [InterviewStatus::Submitting, true];
        yield 'awaiting does not poll' => [InterviewStatus::Awaiting, false];
        yield 'brief_ready does not poll' => [InterviewStatus::BriefReady, false];
        yield 'submitted does not poll' => [InterviewStatus::Submitted, false];
        yield 'failed does not poll' => [InterviewStatus::Failed, false];
        yield 'abandoned does not poll' => [InterviewStatus::Abandoned, false];
    }
}
