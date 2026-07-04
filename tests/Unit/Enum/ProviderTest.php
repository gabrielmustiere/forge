<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\Type\Provider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProviderTest extends TestCase
{
    /**
     * @param non-empty-string $host
     * @param non-empty-string $label
     */
    #[DataProvider('provideCases')]
    public function testHostAndLabel(Provider $provider, string $host, string $label): void
    {
        self::assertSame($host, $provider->host());
        self::assertSame($label, $provider->label());
    }

    /**
     * @return iterable<string, array{Provider, string, string}>
     */
    public static function provideCases(): iterable
    {
        yield 'github' => [Provider::GitHub, 'github.com', 'GitHub'];
        yield 'gitlab' => [Provider::GitLab, 'gitlab.com', 'GitLab'];
    }

    public function testIcon(): void
    {
        self::assertSame('tabler:brand-github', Provider::GitHub->icon());
        self::assertSame('tabler:brand-gitlab', Provider::GitLab->icon());
    }
}
