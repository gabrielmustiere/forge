<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Enum\Type\Provider;
use App\Form\ProjectFormData;
use App\Repository\ProjectRepository;
use App\Service\RepositoryUrlNormalizer;
use App\Validator\UniqueRepositoryUrl;
use App\Validator\UniqueRepositoryUrlValidator;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<UniqueRepositoryUrlValidator>
 */
final class UniqueRepositoryUrlValidatorTest extends ConstraintValidatorTestCase
{
    private ProjectRepository&Stub $projects;

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->projects = $this->createStub(ProjectRepository::class);

        return new UniqueRepositoryUrlValidator(new RepositoryUrlNormalizer(), $this->projects);
    }

    public function testUniqueUrlPasses(): void
    {
        $this->projects->method('existsByNormalizedUrl')->willReturn(false);

        $this->validator->validate($this->data(Provider::GitHub, 'https://github.com/foo/bar'), new UniqueRepositoryUrl());

        $this->assertNoViolation();
    }

    public function testDuplicateUrlIsRejected(): void
    {
        $this->projects->method('existsByNormalizedUrl')->willReturn(true);
        $constraint = new UniqueRepositoryUrl();

        $this->validator->validate($this->data(Provider::GitHub, 'https://github.com/foo/bar.git'), $constraint);

        $this->buildViolation($constraint->duplicateMessage)->atPath('property.path.url')->assertRaised();
    }

    public function testProviderMismatchIsRejected(): void
    {
        $constraint = new UniqueRepositoryUrl();

        $this->validator->validate($this->data(Provider::GitHub, 'https://gitlab.com/foo/bar'), $constraint);

        $this->buildViolation($constraint->providerMismatchMessage)->atPath('property.path.url')->assertRaised();
    }

    public function testInvalidUrlIsRejected(): void
    {
        $constraint = new UniqueRepositoryUrl();

        $this->validator->validate($this->data(Provider::GitHub, 'not a repo url'), $constraint);

        $this->buildViolation($constraint->invalidMessage)->atPath('property.path.url')->assertRaised();
    }

    public function testEditingSameEntityPasses(): void
    {
        // Le dépôt n'est « déjà suivi » que par une autre entité : s'exclure soi-même lève la collision.
        $this->projects->method('existsByNormalizedUrl')
            ->willReturnCallback(static fn (string $url, ?int $excludeId): bool => 42 !== $excludeId);

        $data = $this->data(Provider::GitHub, 'https://github.com/foo/bar');
        $data->id = 42;

        $this->validator->validate($data, new UniqueRepositoryUrl());

        $this->assertNoViolation();
    }

    private function data(Provider $provider, string $url): ProjectFormData
    {
        $data = new ProjectFormData();
        $data->provider = $provider;
        $data->url = $url;

        return $data;
    }
}
