<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\Type\InterviewStatus;
use App\Message\RunInterviewTurn;
use App\Repository\InterviewRepository;
use App\Service\Interview\InterviewFailedException;
use App\Service\Interview\InterviewRunnerInterface;
use App\Service\Interview\ProducedBriefLocator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Exécute un tour d'interview hors requête HTTP : joue le tour via le runner, stocke la réponse
 * du skill, détecte le brief sur le filesystem, puis pose l'état final (`Awaiting`, `BriefReady`
 * ou `Failed`).
 *
 * La garde de statut (`Thinking` requis) borne la double-livraison Messenger : un message rejoué
 * sur une interview déjà avancée est ignoré (idempotence, pattern {@see CloneRepositoryHandler}).
 * Un échec métier est traduit en {@see InterviewStatus::Failed} lisible et **non re-propagé**
 * (inutile de solliciter le retry pour une erreur non transitoire).
 */
#[AsMessageHandler]
final readonly class RunInterviewTurnHandler
{
    public function __construct(
        private InterviewRepository $interviews,
        private InterviewRunnerInterface $runner,
        private ProducedBriefLocator $briefLocator,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(RunInterviewTurn $message): void
    {
        $interview = $this->interviews->find($message->interviewId);

        if (null === $interview || InterviewStatus::Thinking !== $interview->getStatus()) {
            // Interview supprimée, ou tour déjà joué (double livraison) : rien à faire.
            return;
        }

        try {
            $workingDir = $interview->getProject()->getLocalPath()
                ?? throw new InterviewFailedException('Le projet n\'est plus cloné localement.');

            $result = $this->runner->converse(
                $interview->getSessionId(),
                $workingDir,
                $interview->lastUserMessage(),
                $interview->isFirstTurn(),
            );

            $interview->addAssistantMessage($result->assistantText);

            $slug = $this->briefLocator->locate($workingDir);
            null !== $slug ? $interview->markBriefReady($slug) : $interview->markAwaiting();
        } catch (InterviewFailedException $e) {
            $interview->markFailed($e->getMessage());
        }

        $this->em->flush();
    }
}
