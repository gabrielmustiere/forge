<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Type\Provider;
use App\Enum\Type\VerificationStatus;
use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_PROJECT_URL', fields: ['url'])]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.unusedType (assigned by Doctrine) */
    private ?int $id = null;

    #[ORM\Column(name: 'created_at')]
    private readonly \DateTimeImmutable $createdAt;

    /** Résultat de la dernière vérification d'accès ; `Unverified` tant qu'aucune n'a eu lieu. */
    #[ORM\Column(name: 'verification_status', enumType: VerificationStatus::class, options: ['default' => 'unverified'])]
    private VerificationStatus $verificationStatus;

    /** Horodatage de la dernière vérification ; `null` tant qu'aucune n'a eu lieu. */
    #[ORM\Column(name: 'verified_at', nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    public function __construct(
        #[ORM\Column(enumType: Provider::class)]
        private Provider $provider,
        #[ORM\Column(length: 255)]
        private string $url,
        #[ORM\Column(length: 255)]
        private string $name,
        #[ORM\Column(type: Types::TEXT)]
        private string $token,
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->verificationStatus = VerificationStatus::Unverified;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    public function setProvider(Provider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getVerificationStatus(): VerificationStatus
    {
        return $this->verificationStatus;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    /**
     * Pose le statut et son horodatage de façon cohérente (jamais l'un sans l'autre).
     */
    public function applyVerification(VerificationStatus $status, \DateTimeImmutable $at): static
    {
        $this->verificationStatus = $status;
        $this->verifiedAt = $at;

        return $this;
    }
}
