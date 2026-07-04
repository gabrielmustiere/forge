<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\Type\VerificationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VerificationStatusTest extends TestCase
{
    /**
     * @param non-empty-string                  $label
     * @param 'ok'|'neutral'|'warning'|'danger' $tone
     */
    #[DataProvider('provideCases')]
    public function testLabelAndBadgeTone(VerificationStatus $status, string $label, string $tone): void
    {
        self::assertSame($label, $status->label());
        self::assertSame($tone, $status->badgeTone());
    }

    /**
     * @return iterable<string, array{VerificationStatus, string, string}>
     */
    public static function provideCases(): iterable
    {
        yield 'unverified' => [VerificationStatus::Unverified, 'Non vérifié', 'neutral'];
        yield 'eligible' => [VerificationStatus::Eligible, 'Éligible', 'ok'];
        yield 'not_forge' => [VerificationStatus::NotForge, 'Non-forge', 'warning'];
        yield 'invalid_token' => [VerificationStatus::InvalidToken, 'Token invalide', 'danger'];
        yield 'unreachable' => [VerificationStatus::Unreachable, 'Injoignable', 'danger'];
        yield 'unsupported_provider' => [VerificationStatus::UnsupportedProvider, 'Provider non scannable', 'neutral'];
    }

    public function testEveryCaseHasAnIcon(): void
    {
        foreach (VerificationStatus::cases() as $status) {
            self::assertNotSame('', $status->icon());
        }
    }
}
