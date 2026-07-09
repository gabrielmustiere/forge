<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\Type\CloneStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CloneStatusTest extends TestCase
{
    /**
     * @param non-empty-string                  $label
     * @param 'ok'|'neutral'|'warning'|'danger' $tone
     */
    #[DataProvider('provideCases')]
    public function testLabelAndBadgeTone(CloneStatus $status, string $label, string $tone): void
    {
        self::assertSame($label, $status->label());
        self::assertSame($tone, $status->badgeTone());
    }

    /**
     * @return iterable<string, array{CloneStatus, string, string}>
     */
    public static function provideCases(): iterable
    {
        yield 'not_cloned' => [CloneStatus::NotCloned, 'Non cloné', 'neutral'];
        yield 'cloning' => [CloneStatus::Cloning, 'Clonage…', 'neutral'];
        yield 'cloned' => [CloneStatus::Cloned, 'Cloné', 'ok'];
        yield 'failed' => [CloneStatus::Failed, 'Échec', 'danger'];
    }

    public function testEveryCaseHasAnIcon(): void
    {
        foreach (CloneStatus::cases() as $status) {
            self::assertNotSame('', $status->icon());
        }
    }
}
