<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\Type\Provider;
use App\Service\InvalidRepositoryUrlException;
use App\Service\RepositoryUrlNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RepositoryUrlNormalizerTest extends TestCase
{
    private RepositoryUrlNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new RepositoryUrlNormalizer();
    }

    #[DataProvider('provideValidUrls')]
    public function testNormalize(string $input, Provider $provider, string $owner, string $repo, string $normalized): void
    {
        $result = $this->normalizer->normalize($input);

        self::assertSame($provider, $result->provider);
        self::assertSame($owner, $result->owner);
        self::assertSame($repo, $result->repo);
        self::assertSame($normalized, $result->normalizedUrl);
        self::assertSame($owner . '/' . $repo, $result->name());
    }

    /**
     * @return iterable<string, array{string, Provider, string, string, string}>
     */
    public static function provideValidUrls(): iterable
    {
        yield 'https' => ['https://github.com/symfony/symfony', Provider::GitHub, 'symfony', 'symfony', 'https://github.com/symfony/symfony'];
        yield 'https with .git' => ['https://github.com/symfony/symfony.git', Provider::GitHub, 'symfony', 'symfony', 'https://github.com/symfony/symfony'];
        yield 'https trailing slash' => ['https://github.com/symfony/symfony/', Provider::GitHub, 'symfony', 'symfony', 'https://github.com/symfony/symfony'];
        yield 'http downgraded' => ['http://github.com/symfony/symfony', Provider::GitHub, 'symfony', 'symfony', 'https://github.com/symfony/symfony'];
        yield 'ssh scp' => ['git@github.com:symfony/symfony.git', Provider::GitHub, 'symfony', 'symfony', 'https://github.com/symfony/symfony'];
        yield 'ssh url' => ['ssh://git@github.com/symfony/symfony.git', Provider::GitHub, 'symfony', 'symfony', 'https://github.com/symfony/symfony'];
        yield 'uppercase host' => ['https://GitHub.com/symfony/symfony', Provider::GitHub, 'symfony', 'symfony', 'https://github.com/symfony/symfony'];
        yield 'surrounding spaces' => ['  https://github.com/symfony/symfony  ', Provider::GitHub, 'symfony', 'symfony', 'https://github.com/symfony/symfony'];
        yield 'gitlab' => ['https://gitlab.com/gitlab-org/gitlab', Provider::GitLab, 'gitlab-org', 'gitlab', 'https://gitlab.com/gitlab-org/gitlab'];
        yield 'gitlab subgroup' => ['https://gitlab.com/group/sub/project.git', Provider::GitLab, 'group', 'sub/project', 'https://gitlab.com/group/sub/project'];
    }

    #[DataProvider('provideInvalidUrls')]
    public function testNormalizeRejectsInvalid(string $input): void
    {
        $this->expectException(InvalidRepositoryUrlException::class);
        $this->normalizer->normalize($input);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidUrls(): iterable
    {
        yield 'unknown host' => ['https://bitbucket.org/owner/repo'];
        yield 'missing repo segment' => ['https://github.com/symfony'];
        yield 'host only' => ['https://github.com'];
        yield 'empty' => [''];
        yield 'free text' => ['just some text'];
    }
}
