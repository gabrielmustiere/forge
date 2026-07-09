<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Type\InterviewStatus;
use App\Enum\Type\MessageRole;
use App\Repository\InterviewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Interview de cadrage rattachée à un projet (story 009) : un dialogue multi-tours avec le
 * skill `feature-interview` exécuté en headless dans le clone local, jusqu'à la production
 * d'un `brief.md` déposé en PR draft.
 *
 * Entité à état sur le modèle de {@see Project} : les transitions posent le statut **et** ses
 * champs liés ensemble (jamais l'un sans l'autre), pilotées par les jobs asynchrones
 * {@see \App\Message\RunInterviewTurn} et {@see \App\Message\SubmitBrief}. `updatedAt` est
 * rebougé à chaque transition ; `createdAt` ne change jamais.
 *
 * @see InterviewStatus pour le cycle de vie (actif / terminal / in-flight).
 */
#[ORM\Entity(repositoryClass: InterviewRepository::class)]
class Interview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.unusedType (assigned by Doctrine) */
    private ?int $id = null;

    /** État courant du parcours ; `Awaiting` à la création (en attente du premier message). */
    #[ORM\Column(name: 'status', enumType: InterviewStatus::class, options: ['default' => 'awaiting'])]
    private InterviewStatus $status;

    /** Slug `NNN-f-<slug>` de la story produite ; `null` tant que le brief n'est pas détecté. */
    #[ORM\Column(name: 'story_slug', length: 255, nullable: true)]
    private ?string $storySlug = null;

    /** URL de la PR draft ; `null` tant qu'elle n'est pas ouverte. */
    #[ORM\Column(name: 'pull_request_url', length: 255, nullable: true)]
    private ?string $pullRequestUrl = null;

    /** Raison lisible du dernier échec (sans token ni secret) ; effacée à chaque relance. */
    #[ORM\Column(name: 'last_error', type: Types::TEXT, nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column(name: 'created_at')]
    private readonly \DateTimeImmutable $createdAt;

    /** Horodatage de la dernière transition ; `null` tant qu'aucune n'a eu lieu après la création. */
    #[ORM\Column(name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Le fil de conversation, ordonné chronologiquement (ré-affichable après reload).
     *
     * @var Collection<int, InterviewMessage>
     */
    #[ORM\OneToMany(targetEntity: InterviewMessage::class, mappedBy: 'interview', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'interviews')]
        #[ORM\JoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
        private readonly Project $project,
        /** UUID de session `claude`, généré à la création et réutilisé par `--resume`. */
        #[ORM\Column(name: 'session_id', length: 255)]
        private readonly string $sessionId,
    ) {
        $this->status = InterviewStatus::Awaiting;
        $this->createdAt = new \DateTimeImmutable();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getStatus(): InterviewStatus
    {
        return $this->status;
    }

    public function getStorySlug(): ?string
    {
        return $this->storySlug;
    }

    public function getPullRequestUrl(): ?string
    {
        return $this->pullRequestUrl;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, InterviewMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /** Ajoute un tour saisi par l'utilisateur et rebouge `updatedAt`. */
    public function addUserMessage(string $content): static
    {
        return $this->addMessage(MessageRole::User, $content);
    }

    /** Ajoute la réponse du skill et rebouge `updatedAt`. */
    public function addAssistantMessage(string $content): static
    {
        return $this->addMessage(MessageRole::Assistant, $content);
    }

    /** Un tour (ou le dépôt) part en tâche de fond : efface l'erreur précédente. */
    public function markThinking(): static
    {
        $this->status = InterviewStatus::Thinking;
        $this->lastError = null;

        return $this->touch();
    }

    /** Le skill a rendu la main : on attend le prochain message de l'utilisateur. */
    public function markAwaiting(): static
    {
        $this->status = InterviewStatus::Awaiting;

        return $this->touch();
    }

    /** Le brief a été détecté sur le filesystem : on pose le slug et on attend la validation. */
    public function markBriefReady(string $storySlug): static
    {
        $this->status = InterviewStatus::BriefReady;
        $this->storySlug = $storySlug;

        return $this->touch();
    }

    /** Le dépôt (push + PR draft) est lancé : efface l'erreur précédente. */
    public function markSubmitting(): static
    {
        $this->status = InterviewStatus::Submitting;
        $this->lastError = null;

        return $this->touch();
    }

    /** PR draft ouverte : parcours terminé, on pose son URL. */
    public function markSubmitted(string $pullRequestUrl): static
    {
        $this->status = InterviewStatus::Submitted;
        $this->pullRequestUrl = $pullRequestUrl;

        return $this->touch();
    }

    /** Échec récupérable : pose la raison lisible, l'utilisateur peut re-tenter. */
    public function markFailed(string $reason): static
    {
        $this->status = InterviewStatus::Failed;
        $this->lastError = $reason;

        return $this->touch();
    }

    /** Abandon : terminal, libère le créneau « 1 active par projet » (aucun effet distant). */
    public function markAbandoned(): static
    {
        $this->status = InterviewStatus::Abandoned;

        return $this->touch();
    }

    /** Aucun message assistant encore reçu : le prochain tour est le premier (pas de `--resume`). */
    public function isFirstTurn(): bool
    {
        foreach ($this->messages as $message) {
            if (MessageRole::Assistant === $message->getRole()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Contenu du dernier message utilisateur (celui qui pilote le tour à exécuter).
     *
     * @throws \LogicException si aucun message utilisateur n'a encore été ajouté
     */
    public function lastUserMessage(): string
    {
        $last = null;
        foreach ($this->messages as $message) {
            if (MessageRole::User === $message->getRole()) {
                $last = $message;
            }
        }

        return $last?->getContent() ?? throw new \LogicException('Aucun message utilisateur dans cette interview.');
    }

    private function addMessage(MessageRole $role, string $content): static
    {
        $this->messages->add(new InterviewMessage($this, $role, $content));

        return $this->touch();
    }

    private function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
