<?php

declare(strict_types=1);

namespace App\Validator;

use App\Form\ProjectFormData;
use App\Repository\ProjectRepository;
use App\Service\InvalidRepositoryUrlException;
use App\Service\RepositoryUrlNormalizer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniqueRepositoryUrlValidator extends ConstraintValidator
{
    public function __construct(
        private readonly RepositoryUrlNormalizer $normalizer,
        private readonly ProjectRepository $projects,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueRepositoryUrl) {
            throw new UnexpectedValueException($constraint, UniqueRepositoryUrl::class);
        }

        if (!$value instanceof ProjectFormData) {
            throw new UnexpectedValueException($value, ProjectFormData::class);
        }

        // Laisse NotBlank / NotNull traiter les champs vides.
        if (null === $value->provider || null === $value->url || '' === trim($value->url)) {
            return;
        }

        try {
            $repositoryUrl = $this->normalizer->normalize($value->url);
        } catch (InvalidRepositoryUrlException) {
            $this->context->buildViolation($constraint->invalidMessage)->atPath('url')->addViolation();

            return;
        }

        if ($repositoryUrl->provider !== $value->provider) {
            $this->context->buildViolation($constraint->providerMismatchMessage)->atPath('url')->addViolation();

            return;
        }

        if ($this->projects->existsByNormalizedUrl($repositoryUrl->normalizedUrl, $value->id)) {
            $this->context->buildViolation($constraint->duplicateMessage)->atPath('url')->addViolation();
        }
    }
}
