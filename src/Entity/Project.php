<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Type\CloneStatus;
use App\Enum\Type\Provider;
use App\Enum\Type\VerificationStatus;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /** État du clone local ; `NotCloned` tant qu'aucun clone n'a été demandé. */
    #[ORM\Column(name: 'clone_status', enumType: CloneStatus::class, options: ['default' => 'not_cloned'])]
    private CloneStatus $cloneStatus;

    /** Horodatage du dernier clone/pull réussi ; `null` tant qu'aucun n'a abouti. */
    #[ORM\Column(name: 'cloned_at', nullable: true)]
    private ?\DateTimeImmutable $clonedAt = null;

    /** Chemin absolu de la copie locale ; `null` tant que le clone n'a pas réussi. */
    #[ORM\Column(name: 'local_path', length: 255, nullable: true)]
    private ?string $localPath = null;

    /** Raison lisible du dernier échec de clone (sans token ni URL crédentialisée). */
    #[ORM\Column(name: 'last_clone_error', type: Types::TEXT, nullable: true)]
    private ?string $lastCloneError = null;

    /**
     * Interviews de cadrage rattachées au projet (story 009). Inverse purement lisible :
     * la garde « 1 active par projet » passe par {@see \App\Repository\InterviewRepository}.
     *
     * @var Collection<int, Interview>
     */
    #[ORM\OneToMany(targetEntity: Interview::class, mappedBy: 'project')]
    private Collection $interviews;

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
        $this->cloneStatus = CloneStatus::NotCloned;
        $this->interviews = new ArrayCollection();
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

    public function getCloneStatus(): CloneStatus
    {
        return $this->cloneStatus;
    }

    /** Précondition d'interview (story 009) : le repo doit être rapatrié localement. */
    public function isCloned(): bool
    {
        return CloneStatus::Cloned === $this->cloneStatus;
    }

    /**
     * @return Collection<int, Interview>
     */
    public function getInterviews(): Collection
    {
        return $this->interviews;
    }

    public function getClonedAt(): ?\DateTimeImmutable
    {
        return $this->clonedAt;
    }

    public function getLocalPath(): ?string
    {
        return $this->localPath;
    }

    public function getLastCloneError(): ?string
    {
        return $this->lastCloneError;
    }

    /**
     * Passe le clone en « en cours » et efface toute erreur précédente.
     * Appelé en synchrone avant le dispatch du job pour borner le double-clic.
     */
    public function markCloning(): static
    {
        $this->cloneStatus = CloneStatus::Cloning;
        $this->lastCloneError = null;

        return $this;
    }

    /**
     * Pose l'état de succès de façon cohérente : statut, chemin local et horodatage
     * ensemble, erreur remise à zéro.
     */
    public function markCloned(string $localPath, \DateTimeImmutable $at): static
    {
        $this->cloneStatus = CloneStatus::Cloned;
        $this->localPath = $localPath;
        $this->clonedAt = $at;
        $this->lastCloneError = null;

        return $this;
    }

    /**
     * Pose l'état d'échec avec la raison lisible (jamais de token ni d'URL crédentialisée).
     * `clonedAt` reste inchangé : il n'horodate que les succès (un pull raté ne l'efface pas).
     */
    public function markCloneFailed(string $reason): static
    {
        $this->cloneStatus = CloneStatus::Failed;
        $this->lastCloneError = $reason;

        return $this;
    }
}
