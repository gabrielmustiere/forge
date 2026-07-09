<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Type\MessageRole;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un tour de conversation d'une {@see Interview} (story 009) : le fil est ré-affichable
 * à l'identique après un rechargement de page. Créé uniquement par les méthodes de
 * transition de l'{@see Interview} parente ({@see Interview::addUserMessage()} /
 * {@see Interview::addAssistantMessage()}), jamais isolément.
 */
#[ORM\Entity]
class InterviewMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.unusedType (assigned by Doctrine) */
    private ?int $id = null;

    #[ORM\Column(name: 'created_at')]
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'messages')]
        #[ORM\JoinColumn(name: 'interview_id', nullable: false, onDelete: 'CASCADE')]
        private readonly Interview $interview,
        #[ORM\Column(enumType: MessageRole::class)]
        private readonly MessageRole $role,
        #[ORM\Column(type: Types::TEXT)]
        private readonly string $content,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInterview(): Interview
    {
        return $this->interview;
    }

    public function getRole(): MessageRole
    {
        return $this->role;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
